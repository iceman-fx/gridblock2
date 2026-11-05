<?php
/*
	Redaxo-Addon Gridblock²
	Favoriten (Presets): Datenzugriff, Rechte, Sichtbarkeit
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class Gridblock2PresetsException extends rex_exception
{
	private int $httpStatus;

	public function __construct(string $message, int $httpStatus = 400)
	{	parent::__construct($message);
		$this->httpStatus = $httpStatus;
	}

	public function getHttpStatus(): int
	{	return $this->httpStatus;
	}
}


class Gridblock2Presets
{
	public const TABLE = 'gridblock2_presets';
	public const PERM = 'gridblock2[favorites]';
	public const KINDS = ['element', 'section', 'columns', 'group'];
	public const NODE_TYPES = ['module', 'custom', 'section', 'columns'];
	public const MODES = ['project', 'user', 'choose'];
	public const MAX_TITLE = 120;
	public const MAX_LABEL = 60;
	public const MAX_PAYLOAD = 524288;
	public const MAX_NODES = 50;
	public const MAX_FIELDS = 5000;
	public const MAX_TREE_NODES = 500;
	public const MAX_TREE_DEPTH = 16;
	public const MAX_LIST = 200;
	public const MAX_TOTAL = 2000;
	public const MAX_PER_USER = 300;

	public const MAX_SETTINGS = 200000;
	private const PLAIN_FIELD_NAME = '/^[A-Za-z0-9_][A-Za-z0-9_\-\[\]\.:]{0,255}$/';
	private const FIELD_NAME ='/^REX_INPUT_VALUE\[\d{1,4}\]\[[A-Za-z0-9_-]{1,128}\](?:\[[^\[\]\r\n]{0,128}\]){0,8}$/';


	public static function table(): string
	{	return rex::getTable(self::TABLE);
	}

	public static function ensureTable(bool $force = false): void
	{	static $checked = false;
		if ($checked && !$force) {
			return;
		}

		$table = rex_sql_table::get(self::table());
		if ($force || !$table->exists()) {
			$table
				->ensureColumn(new rex_sql_column('id', 'int(11)', false, null, 'auto_increment'))
				->ensureColumn(new rex_sql_column('title', 'varchar(255)'))
				->ensureColumn(new rex_sql_column('kind', 'varchar(20)'))
				->ensureColumn(new rex_sql_column('payload', 'mediumtext'))
				->ensureColumn(new rex_sql_column('summary', 'varchar(255)', true))
				->ensureColumn(new rex_sql_column('domain_id', 'int(11)', false, '0'))
				->ensureColumn(new rex_sql_column('private', 'tinyint(1)', false, '0'))
				->ensureColumn(new rex_sql_column('created_by', 'int(11)', false, '0'))
				->ensureColumn(new rex_sql_column('used_count', 'int(11)', false, '0'))
				->ensureColumn(new rex_sql_column('last_used', 'datetime', true))
				->ensureGlobalColumns()
				->setPrimaryKey('id')
				->ensure();
		}

		$checked = true;
	}

	public static function getToken(): string
	{	return rex_csrf_token::factory('rex_api_gridblock2_favorites')->getValue();
	}


	public static function isLoggedIn(): bool
	{	return rex::getUser() !== null;
	}

	public static function isAdmin(): bool
	{	$user = rex::getUser();

		return $user !== null && ($user->isAdmin() || $user->hasPerm('gridblock2[admin]'));
	}

	public static function canUse(): bool
	{	return self::isLoggedIn();
	}

	public static function canSave(): bool
	{	$user = rex::getUser();

		return $user !== null && (self::isAdmin() || $user->hasPerm(self::PERM));
	}

	public static function userId(): int
	{	$user = rex::getUser();

		return $user !== null ? (int) $user->getId() : 0;
	}

	public static function canModify(array $row): bool
	{	if (!self::isLoggedIn()) {
			return false;
		}
		if (self::isAdmin()) {
			return true;
		}

		return self::canSave() && (int) ($row['created_by'] ?? 0) === self::userId();
	}


	public static function mode(): string
	{	$mode = (string) (rex_gridblock2::getConfig('gb_favorites_mode') ?? '');

		return in_array($mode, self::MODES, true) ? $mode : 'project';
	}

	public static function crossDomainAllowed(): bool
	{	return (string) (rex_gridblock2::getConfig('gb_favorites_cross_domain') ?? '') === 'checked';
	}

	public static function multisiteActive(): bool
	{	return Gridblock2Builder_MultiSite::isEnabled();
	}


	private static function visibility(int $domainId, bool $ignoreDomain): array
	{	$sql = '(`private` = 0 OR `created_by` = :uid)';
		$params = ['uid' => self::userId()];

		if (self::multisiteActive() && !$ignoreDomain) {
			$sql .= ' AND (`domain_id` = 0 OR `domain_id` = :dom)';
			$params['dom'] = $domainId;
		}

		return [$sql, $params];
	}

	private static function present(array $row): array
	{	return [
			'id' => (int) $row['id'],
			'title' => (string) $row['title'],
			'kind' => (string) $row['kind'],
			'summary' => (string) $row['summary'],
			'private' => (int) $row['private'] === 1,
			'domain_id' => (int) $row['domain_id'],
			'author' => (string) ($row['createuser'] ?? ''),
			'mine' => (int) $row['created_by'] === self::userId(),
			'editable' => self::canModify($row),
		];
	}


	public static function listVisible(string $search = '', bool $ignoreDomain = false): array
	{	if (!self::canUse()) {
			throw new Gridblock2PresetsException('forbidden', 403);
		}

		[$where, $params] = self::visibility(Gridblock2Builder_MultiSite::getCurrentDomainId(), $ignoreDomain);

		$search = trim($search);
		if ($search !== '') {
			$where .= ' AND `title` LIKE :q';
			$params['q'] = '%'.addcslashes(mb_substr($search, 0, 100), '%_\\').'%';
		}

		$sql = rex_sql::factory();
		$rows = $sql->getArray(
			'SELECT `id`, `title`, `kind`, `summary`, `private`, `domain_id`, `created_by`, `createuser` FROM '.self::table().' WHERE '.$where.' ORDER BY `title` ASC, `id` ASC LIMIT '.self::MAX_LIST,
			$params
		);

		return array_map([self::class, 'present'], $rows);
	}

	public static function find(int $id): ?array
	{	if ($id <= 0) {
			return null;
		}

		$rows = rex_sql::factory()->getArray('SELECT * FROM '.self::table().' WHERE `id` = :id', ['id' => $id]);

		return $rows[0] ?? null;
	}

	public static function findVisible(int $id, bool $ignoreDomain = false): ?array
	{	if ($id <= 0 || !self::canUse()) {
			return null;
		}

		[$where, $params] = self::visibility(Gridblock2Builder_MultiSite::getCurrentDomainId(), $ignoreDomain);
		$params['id'] = $id;
		$rows = rex_sql::factory()->getArray('SELECT * FROM '.self::table().' WHERE `id` = :id AND '.$where, $params);

		return $rows[0] ?? null;
	}


	public static function normalizeTitle(string $title): string
	{	$title = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $title);
		$title = trim((string) preg_replace('/\s+/u', ' ', $title));
		$title = mb_substr($title, 0, self::MAX_TITLE);

		if ($title === '') {
			throw new Gridblock2PresetsException('title required', 422);
		}

		return $title;
	}

	private static function checkTree($value, int $depth, int &$count): void
	{	if ($depth > self::MAX_TREE_DEPTH) {
			throw new Gridblock2PresetsException('payload too deep', 422);
		}
		if (is_array($value)) {
			foreach ($value as $key => $item) {
				if (++$count > self::MAX_TREE_NODES * 20) {
					throw new Gridblock2PresetsException('payload too large', 422);
				}
				if (!is_int($key) && !is_string($key)) {
					throw new Gridblock2PresetsException('invalid payload', 422);
				}
				self::checkTree($item, $depth + 1, $count);
			}
		} elseif (!is_scalar($value) && $value !== null) {
			throw new Gridblock2PresetsException('invalid payload', 422);
		}
	}

	private static function cleanLabel($label): string
	{	$label = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $label));

		return mb_substr($label, 0, self::MAX_LABEL);
	}

	private static function cleanUid($uid): string
	{	$uid = (string) $uid;
		if (!rex_article_content_gridblock2::isValidUID($uid)) {
			throw new Gridblock2PresetsException('invalid uid', 422);
		}

		return $uid;
	}

	private static function cleanFields($fields, bool $plain = false): array
	{	if (!is_array($fields) || !array_is_list($fields) || count($fields) > self::MAX_FIELDS) {
			throw new Gridblock2PresetsException('invalid fields', 422);
		}

		$clean = [];
		foreach ($fields as $pair) {
			if (!is_array($pair) || count($pair) !== 2 || !isset($pair[0]) || !is_string($pair[0]) || !preg_match($plain ? self::PLAIN_FIELD_NAME : self::FIELD_NAME, $pair[0])) {
				throw new Gridblock2PresetsException('invalid field name', 422);
			}
			if (!is_scalar($pair[1]) && $pair[1] !== null) {
				throw new Gridblock2PresetsException('invalid field value', 422);
			}
			$clean[] = [$pair[0], (string) $pair[1]];
		}

		return $clean;
	}

	public static function validatePayload(string $raw): array
	{	if ($raw === '' || strlen($raw) > self::MAX_PAYLOAD) {
			throw new Gridblock2PresetsException('payload size', 422);
		}

		$data = json_decode($raw, true, 32);
		if (!is_array($data) || (int) ($data['v'] ?? 0) !== 1 || !isset($data['nodes']) || !is_array($data['nodes']) || !array_is_list($data['nodes'])
			|| $data['nodes'] === [] || count($data['nodes']) > self::MAX_NODES) {
			throw new Gridblock2PresetsException('invalid payload', 422);
		}

		$nodes = [];
		$labels = [];
		foreach ($data['nodes'] as $node) {
			if (!is_array($node) || !in_array($node['type'] ?? '', self::NODE_TYPES, true)) {
				throw new Gridblock2PresetsException('invalid node', 422);
			}

			$type = (string) $node['type'];
			$clean = ['type' => $type, 'label' => self::cleanLabel($node['label'] ?? ''), 'fields' => self::cleanFields($node['fields'] ?? [], $type === 'custom')];

			if ($type === 'module') {
				$moduleId = (int) ($node['id'] ?? 0);
				if ($moduleId <= 0) {
					throw new Gridblock2PresetsException('invalid module', 422);
				}
				$clean['id'] = $moduleId;
				$clean['uid'] = self::cleanUid($node['uid'] ?? '');
			} elseif ($type === 'custom') {
				$element = (string) ($node['element'] ?? '');
				if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $element)) {
					throw new Gridblock2PresetsException('invalid element', 422);
				}
				$clean['element'] = $element;
				$clean['uid'] = self::cleanUid($node['uid'] ?? '');
				$settings = (string) ($node['settings'] ?? '');
				if (strlen($settings) > self::MAX_SETTINGS) {
					throw new Gridblock2PresetsException('settings too large', 422);
				}
				$clean['settings'] = $settings;
			} else {
				if (!isset($node['tree']) || !is_array($node['tree']) || (string) ($node['tree']['type'] ?? '') !== $type) {
					throw new Gridblock2PresetsException('invalid tree', 422);
				}
				$count = 0;
				self::checkTree($node['tree'], 0, $count);
				$clean['tree'] = $node['tree'];
			}

			$nodes[] = $clean;
			$labels[] = ($clean['label'] !== '') ? $clean['label'] : $type;
		}

		return ['json' => (string) json_encode(['v' => 1, 'nodes' => $nodes], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'nodes' => $nodes, 'labels' => $labels];
	}

	private static function buildSummary(array $labels): string
	{	if (count($labels) < 2) {
			return '';
		}

		$shown = array_slice($labels, 0, 5);
		$summary = implode(' · ', $shown).(count($labels) > 5 ? ' …' : '');

		return mb_substr($summary, 0, 250);
	}

	private static function collectElementTypes($value, array &$types, int $depth = 0): void
	{	if (!is_array($value) || $depth > self::MAX_TREE_DEPTH + 4) {
			return;
		}
		if (($value['type'] ?? '') === 'element' && is_string($value['elementType'] ?? null) && $value['elementType'] !== '') {
			$types[$value['elementType']] = true;
		}
		foreach ($value as $item) {
			if (is_array($item)) {
				self::collectElementTypes($item, $types, $depth + 1);
			}
		}
	}

	private static function assertNoDemoElements(array $nodes): void
	{	$types = [];
		foreach ($nodes as $node) {
			if (($node['type'] ?? '') === 'custom') {
				$types[(string) $node['element']] = true;
			}
			if (isset($node['tree'])) {
				self::collectElementTypes($node['tree'], $types);
			}
		}
		if ($types === []) {
			return;
		}

		$known = Gridblock2Builder_Tree::discoverCustomElementTypes();
		foreach (array_keys($types) as $type) {
			$meta = $known[$type] ?? null;
			if ($meta === null) {
				throw new Gridblock2PresetsException('unknown element', 422);
			}
			if (!empty($meta['demoOnly'])) {
				throw new Gridblock2PresetsException('demo element not allowed', 422);
			}
		}
	}

	private static function checkKind(string $kind, array $nodes): void
	{	if (!in_array($kind, self::KINDS, true)) {
			throw new Gridblock2PresetsException('invalid kind', 422);
		}

		$types = array_column($nodes, 'type');
		$ok = match ($kind) {
			'element' => count($nodes) === 1 && in_array($types[0], ['module', 'custom'], true),
			'section' => count($nodes) === 1 && $types[0] === 'section',
			'columns' => count($nodes) === 1 && $types[0] === 'columns',
			default => true,
		};
		if (!$ok) {
			throw new Gridblock2PresetsException('kind does not match payload', 422);
		}
	}

	private static function resolveDomain(string $domain, bool $isDemo): int
	{	if (!self::multisiteActive()) {
			return 0;
		}

		if ($domain === 'all') {
			if (!self::crossDomainAllowed()) {
				throw new Gridblock2PresetsException('cross-domain favorites not enabled', 403);
			}

			return 0;
		}

		$domainId = ($domain === 'current' || $domain === '') ? Gridblock2Builder_MultiSite::getCurrentDomainId() : (ctype_digit($domain) ? (int) $domain : 0);
		if ($domainId <= 0 || ($isDemo && $domain === 'current')) {
			throw new Gridblock2PresetsException('domain required', 422);
		}
		if (!array_key_exists($domainId, Gridblock2Builder_MultiSite::getDomains())) {
			throw new Gridblock2PresetsException('unknown domain', 422);
		}
		if (!self::isAdmin() && $domainId !== Gridblock2Builder_MultiSite::getCurrentDomainId()) {
			throw new Gridblock2PresetsException('forbidden', 403);
		}

		return $domainId;
	}

	private static function resolvePrivate(?bool $requested): int
	{	if (self::isAdmin()) {
			return $requested === true ? 1 : 0;
		}

		return match (self::mode()) {
			'user' => 1,
			'choose' => $requested === true ? 1 : 0,
			default => 0,
		};
	}


	public static function save(string $title, string $kind, string $payload, ?bool $private, string $domain, bool $isDemo): array
	{	if (!self::canSave()) {
			throw new Gridblock2PresetsException('forbidden', 403);
		}

		$title = self::normalizeTitle($title);
		$validated = self::validatePayload($payload);
		self::checkKind($kind, $validated['nodes']);
		self::assertNoDemoElements($validated['nodes']);

		$total = (int) rex_sql::factory()->getArray('SELECT COUNT(*) AS `c` FROM '.self::table())[0]['c'];
		if ($total >= self::MAX_TOTAL) {
			throw new Gridblock2PresetsException('limit reached', 409);
		}

		$user = rex::getUser();
		$now = rex_sql::datetime();

		if (!self::isAdmin()) {
			$own = (int) rex_sql::factory()->getArray('SELECT COUNT(*) AS `c` FROM '.self::table().' WHERE `created_by` = :uid', ['uid' => (int) $user->getId()])[0]['c'];
			if ($own >= self::MAX_PER_USER) {
				throw new Gridblock2PresetsException('limit reached', 409);
			}
		}

		$sql = rex_sql::factory();
		$sql->setTable(self::table());
		$sql->setValue('title', $title);
		$sql->setValue('kind', $kind);
		$sql->setValue('payload', $validated['json']);
		$sql->setValue('summary', self::buildSummary($validated['labels']));
		$sql->setValue('domain_id', self::resolveDomain($domain, $isDemo));
		$sql->setValue('private', self::resolvePrivate($private));
		$sql->setValue('created_by', (int) $user->getId());
		$sql->setValue('used_count', 0);
		$sql->setValue('createdate', $now);
		$sql->setValue('createuser', (string) $user->getLogin());
		$sql->setValue('updatedate', $now);
		$sql->setValue('updateuser', (string) $user->getLogin());
		$sql->insert();

		$row = self::find((int) $sql->getLastId());

		return $row ? self::present($row) : [];
	}

	public static function fetchForInsert(int $id, bool $ignoreDomain = false): array
	{	$row = self::findVisible($id, $ignoreDomain);
		if ($row === null) {
			throw new Gridblock2PresetsException('not found', 404);
		}

		$validated = self::validatePayload((string) $row['payload']);

		rex_sql::factory()->setQuery(
			'UPDATE '.self::table().' SET `used_count` = `used_count` + 1, `last_used` = :now WHERE `id` = :id',
			['now' => rex_sql::datetime(), 'id' => (int) $row['id']]
		);

		return ['id' => (int) $row['id'], 'title' => (string) $row['title'], 'kind' => (string) $row['kind'], 'nodes' => $validated['nodes']];
	}

	public static function rename(int $id, string $title): array
	{	$row = self::find($id);
		if ($row === null || !self::canUse() || !self::isVisibleTo($row)) {
			throw new Gridblock2PresetsException('not found', 404);
		}
		if (!self::canModify($row)) {
			throw new Gridblock2PresetsException('forbidden', 403);
		}

		$user = rex::getUser();
		$sql = rex_sql::factory();
		$sql->setTable(self::table());
		$sql->setWhere('`id` = :id', ['id' => $id]);
		$sql->setValue('title', self::normalizeTitle($title));
		$sql->setValue('updatedate', rex_sql::datetime());
		$sql->setValue('updateuser', (string) $user->getLogin());
		$sql->update();

		return self::present(self::find($id) ?? $row);
	}

	public static function delete(int $id): void
	{	$row = self::find($id);
		if ($row === null || !self::canUse() || !self::isVisibleTo($row)) {
			throw new Gridblock2PresetsException('not found', 404);
		}
		if (!self::canModify($row)) {
			throw new Gridblock2PresetsException('forbidden', 403);
		}

		$sql = rex_sql::factory();
		$sql->setTable(self::table());
		$sql->setWhere('`id` = :id', ['id' => $id]);
		$sql->delete();
	}

	private static function requireAdmin(): void
	{	if (!self::isAdmin()) {
			throw new Gridblock2PresetsException('forbidden', 403);
		}
	}

	private static function idList(array $ids): array
	{	$clean = [];
		foreach ($ids as $id) {
			if (is_scalar($id) && ctype_digit((string) $id) && (int) $id > 0) {
				$clean[(int) $id] = (int) $id;
			}
		}

		return array_values(array_slice($clean, 0, 500));
	}

	private static function inClause(array $ids, array &$params): string
	{	$names = [];
		foreach ($ids as $i => $id) {
			$params['i'.$i] = $id;
			$names[] = ':i'.$i;
		}

		return implode(',', $names);
	}

	public static function adminFilters(array $raw): array
	{	$sorts = ['title', 'new', 'unused', 'used'];
		$domain = (string) ($raw['domain'] ?? '');

		return [
			'q' => mb_substr(trim((string) ($raw['q'] ?? '')), 0, 100),
			'domain' => ($domain === 'global' || ctype_digit($domain)) ? $domain : '',
			'author' => mb_substr(trim((string) ($raw['author'] ?? '')), 0, 100),
			'kind' => in_array((string) ($raw['kind'] ?? ''), self::KINDS, true) ? (string) $raw['kind'] : '',
			'vis' => in_array((string) ($raw['vis'] ?? ''), ['private', 'shared'], true) ? (string) $raw['vis'] : '',
			'sort' => in_array((string) ($raw['sort'] ?? ''), $sorts, true) ? (string) $raw['sort'] : 'title',
		];
	}

	public static function adminList(array $filters, int $page, int $perPage = 50): array
	{	self::requireAdmin();

		$where = ['1 = 1'];
		$params = [];
		if ($filters['q'] !== '') {
			$where[] = '`title` LIKE :q';
			$params['q'] = '%'.addcslashes($filters['q'], '%_\\').'%';
		}
		if ($filters['domain'] === 'global') {
			$where[] = '`domain_id` = 0';
		} elseif ($filters['domain'] !== '') {
			$where[] = '`domain_id` = :dom';
			$params['dom'] = (int) $filters['domain'];
		}
		if ($filters['author'] !== '') {
			$where[] = '`createuser` = :author';
			$params['author'] = $filters['author'];
		}
		if ($filters['kind'] !== '') {
			$where[] = '`kind` = :kind';
			$params['kind'] = $filters['kind'];
		}
		if ($filters['vis'] === 'private') {
			$where[] = '`private` = 1';
		} elseif ($filters['vis'] === 'shared') {
			$where[] = '`private` = 0';
		}

		$order = match ($filters['sort']) {
			'new' => '`id` DESC',
			'unused' => '`used_count` ASC, `title` ASC',
			'used' => '`used_count` DESC, `title` ASC',
			default => '`title` ASC, `id` ASC',
		};

		$sql = rex_sql::factory();
		$clause = implode(' AND ', $where);
		$total = (int) $sql->getArray('SELECT COUNT(*) AS `c` FROM '.self::table().' WHERE '.$clause, $params)[0]['c'];
		$perPage = max(10, min(200, $perPage));
		$pages = max(1, (int) ceil($total / $perPage));
		$page = max(1, min($pages, $page));
		$rows = $sql->getArray(
			'SELECT `id`, `title`, `kind`, `summary`, `domain_id`, `private`, `created_by`, `createuser`, `used_count`, `last_used`, `createdate` FROM '.self::table().' WHERE '.$clause.' ORDER BY '.$order.' LIMIT '.$perPage.' OFFSET '.(($page - 1) * $perPage),
			$params
		);

		return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
	}

	public static function adminAuthors(): array
	{	self::requireAdmin();
		$rows = rex_sql::factory()->getArray('SELECT DISTINCT `createuser` FROM '.self::table().' ORDER BY `createuser` ASC');

		return array_map(static fn (array $row): string => (string) $row['createuser'], $rows);
	}

	public static function existingLogins(): array
	{	$rows = rex_sql::factory()->getArray('SELECT `login` FROM '.rex::getTable('user'));

		return array_map(static fn (array $row): string => (string) $row['login'], $rows);
	}

	public static function adminDelete(array $ids): int
	{	self::requireAdmin();
		$ids = self::idList($ids);
		if ($ids === []) {
			return 0;
		}

		$params = [];
		$sql = rex_sql::factory();
		$sql->setQuery('DELETE FROM '.self::table().' WHERE `id` IN ('.self::inClause($ids, $params).')', $params);

		return $sql->getRows();
	}

	public static function adminRelease(array $ids): int
	{	self::requireAdmin();
		$ids = self::idList($ids);
		if ($ids === []) {
			return 0;
		}

		$params = ['now' => rex_sql::datetime(), 'login' => (string) rex::getUser()->getLogin()];
		$sql = rex_sql::factory();
		$sql->setQuery('UPDATE '.self::table().' SET `private` = 0, `updatedate` = :now, `updateuser` = :login WHERE `id` IN ('.self::inClause($ids, $params).')', $params);

		return $sql->getRows();
	}

	public static function adminAssignDomain(array $ids, string $domain): int
	{	self::requireAdmin();
		if (!self::multisiteActive()) {
			throw new Gridblock2PresetsException('multisite not active', 409);
		}

		if ($domain === 'all') {
			if (!self::crossDomainAllowed()) {
				throw new Gridblock2PresetsException('cross-domain favorites not enabled', 403);
			}
			$domainId = 0;
		} else {
			$domainId = ctype_digit($domain) ? (int) $domain : 0;
			if ($domainId <= 0 || !array_key_exists($domainId, Gridblock2Builder_MultiSite::getDomains())) {
				throw new Gridblock2PresetsException('unknown domain', 422);
			}
		}

		$ids = self::idList($ids);
		if ($ids === []) {
			return 0;
		}

		$params = ['dom' => $domainId, 'now' => rex_sql::datetime(), 'login' => (string) rex::getUser()->getLogin()];
		$sql = rex_sql::factory();
		$sql->setQuery('UPDATE '.self::table().' SET `domain_id` = :dom, `updatedate` = :now, `updateuser` = :login WHERE `id` IN ('.self::inClause($ids, $params).')', $params);

		return $sql->getRows();
	}

	public static function adminRename(int $id, string $title): void
	{	self::requireAdmin();
		if (self::find($id) === null) {
			throw new Gridblock2PresetsException('not found', 404);
		}

		$sql = rex_sql::factory();
		$sql->setTable(self::table());
		$sql->setWhere('`id` = :id', ['id' => $id]);
		$sql->setValue('title', self::normalizeTitle($title));
		$sql->setValue('updatedate', rex_sql::datetime());
		$sql->setValue('updateuser', (string) rex::getUser()->getLogin());
		$sql->update();
	}

	private static function isVisibleTo(array $row): bool
	{	if (self::isAdmin()) {
			return true;
		}

		return (int) $row['private'] === 0 || (int) $row['created_by'] === self::userId();
	}
}
