<?php
/*
	Redaxo-Addon Gridblock²
	Builder-Engine: Baum-Speicherung/-Rendering (Backend-Edit + Frontend-Ausgabe)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)

	Eine flache Builder-Wurzelliste ersetzt die
	alten Bootstrap-Tabs. Speicherung additiv in zwei bisher garantiert ungenutzten Value-Slots:

	- value16: die komplette Baumstruktur als EIN JSON-String (Reihenfolge/Verschachtelung/Typ/Settings/
	  Layout) - wird clientseitig von gridblock_builder.js gebaut (analog fm_builders data-builder-json)
	  und unverändert von REDAXOs Standard-Slice-Speicherung übernommen, keine serverseitige
	  Sonderbehandlung nötig.
	- value15: die tatsächlichen Modul-Feldwerte, flach je uID (REX_INPUT_VALUE[15][uID][VALUE][n] etc.) -
	  entspricht exakt dem bestehenden var_replacer/rex_sql_gridblock2-Mechanismus, nur mit einer FESTEN
	  "15" statt einer dynamischen colID (da uIDs bereits global eindeutig sind, ersetzt das die im
	- value14: die Contentsettings-Werte von Sektion-/Spalten-/Slot-Knoten, geschrieben über die

	Alle drei Slots waren im Legacy-Code (maxCols=12) nachweislich nie beschrieben - dadurch keine
	Kollisionsgefahr mit Bestandsdaten.

	WICHTIG zum Speichervorgang: value16 wird clientseitig NUR mit Strukturmetadaten befüllt (Reihenfolge/
	Typ/uid/Layout/id+status bei Modulen) - OHNE die eigentlichen Settings-Werte (die kommen wie bei
	Modul-Feldern nativ per POST über value14/value15, um Sync-Probleme mit Fremd-Widgets zu vermeiden,
	siehe die aus der popupmanager-Historie bekannte CKEditor-Problematik). boot.php führt vor dem
	eigentlichen Speichern per injectSettingsFromPost() beides zusammen, bevor value16 final geschrieben wird.
*/

class Gridblock2Builder_Tree
{
	const SLOT_STRUCTURE = 16;
	const SLOT_MODULES = 15;
	const SLOT_SETTINGS = 14;

	private static bool $suppressCsFieldHiding = false;


	/* ── Formaterkennung & Laden ─────────────────────────────────────────── */

	public static function loadSliceValues(int $sliceID): array
	{	$cols = [];
		for ($i = 1; $i <= 20; ++$i) {
			$cols[] = 'value'.$i;
		}

		$sql = rex_sql::factory();
		$sql->prepareQuery('SELECT '.implode(', ', $cols).' FROM '.rex::getTable('article_slice').' WHERE id = ?');
		$sql->execute([$sliceID]);

		$values = [];
		if ($sql->hasNext()) {
			for ($i = 1; $i <= 20; ++$i) {
				$values[$i] = $sql->getValue('value'.$i);
			}
		}
		return $values;
	}

	public static function hasNewFormat(array $sliceValues): bool
	{	return !empty($sliceValues[self::SLOT_STRUCTURE]);
	}

	public static function loadModuleValues(array $sliceValues): array
	{	if (self::hasNewFormat($sliceValues)) {
			$raw = $sliceValues[self::SLOT_MODULES] ?? '';
			if (empty($raw)) {
				return [];
			}
			$decoded = is_array($raw) ? $raw : rex_var::toArray($raw);
			return is_array($decoded) ? $decoded : [];
		}

		return self::loadLegacyModuleValues($sliceValues);
	}

	private static function loadLegacyModuleValues(array $sliceValues): array
	{	$moduleValues = [];
		for ($i = 1; $i <= 12; ++$i) {
			$raw = $sliceValues[$i] ?? '';
			if (empty($raw)) {
				continue;
			}
			$decoded = is_array($raw) ? $raw : rex_var::toArray($raw);
			if (is_array($decoded)) {
				$moduleValues += $decoded;
			}
		}
		return $moduleValues;
	}

	public static function loadStructure(array $sliceValues): array
	{	if (self::hasNewFormat($sliceValues)) {
			$raw = $sliceValues[self::SLOT_STRUCTURE];
			$decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
			return is_array($decoded) ? $decoded : [];
		}

		return self::buildVirtualTree($sliceValues);
	}


	/* ── Speichern: Settings aus dem value14-POST-Bucket in die clientseitig gebaute Struktur einfügen ── */

	public static function injectSettingsFromPost(array &$nodes, array $settingsPost): void
	{	foreach ($nodes as &$node) {
			$uid = (string) ($node['uid'] ?? '');
			$type = $node['type'] ?? 'module';

			$blockPost = $settingsPost[$uid]['modules'] ?? null;
			if (is_array($blockPost) && $blockPost !== []) {
				$node['blockSettings'] = $blockPost;
			} else {
				unset($node['blockSettings']);
			}

			if ($type === 'section') {
				$node['settings'] = $settingsPost[$uid]['section'] ?? [];
				if (isset($node['children']) && is_array($node['children'])) {
					self::injectSettingsFromPost($node['children'], $settingsPost);
				}
			} elseif ($type === 'columns') {
				$node['settings'] = $settingsPost[$uid]['template'] ?? [];

				$gapMapping = self::getGapCsMapping();
				if ($gapMapping !== null && $gapMapping['scope'] === 'template' && isset($node['layout']['gap'])) {
					$node['settings'][$gapMapping['field']] = $node['layout']['gap'];
				}

				if (isset($node['slots']) && is_array($node['slots'])) {
					foreach ($node['slots'] as $i => &$slot) {
						$slot['settings'] = $settingsPost[$uid]['column_'.($i + 1)] ?? [];
						if (isset($slot['children']) && is_array($slot['children'])) {
							self::injectSettingsFromPost($slot['children'], $settingsPost);
						}
					}
					unset($slot);
				}
			}
		}
		unset($node);
	}



	public static function buildVirtualTree(array $sliceValues): array
	{	$template = isset($sliceValues[17]) ? rex_var::toArray($sliceValues[17]) : [];
		$selTemplate = (int) ($template['selectedTemplate'] ?? 0);
		if ($selTemplate <= 0) {
			return [];
		}

		$modules = isset($sliceValues[19]) ? rex_var::toArray($sliceValues[19]) : [];
		$settings = $sliceValues[20] ?? '';

		$db = rex_sql::factory();
		$db->setQuery("SELECT columns FROM ".rex::getTable('2034_gridtemplates')." WHERE id = '".$selTemplate."'");
		$selColumns = ($db->getRows() > 0) ? (int) $db->getValue('columns') : 0;
		if ($selColumns <= 0) {
			return [];
		}

		$rawSettings = ($settings !== '') ? rex_var::toArray($settings) : [];

		$slots = [];
		for ($i = 1; $i <= $selColumns; ++$i):
			$children = [];
			$moduleIDs = $modules[$i] ?? [];

			foreach ($moduleIDs as $uID => $moduleRef):
				$uID = str_replace("'", "", (string) $uID);
				$moduleStatus = 1;

				if (!is_array($moduleRef)):
					$moduleID = (int) $moduleRef;			//alte Speicherart (1.0-beta)
				else:
					$moduleStatus = (int) ($moduleRef['status'] ?? 1);
					$moduleID = (int) ($moduleRef['id'] ?? 0);
				endif;

				if ($moduleID <= 0 || $uID === '') {
					continue;	//leerer, vorab angelegter Slot - kein Fehlerfall
				}

				$children[] = ['type' => 'module', 'uid' => $uID, 'id' => $moduleID, 'status' => $moduleStatus];
			endforeach;

			$slotSettingsObj = $rawSettings['column_'.$i] ?? null;
			$slots[] = ['settings' => self::objToArrayDeep($slotSettingsObj), 'children' => $children];
		endfor;

		$columnsSettingsArr = self::objToArrayDeep($rawSettings['template'] ?? null);

		return [[
			'type' => 'columns',
			'uid' => 'virtual_columns',
			'layout' => ['mode' => 'template', 'templateId' => $selTemplate, 'gap' => self::getDefaultGapValue()],
			'settings' => $columnsSettingsArr,
			'slots' => $slots,
		]];
	}

	private static function objToArrayDeep($value): array
	{	if ($value === null) {
			return [];
		}
		return json_decode(json_encode($value), true) ?: [];
	}


	/* ── Tiefenbegrenzung ─────────────────────────────────────────────────── */

	public static function getMaxDepth(): int
	{	$max = (int) (rex_gridblock2::getConfig('gb_builder_maxdepth') ?: 3);
		return ($max > 0) ? $max : 3;
	}

	public static function isDepthAllowed(int $depth): bool
	{	return $depth < self::getMaxDepth();
	}

	public static function validateTreeSecurity(array $nodes, array $previousModules = [], int $depth = 0): void
	{	foreach ($nodes as $node) {
			$type = $node['type'] ?? 'module';

			if ($type === 'module') {
				$moduleID = (int) ($node['id'] ?? 0);
				$uID = (string) ($node['uid'] ?? '');
				$wasAlreadyPresent = ($uID !== '' && isset($previousModules[$uID]) && $previousModules[$uID] === $moduleID);
				if ($moduleID > 0 && !$wasAlreadyPresent && isset($_SESSION['gridAllowedModules']) && !isset($_SESSION['gridAllowedModules'][$moduleID])) {
					throw new rex_exception('Module '.$moduleID.' not allowed in gridblock');
				}
				$user = rex::getUser();
				if ($moduleID > 0 && !$wasAlreadyPresent && $user !== null) {
					$modulePerm = $user->getComplexPerm('modules');
					if ($modulePerm !== null ? !$modulePerm->hasPerm($moduleID) : !$user->isAdmin()) {
						throw new rex_exception('No permission for module '.$moduleID);
					}
				}
				continue;
			}

			if (!self::isDepthAllowed($depth)) {
				throw new rex_exception('max nesting depth exceeded');
			}

			if ($type === 'section') {
				self::validateTreeSecurity((array) ($node['children'] ?? []), $previousModules, $depth + 1);
			} elseif ($type === 'columns') {
				foreach ((array) ($node['slots'] ?? []) as $slot) {
					self::validateTreeSecurity((array) ($slot['children'] ?? []), $previousModules, $depth + 1);
				}
			}
		}
	}

	//Sammelt alle UIDs eines (bereits gespeicherten) Baums - Bestandsschutz fuer validateTreeSchema()
	public static function collectAllUids(array $nodes, array &$uids): void
	{	foreach ($nodes as $node) {
			if (!is_array($node)) {
				continue;
			}
			if (isset($node['uid']) && is_string($node['uid'])) {
				$uids[$node['uid']] = true;
			}
			self::collectAllUids((array) ($node['children'] ?? []), $uids);
			foreach ((array) ($node['slots'] ?? []) as $slot) {
				self::collectAllUids((array) (is_array($slot) ? ($slot['children'] ?? []) : []), $uids);
			}
		}
	}

	public static function validateTreeSchema(array $nodes, array $previousUids = []): void
	{	$seen = [];
		$count = 0;
		self::validateTreeSchemaNodes($nodes, $previousUids, $seen, $count);
	}

	private static function validateTreeSchemaNodes(array $nodes, array $previousUids, array &$seen, int &$count): void
	{	foreach ($nodes as $node) {
			if (!is_array($node)) {
				throw new rex_exception('invalid tree node');
			}
			if (++$count > 500) {
				throw new rex_exception('too many tree nodes');
			}

			$type = $node['type'] ?? 'module';
			if (!in_array($type, ['module', 'section', 'columns', 'element'], true)) {
				throw new rex_exception('unknown tree node type');
			}

			$uid = $node['uid'] ?? '';
			if (!is_string($uid) || $uid === '') {
				throw new rex_exception('tree node without uid');
			}
			if (!isset($previousUids[$uid])) {
				if (!rex_article_content_gridblock2::isValidUID($uid)) {
					throw new rex_exception('invalid tree node uid');
				}
				if (isset($seen[$uid])) {
					throw new rex_exception('duplicate tree node uid');
				}
			}
			$seen[$uid] = true;

			if ($type === 'module') {
				if ((int) ($node['id'] ?? 0) <= 0) {
					throw new rex_exception('invalid module id in tree');
				}
			} elseif ($type === 'element') {
				if (!is_string($node['elementType'] ?? null) || !preg_match('/^[A-Za-z0-9_-]+$/', $node['elementType'])) {
					throw new rex_exception('invalid element type in tree');
				}
			} elseif ($type === 'section') {
				if (isset($node['children']) && !is_array($node['children'])) {
					throw new rex_exception('invalid section children');
				}
				self::validateTreeSchemaNodes((array) ($node['children'] ?? []), $previousUids, $seen, $count);
			} else {
				if (isset($node['slots']) && !is_array($node['slots'])) {
					throw new rex_exception('invalid column slots');
				}
				foreach ((array) ($node['slots'] ?? []) as $slot) {
					if (!is_array($slot) || (isset($slot['children']) && !is_array($slot['children']))) {
						throw new rex_exception('invalid column slot');
					}
					self::validateTreeSchemaNodes((array) ($slot['children'] ?? []), $previousUids, $seen, $count);
				}
			}
		}
	}

	public static function collectModuleUidMap(array $nodes, array &$map): void
	{	foreach ($nodes as $node) {
			$type = $node['type'] ?? 'module';

			if ($type === 'module') {
				$uID = (string) ($node['uid'] ?? '');
				if ($uID !== '') {
					$map[$uID] = (int) ($node['id'] ?? 0);
				}
				continue;
			}

			if ($type === 'section') {
				self::collectModuleUidMap((array) ($node['children'] ?? []), $map);
			} elseif ($type === 'columns') {
				foreach ((array) ($node['slots'] ?? []) as $slot) {
					self::collectModuleUidMap((array) ($slot['children'] ?? []), $map);
				}
			}
		}
	}


	public static function getNodeAvailabilityKey(array $node): string
	{	$type = $node['type'] ?? 'module';

		if ($type === 'module') {
			return Gridblock2Builder_Availability::moduleKey((int) ($node['id'] ?? 0));
		}
		if ($type === 'element') {
			return Gridblock2Builder_Availability::customKey((string) ($node['elementType'] ?? ''));
		}
		if ($type === 'section') {
			return Gridblock2Builder_Availability::sectionKey();
		}

		return '';
	}

	public static function collectAvailabilityKeyMap(array $nodes, array &$map): void
	{	foreach ($nodes as $node) {
			if (!is_array($node)) {
				continue;
			}

			$uID = (string) ($node['uid'] ?? '');
			$key = self::getNodeAvailabilityKey($node);
			if ($uID !== '' && $key !== '') {
				$map[$uID] = $key;
			}

			self::collectAvailabilityKeyMap((array) ($node['children'] ?? []), $map);
			foreach ((array) ($node['slots'] ?? []) as $slot) {
				self::collectAvailabilityKeyMap((array) (is_array($slot) ? ($slot['children'] ?? []) : []), $map);
			}
		}
	}

	public static function validateTreeAvailability(array $nodes, array $previousKeys, int $articleId, int $clangId, int $ctypeId): void
	{	foreach ($nodes as $node) {
			if (!is_array($node)) {
				continue;
			}

			$type = $node['type'] ?? 'module';
			$uID = (string) ($node['uid'] ?? '');
			$key = self::getNodeAvailabilityKey($node);
			$wasAlreadyPresent = ($key !== '' && $uID !== '' && ($previousKeys[$uID] ?? null) === $key);

			if ($key !== '' && !$wasAlreadyPresent) {
				if ($type === 'module') {
					$moduleID = (int) ($node['id'] ?? 0);
					$rexVars = ['artID' => $articleId, 'clangID' => $clangId, 'ctypeID' => $ctypeId];
					if (!self::isModuleAllowedByTemplate($moduleID, $rexVars)) {
						throw new rex_exception('Module '.$moduleID.' not allowed in template');
					}
				} elseif ($type === 'element') {
					$elementType = (string) ($node['elementType'] ?? '');
					if (!self::isCustomElementTypeAddable($elementType, false)) {
						throw new rex_exception('unknown custom element type');
					}
					if (!self::canUseCustomElement($elementType)) {
						throw new rex_exception('No permission for element '.$elementType);
					}
				} elseif ($type === 'section') {
					if (!self::canUseSection()) {
						throw new rex_exception('No permission for section element');
					}
				}

				if (!Gridblock2Builder_Availability::isAllowedForArticle($key, $articleId, $clangId)) {
					throw new rex_exception('Element '.$key.' not available for this domain');
				}
			}

			self::validateTreeAvailability((array) ($node['children'] ?? []), $previousKeys, $articleId, $clangId, $ctypeId);
			foreach ((array) ($node['slots'] ?? []) as $slot) {
				self::validateTreeAvailability((array) (is_array($slot) ? ($slot['children'] ?? []) : []), $previousKeys, $articleId, $clangId, $ctypeId);
			}
		}
	}

	private static function isArticleAccessible(int $articleId, int $clangId): bool
	{	$user = rex::getUser();
		if ($user === null || $articleId <= 0) {
			return false;
		}
		if ($user->isAdmin()) {
			return true;
		}

		$articleSql = rex_sql::factory();
		$articleSql->setQuery('SELECT id, parent_id, startarticle FROM '.rex::getTable('article').' WHERE id = ? AND clang_id = ?', [$articleId, $clangId]);
		if ($articleSql->getRows() < 1) {
			return false;
		}
		$categoryId = ((int) $articleSql->getValue('startarticle') === 1) ? (int) $articleSql->getValue('id') : (int) $articleSql->getValue('parent_id');
		return $user->getComplexPerm('structure')->hasCategoryPerm($categoryId);
	}

	public static function isModulePlacedInSlice(int $sliceId, string $uid, int $moduleId): bool
	{	if ($sliceId <= 0 || $uid === '' || $moduleId <= 0) {
			return false;
		}

		$sql = rex_sql::factory();
		$sql->setQuery('SELECT * FROM '.rex::getTable('article_slice').' WHERE id = ?', [$sliceId]);
		if ($sql->getRows() < 1) {
			return false;
		}

		if (!self::isArticleAccessible((int) $sql->getValue('article_id'), (int) $sql->getValue('clang_id'))) {
			return false;
		}

		$sliceValues = [];
		for ($i = 1; $i <= 20; ++$i) {
			$sliceValues[$i] = $sql->getValue('value'.$i);
		}

		$map = [];
		self::collectModuleUidMap(self::loadStructure($sliceValues), $map);

		return isset($map[$uid]) && $map[$uid] === $moduleId;
	}

	public static function isDemoVars(array $rexVars): bool
	{	return !empty($rexVars['demo']);
	}

	private static ?array $templateCheckVars = null;
	private static array $availCheckVars = [];
	private static array $templateAttributesCache = [];

	public static function applyRequestContext(array $rexVars): array
	{	$art = rex_request::get('gbart', 'int', 0);
		if ($art > 0) {
			$rexVars['artID'] = $art;
			$rexVars['clangID'] = rex_request::get('gbclang', 'int', 0) ?: (int) ($rexVars['clangID'] ?? 0);
			$ctype = rex_request::get('gbctype', 'int', 0);
			if ($ctype > 0) {
				$rexVars['ctypeID'] = $ctype;
			}
		}

		return $rexVars;
	}

	public static function isModuleAllowedByTemplate(int $moduleId, array $rexVars): bool
	{	if (self::isDemoVars($rexVars)) {
			return true;
		}

		$articleId = (int) ($rexVars['artID'] ?? 0);
		if ($moduleId <= 0 || $articleId <= 0) {
			return true;
		}

		$article = rex_article::get($articleId, ((int) ($rexVars['clangID'] ?? 0)) ?: null);
		$templateId = $article ? (int) $article->getTemplateId() : 0;
		if ($templateId <= 0) {
			return true;
		}

		if (!array_key_exists($templateId, self::$templateAttributesCache)) {
			$sql = rex_sql::factory();
			$sql->setQuery('SELECT attributes FROM '.rex::getTable('template').' WHERE id = ?', [$templateId]);
			$attributes = ($sql->getRows() > 0) ? json_decode((string) $sql->getValue('attributes'), true) : [];
			self::$templateAttributesCache[$templateId] = is_array($attributes) ? $attributes : [];
		}

		return rex_template::hasModule(self::$templateAttributesCache[$templateId], max(1, (int) ($rexVars['ctypeID'] ?? 1)), $moduleId);
	}

	public static function buildCopiedSubtree(string $type, string $raw, int $depth, bool $isDemoContext, array $rexVars = []): array
	{	self::$templateCheckVars = $isDemoContext ? null : $rexVars;
		self::$availCheckVars = $rexVars;
	$payload = json_decode($raw, true);
		$tree = is_array($payload) ? ($payload['tree'] ?? null) : null;
		$pairs = is_array($payload) ? ($payload['fields'] ?? null) : null;
		if (!is_array($tree) || !is_array($pairs) || ($tree['type'] ?? '') !== $type) {
			throw new rex_functional_exception('invalid copy payload');
		}

		try {
			self::validateTreeSecurity([$tree], [], $depth);
		} catch (rex_exception $e) {
			throw new rex_functional_exception($e->getMessage());
		}

		$fields = [self::SLOT_SETTINGS => [], self::SLOT_MODULES => []];
		foreach ($pairs as $pair) {
			if (!is_array($pair) || !isset($pair[0], $pair[1]) || !is_string($pair[0]) || !is_scalar($pair[1])) {
				continue;
			}
			if (!preg_match('/^REX_INPUT_VALUE\[('.self::SLOT_SETTINGS.'|'.self::SLOT_MODULES.')\]\[([A-Za-z0-9_-]{1,128})\]((?:\[[^\[\]]*\]){0,8})$/', $pair[0], $m)) {
				continue;
			}
			preg_match_all('/\[([^\[\]]*)\]/', $m[3], $keys);
			self::assignNestedField($fields[(int) $m[1]][$m[2]], $keys[1], (string) $pair[1]);
		}

		$moduleValues = [];
		$settingsPost = [];
		$nodes = [self::remapCopiedNode($tree, $fields, $moduleValues, $settingsPost, $isDemoContext)];
		self::injectSettingsFromPost($nodes, $settingsPost);

		return [$nodes[0], $moduleValues];
	}

	private static function assignNestedField(&$target, array $keys, string $value): void
	{	foreach ($keys as $key) {
			if (!is_array($target)) {
				$target = [];
			}
			if ($key === '') {
				$target[] = null;
				$key = array_key_last($target);
			} elseif (!array_key_exists($key, $target)) {
				$target[$key] = null;
			}
			$target = &$target[$key];
		}
		$target = $value;
	}

	private static function remapCopiedNode(array $node, array $fields, array &$moduleValues, array &$settingsPost, bool $isDemoContext): array
	{	$oldUid = (string) ($node['uid'] ?? '');
		if (!rex_article_content_gridblock2::isValidUID($oldUid)) {
			throw new rex_functional_exception('invalid uid in copy payload');
		}
		$newUid = rex_article_content_gridblock2::createUID();
		$status = ((int) ($node['status'] ?? 1) === 0) ? 0 : 1;

		switch ($node['type'] ?? '') {
			case 'module':
				if (self::$templateCheckVars !== null && !self::isModuleAllowedByTemplate((int) ($node['id'] ?? 0), self::$templateCheckVars)) {
					throw new rex_functional_exception('module not allowed in template');
				}
				if (!Gridblock2Builder_Availability::isAllowed(Gridblock2Builder_Availability::moduleKey((int) ($node['id'] ?? 0)), self::$availCheckVars)) {
					throw new rex_functional_exception('module not available for this domain');
				}
				$moduleValues[$newUid] = (array) ($fields[self::SLOT_MODULES][$oldUid] ?? []);
				$settingsPost[$newUid] = (array) ($fields[self::SLOT_SETTINGS][$oldUid] ?? []);
				return ['type' => 'module', 'uid' => $newUid, 'id' => (int) ($node['id'] ?? 0), 'status' => $status];

			case 'element':
				$elementType = (string) ($node['elementType'] ?? '');
				if ($elementType === '' || !self::isCustomElementTypeAddable($elementType, $isDemoContext)) {
					throw new rex_functional_exception('unknown custom element type');
				}
				if (!self::canUseCustomElement($elementType, $isDemoContext)) {
					throw new rex_functional_exception('no permission for this element');
				}
				if (!Gridblock2Builder_Availability::isAllowed(Gridblock2Builder_Availability::customKey($elementType), self::$availCheckVars)) {
					throw new rex_functional_exception('element not available for this domain');
				}
				$settingsPost[$newUid] = (array) ($fields[self::SLOT_SETTINGS][$oldUid] ?? []);
				return ['type' => 'element', 'uid' => $newUid, 'elementType' => $elementType, 'status' => $status, 'values' => (array) ($node['values'] ?? [])];

			case 'section':
				if (!self::canUseSection($isDemoContext)) {
					throw new rex_functional_exception('no permission for this element');
				}
				if (!Gridblock2Builder_Availability::isAllowed(Gridblock2Builder_Availability::sectionKey(), self::$availCheckVars)) {
					throw new rex_functional_exception('element not available for this domain');
				}
				$settingsPost[$newUid] = (array) ($fields[self::SLOT_SETTINGS][$oldUid] ?? []);
				return ['type' => 'section', 'uid' => $newUid, 'settings' => [], 'children' => self::remapCopiedChildren((array) ($node['children'] ?? []), $fields, $moduleValues, $settingsPost, $isDemoContext)];

			case 'columns':
				$settingsPost[$newUid] = (array) ($fields[self::SLOT_SETTINGS][$oldUid] ?? []);
				$slots = [];
				foreach ((array) ($node['slots'] ?? []) as $slot) {
					$slots[] = ['settings' => [], 'children' => self::remapCopiedChildren((array) (is_array($slot) ? ($slot['children'] ?? []) : []), $fields, $moduleValues, $settingsPost, $isDemoContext)];
				}
				return ['type' => 'columns', 'uid' => $newUid, 'layout' => (array) ($node['layout'] ?? []), 'settings' => [], 'slots' => $slots];
		}

		throw new rex_functional_exception('unknown node type in copy payload');
	}

	private static function remapCopiedChildren(array $children, array $fields, array &$moduleValues, array &$settingsPost, bool $isDemoContext): array
	{	$result = [];
		foreach ($children as $child) {
			if (is_array($child)) {
				$result[] = self::remapCopiedNode($child, $fields, $moduleValues, $settingsPost, $isDemoContext);
			}
		}
		return $result;
	}


	/* ── Backend-Edit-Rendering (Karten) ────────────────────────────────── */

	//Wurzel-Fragment: eine .gb-builder-list (depth 0) mit allen Top-Level-Karten + Add-Menü
	public static function renderRoot(array $structure, array $moduleValues, array $rexVars): string
	{	$fragment = new rex_fragment();
		$fragment->setVar('nodes', $structure, false);
		$fragment->setVar('moduleValues', $moduleValues, false);
		$fragment->setVar('rexVars', $rexVars, false);
		$fragment->setVar('depth', 0, false);
		$fragment->setVar('maxDepth', self::getMaxDepth(), false);
		$fragment->setVar('instanceUid', 'gbb'.substr(sha1((string) ($rexVars['sliceID'] ?? uniqid())), 0, 8), false);
		return $fragment->parse('gridblock2/builder/root.php');
	}

	public static function renderList(array $nodes, array $moduleValues, array $rexVars, int $depth): string
	{	$op = '';
		foreach ($nodes as $node) {
			$op .= self::renderNode($node, $moduleValues, $rexVars, $depth);
		}
		return $op;
	}

	public static function renderNode(array $node, array $moduleValues, array $rexVars, int $depth): string
	{	switch ($node['type'] ?? 'module') {
			case 'section':
				return self::renderSectionCard($node, $moduleValues, $rexVars, $depth);
			case 'columns':
				return self::renderColumnsCard($node, $moduleValues, $rexVars, $depth);
			case 'element':
				return self::renderElementCard($node, $depth, self::isDemoVars($rexVars), $rexVars);
			default:
				return self::renderModuleCard($node, $moduleValues, $rexVars, $depth);
		}
	}

	private static function renderElementCard(array $node, int $depth, bool $isDemoContext = false, array $rexVars = []): string
	{	$uID = (string) ($node['uid'] ?? '');
		$elementType = (string) ($node['elementType'] ?? '');
		$status = (int) ($node['status'] ?? 1);
		$values = (array) ($node['values'] ?? []);
		if ($uID === '' || $elementType === '') {
			return '';
		}

		$types = self::discoverCustomElementTypes();
		$unavailable = !isset($types[$elementType]);
		$unmet = self::$unmetRequirements[$elementType] ?? null;
		if ($unavailable) {
			$label = $unmet['label'] ?? $elementType;
			$formHtml = '';
			foreach ($values as $name => $value) {
				if (is_scalar($value) || $value === null) {
					$formHtml .= '<input type="hidden" name="'.rex_escape((string) $name).'" value="'.rex_escape((string) $value).'">';
				}
			}
		} else {
			$label = $types[$elementType]['label'];
			rex_gridblock2::isBuilderEdit(true);
			$formHtml = self::renderElementFile('custom/'.$elementType, 'input.php', ['uid' => $uID, 'values' => $values, 'addon' => rex_addon::get('gridblock2')]);
		}

		$blockSettings = (array) ($node['blockSettings'] ?? []);
		$locked = !$unavailable && (!self::canUseCustomElement($elementType, $isDemoContext) || Gridblock2Builder_Availability::isLockedForUser(Gridblock2Builder_Availability::customKey($elementType), $rexVars));

		$fragment = new rex_fragment();
		$fragment->setVar('type', 'element', true);
		$fragment->setVar('locked', $locked, false);
		$fragment->setVar('settingsFormHtml', ($unavailable || $locked) ? '' : self::renderBlockSettingsForm($uID, $blockSettings, ['kind' => 'custom', 'ids' => [$elementType]]), false);
		$fragment->setVar('settingsKeepHtml', ($unavailable || $locked) ? self::renderBlockSettingsKeep($uID, $blockSettings) : '', false);
		$fragment->setVar('uid', $uID, true);
		$fragment->setVar('label', $label, true);
		$fragment->setVar('status', $status, false);
		$fragment->setVar('depth', $depth, false);
		$fragment->setVar('moduleId', 0, false);
		$fragment->setVar('elementType', $elementType, false);
		$fragment->setVar('layout', [], false);
		$fragment->setVar('gapCssValue', '', false);
		$fragment->setVar('formHtml', $formHtml, false);
		$fragment->setVar('childrenHtml', '', false);
		$fragment->setVar('quickswitchEnabled', false, false);
		$fragment->setVar('quickswitchMenu', '', false);
		$fragment->setVar('editable', !$unavailable && !$locked, false);
		$unavailableText = '';
		if ($unavailable) {
			$unavailableText = ($unmet !== null)
				? rex_i18n::rawMsg('a2034_builder_element_requires_addon', $label, implode(', ', $unmet['addons']))
				: rex_i18n::rawMsg('a2034_builder_element_unavailable', $elementType);
		}
		$fragment->setVar('unavailableText', $unavailableText, false);
		return $fragment->parse('gridblock2/builder/block.php');
	}

	private static array $customElementTypesCache = [];
	private static array $unmetRequirements = [];

	public static function discoverCustomElementTypes(): array
	{	if (self::$customElementTypesCache !== []) {
			return self::$customElementTypesCache;
		}

		$types = [];
		$base = rex_addon::get('gridblock2')->getDataPath('elements/custom');
		foreach (glob($base.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
			$meta = self::readCustomElementMeta($dir, basename($dir));
			if ($meta !== null) {
				$types[basename($dir)] = $meta;
			}
		}

		foreach (rex_addon::getAvailableAddons() as $addon) {
			if ($addon->getName() === 'gridblock2') {
				continue;
			}
			$addonBase = $addon->getPath('gridblock2/elements');
			if (!is_dir($addonBase)) {
				continue;
			}
			foreach (glob($addonBase.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
				$key = $addon->getName().'.'.basename($dir);
				if (isset($types[$key]) || !self::isValidCustomElementKey($key)) {
					continue;
				}
				$meta = self::readCustomElementMeta($dir, $key);
				if ($meta !== null) {
					$types[$key] = $meta;
				}
			}
		}

		uasort($types, static fn (array $a, array $b) => strcasecmp($a['label'], $b['label']));

		self::$customElementTypesCache = $types;
		return $types;
	}

	//"requires" in element.yml (String oder Liste von Addon-Namen): das Element gilt nur als verfuegbar, wenn ALLE genannten Addons installiert und aktiv sind.
	private static function findMissingAddons(array $meta): array
	{	$required = $meta['requires'] ?? [];
		$required = is_array($required) ? $required : [$required];
		$missing = [];
		foreach ($required as $name) {
			$name = trim((string) $name);
			if ($name === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
				continue;
			}
			if (!rex_addon::exists($name) || !rex_addon::get($name)->isAvailable()) {
				$missing[] = $name;
			}
		}
		return $missing;
	}

	private static function isValidCustomElementKey(string $key): bool
	{	return (bool) preg_match('/^[A-Za-z0-9_-]+(\.[A-Za-z0-9_-]+)?$/', $key);
	}

	private static function readCustomElementMeta(string $dir, string $key): ?array
	{	if (!is_file($dir.'/element.yml') || !is_file($dir.'/input.php') || !is_file($dir.'/output.php')) {
			return null;
		}

		$meta = rex_file::getConfig($dir.'/element.yml');
		$missingAddons = self::findMissingAddons((array) $meta);
		if ($missingAddons !== []) {
			self::$unmetRequirements[$key] = ['label' => (string) ($meta['label'] ?? $key), 'addons' => $missingAddons];
			return null;
		}

		$demoOnly = (bool) ($meta['demoOnly'] ?? false);
		return [
			'label'    => (string) ($meta['label'] ?? $key),
			'icon'     => $demoOnly ? 'rex-icon fa-flask' : (string) ($meta['icon'] ?? 'rex-icon fa-cube'),
			'group'    => trim((string) ($meta['group'] ?? 'special')),
			'description' => trim((string) ($meta['description'] ?? '')),
			'preview'  => in_array(($meta['preview'] ?? ''), ['iframe', 'shadowdom'], true) ? $meta['preview'] : '',
			'previewCss' => array_values(array_filter((array) ($meta['preview_css'] ?? []), 'is_string')),
			'demoOnly' => $demoOnly,
			'permission' => (bool) ($meta['permission'] ?? false),
			'enabled'  => ((string) ($meta['status'] ?? 'online')) !== 'offline',
		];
	}

	public static function discoverAddableCustomElementTypes(bool $isDemoContext): array
	{	$types = array_filter(self::discoverCustomElementTypes(), static fn (array $meta) => $meta['enabled']);
		return $isDemoContext ? $types : array_filter($types, static fn (array $meta) => !$meta['demoOnly']);
	}

	public static function isCustomElementTypeAllowed(string $type): bool
	{	return isset(self::discoverCustomElementTypes()[$type]);
	}

	public static function isCustomElementTypeAddable(string $type, bool $isDemoContext): bool
	{	return isset(self::discoverAddableCustomElementTypes($isDemoContext)[$type]);
	}

	public static function renderRepeater(string $name, string|array|null $existing, string $markup, array $options = []): string
	{	if (self::isFmHelperToolActive('htmlrepeater')):
			return \IcemanFx\FmHelper\fmHelper_repeater::render($name, $existing, $markup, $options);
		endif;

		return Gridblock2Builder_VendorRepeater::render($name, $existing, $markup, $options);
	}

	public static function makeRepeaterUid(): string
	{	if (self::isFmHelperToolActive('htmlrepeater')):
			return \IcemanFx\FmHelper\fmHelper_repeater::makeUid();
		endif;

		return Gridblock2Builder_VendorRepeater::makeUid();
	}

	public static function renderMultilink(string $name, string|array|null $existing, array $options = []): string
	{	if (self::isFmHelperToolActive('multilink')):
			return \IcemanFx\FmHelper\fmHelper_multilink::render($name, $existing, $options);
		endif;

		return Gridblock2Builder_VendorMultilink::render($name, $existing, $options);
	}

	public static function isFmHelperToolActive(string $configKey): bool
	{	if (!rex_addon::get('_fm_helper')->isAvailable()):
			return false;
		endif;

		$config = (array) rex_addon::get('_fm_helper')->getConfig('config');
		$defaultChecked = in_array($configKey, ['multilink', 'multideviceselect'], true);
		$value = $config[$configKey] ?? ($defaultChecked ? 'checked' : '');
		return $value === 'checked';
	}

	public static function getCustomElementPreviewCssLinks(string $elementType): array
	{	$entries = self::discoverCustomElementTypes()[$elementType]['previewCss'] ?? [];
		$links = [];
		foreach ($entries as $entry) {
			if (!preg_match('/^addon:([A-Za-z0-9_-]+)\/([A-Za-z0-9_\-.\/]+)$/', $entry, $m) || str_contains($m[2], '..')) {
				continue;
			}
			$file = rex_path::addonAssets($m[1], $m[2]);
			if (!is_file($file)) {
				continue;
			}
			$links[] = rex_url::addonAssets($m[1], $m[2]).'?v='.filemtime($file);
		}

		return $links;
	}

	public static function getCustomElementPreviewCss(string $elementType): string
	{	if (!self::isCustomElementTypeAllowed($elementType)) {
			return '';
		}

		$dir = self::resolveElementDir('custom/'.$elementType);
		if ($dir === null || !is_file($dir.'/style.css')) {
			return '';
		}

		return rex_file::get($dir.'/style.css') ?? '';
	}

	private static function getSystemElementMeta(string $key, string $fallbackLabel, string $fallbackIcon): array
	{	$dir = self::resolveElementDir('system/'.$key);
		$meta = ($dir !== null && is_file($dir.'/element.yml')) ? rex_file::getConfig($dir.'/element.yml') : [];

		return [
			'label'   => (string) ($meta['label'] ?? $fallbackLabel),
			'icon'    => (string) ($meta['icon'] ?? $fallbackIcon),
			'description' => trim((string) ($meta['description'] ?? '')),
			'enabled' => ((string) ($meta['status'] ?? 'online')) !== 'offline' && self::findMissingAddons((array) $meta) === [],
			'permission' => (bool) ($meta['permission'] ?? false),
		];
	}

	public const ELEMENT_PERM_KEY = 'gridblock2_elements';

	private static function isElementPermitted(string $permKey): bool
	{	$user = rex::getUser();
		if ($user === null) {
			return true;
		}

		$perm = $user->getComplexPerm(self::ELEMENT_PERM_KEY);
		return $perm === null || $perm->hasPerm($permKey); // @phpstan-ignore method.notFound
	}

	public static function canUseCustomElement(string $type, bool $isDemoContext = false): bool
	{	if ($isDemoContext) {
			return true;
		}

		$meta = self::discoverCustomElementTypes()[$type] ?? null;
		return $meta === null || !$meta['permission'] || self::isElementPermitted('custom/'.$type);
	}

	public static function canUseSection(bool $isDemoContext = false): bool
	{	return $isDemoContext || !self::getSectionElementMeta()['permission'] || self::isElementPermitted('system/section');
	}

	public static function getPermissionElementOptions(): array
	{	$options = [];

		if (self::getSectionElementMeta()['permission']) {
			$options['system/section'] = self::getSectionElementMeta()['label'].' – '.rex_i18n::msg('a2034_perm_system_element');
		}

		foreach (self::discoverCustomElementTypes() as $key => $meta) {
			if (!$meta['permission']) {
				continue;
			}
			$suffix = str_contains($key, '.') ? ' ('.explode('.', $key, 2)[0].')' : '';
			$options['custom/'.$key] = $meta['label'].$suffix;
		}

		return $options;
	}

	public static function getSectionElementMeta(): array
	{	return self::getSystemElementMeta('section', 'Sektion', 'rex-icon fa-object-group');
	}

	public static function getColumnsElementMeta(): array
	{	return self::getSystemElementMeta('column', 'Spalten', 'rex-icon fa-columns');
	}

	private static function renderModuleCard(array $node, array $moduleValues, array $rexVars, int $depth): string
	{	$uID = (string) ($node['uid'] ?? '');
		$moduleID = (int) ($node['id'] ?? 0);
		$status = (int) ($node['status'] ?? 1);
		if ($uID === '' || $moduleID <= 0) {
			return '';
		}

		$MOD = rex_sql::factory();
		$MOD->setQuery('SELECT name, `key` FROM '.rex::getTablePrefix().'module WHERE id="'.$moduleID.'"');
		$label = ($MOD->getRows() == 1) ? (string) $MOD->getValue('name') : '[ID: '.$moduleID.']';
		$moduleKey = ($MOD->getRows() == 1) ? (string) $MOD->getValue('key') : '';

		rex_gridblock2::isBuilderEdit(true);
		$editor = new rex_article_content_gridblock2();
		$editor->setValues($moduleValues[$uID] ?? [], $uID);
		$formHtml = $editor->getModuleEdit($moduleID, self::SLOT_MODULES, $uID, $rexVars);

		$editable = !isset($_SESSION['gridAllowedModules']) || isset($_SESSION['gridAllowedModules'][$moduleID]);

		$locked = !rex::getUser()->getComplexPerm('modules')->hasPerm($moduleID) || Gridblock2Builder_Availability::isLockedForUser(Gridblock2Builder_Availability::moduleKey($moduleID), $rexVars);
		$templateLocked = !self::isModuleAllowedByTemplate($moduleID, $rexVars);
		$editable = $editable && !$locked && !$templateLocked;

		$blockSettings = (array) ($node['blockSettings'] ?? []);

		$fragment = new rex_fragment();
		$fragment->setVar('type', 'module', true);
		$fragment->setVar('locked', $locked, false);
		$fragment->setVar('templateLocked', $templateLocked, false);
		$fragment->setVar('settingsFormHtml', ($locked || $templateLocked) ? '' : self::renderBlockSettingsForm($uID, $blockSettings, ['kind' => 'redaxo', 'ids' => [(string) $moduleID, $moduleKey]]), false);
		$fragment->setVar('settingsKeepHtml', ($locked || $templateLocked) ? self::renderBlockSettingsKeep($uID, $blockSettings) : '', false);
		$fragment->setVar('uid', $uID, true);
		$fragment->setVar('label', $label, true);
		$fragment->setVar('status', $status, false);
		$fragment->setVar('depth', $depth, false);
		$fragment->setVar('moduleId', $moduleID, false);
		$fragment->setVar('layout', [], false);
		$fragment->setVar('gapCssValue', '', false);
		$fragment->setVar('formHtml', $formHtml, false);
		$fragment->setVar('childrenHtml', '', false);
		$fragment->setVar('quickswitchEnabled', false, false);
		$fragment->setVar('quickswitchMenu', '', false);
		$fragment->setVar('editable', $editable, false);
		return $fragment->parse('gridblock2/builder/block.php');
	}

	private static function renderSectionCard(array $node, array $moduleValues, array $rexVars, int $depth): string
	{	$uID = (string) ($node['uid'] ?? '');
		$children = (array) ($node['children'] ?? []);

		$childrenHtml = self::renderChildList($children, $moduleValues, $rexVars, $depth + 1);

		$blockSettings = (array) ($node['blockSettings'] ?? []);
		$formHtml = self::combineSettingsForms(
			self::renderSettingsForm($uID, 0, $node['settings'] ?? [], 'section'),
			self::renderBlockSettingsForm($uID, $blockSettings, ['kind' => 'system', 'ids' => ['section']])
		);

		$fragment = new rex_fragment();
		$fragment->setVar('locked', !self::canUseSection(self::isDemoVars($rexVars)) || Gridblock2Builder_Availability::isLockedForUser(Gridblock2Builder_Availability::sectionKey(), $rexVars), false);
		$fragment->setVar('type', 'section', true);
		$fragment->setVar('uid', $uID, true);
		$fragment->setVar('label', rex_i18n::msg('a2034_builder_section'), true);
		$fragment->setVar('status', (int) ($node['status'] ?? 1), false);
		$fragment->setVar('depth', $depth, false);
		$fragment->setVar('moduleId', 0, false);
		$fragment->setVar('layout', [], false);
		$fragment->setVar('gapCssValue', '', false);
		$fragment->setVar('formHtml', $formHtml, false);
		$fragment->setVar('childrenHtml', $childrenHtml, false);
		$fragment->setVar('quickswitchEnabled', false, false);
		$fragment->setVar('quickswitchMenu', '', false);
		return $fragment->parse('gridblock2/builder/block.php');
	}

	private static function renderColumnsCard(array $node, array $moduleValues, array $rexVars, int $depth): string
	{	$node = self::resolveColumnsNode($node);
		$uID = (string) ($node['uid'] ?? '');
		$layout = (array) ($node['layout'] ?? ['mode' => 'ratio', 'columns' => [50, 50], 'gap' => self::getDefaultGapValue()]);
		$slots = (array) ($node['slots'] ?? []);

		$slotsHtml = '';
		foreach ($slots as $i => $slot) {
			$slotChildren = (array) ($slot['children'] ?? []);
			$slotListHtml = self::renderChildList($slotChildren, $moduleValues, $rexVars, $depth + 1);

			$slotFragment = new rex_fragment();
			$slotFragment->setVar('index', $i, false);
			$slotFragment->setVar('depth', $depth + 1, false);
			$slotFragment->setVar('listHtml', $slotListHtml, false);
			$slotFragment->setVar('settingsFormHtml', self::renderSettingsForm($uID, $i + 1, $slot['settings'] ?? []), false);
			$slotsHtml .= $slotFragment->parse('gridblock2/builder/columnslot.php');
		}

		$fragment = new rex_fragment();
		$fragment->setVar('type', 'columns', true);
		$fragment->setVar('uid', $uID, true);
		$fragment->setVar('label', rex_i18n::msg('a2034_builder_columns'), true);
		$fragment->setVar('status', (int) ($node['status'] ?? 1), false);
		$fragment->setVar('depth', $depth, false);
		$fragment->setVar('moduleId', 0, false);
		$fragment->setVar('layout', $layout, false);
		$gapOptions = self::getGapOptions();
		$fragment->setVar('gapCssValue', $gapOptions[$layout['gap'] ?? 'md']['value'] ?? '', false);
		$csFormHtml = self::renderSettingsForm($uID, 0, $node['settings'] ?? [], 'template');
		$compact = ($csFormHtml === '');
		$blockSettings = (array) ($node['blockSettings'] ?? []);
		$blockFormHtml = self::renderBlockSettingsForm($uID, $blockSettings, ['kind' => 'system', 'ids' => ['column']]);
		$fragment->setVar('formHtml', self::renderColumnsRatioForm($layout, $compact, $uID).self::combineSettingsForms($csFormHtml, $blockFormHtml), false);
		$fragment->setVar('childrenHtml', $slotsHtml, false);
		$quickswitchEnabled = (rex_gridblock2::getConfig('gb_builder_columns_quickswitch') === 'checked');
		$fragment->setVar('quickswitchEnabled', $quickswitchEnabled, false);
		$fragment->setVar('quickswitchMenu', $quickswitchEnabled ? self::renderColumnsQuickswitchMenu($layout) : '', false);
		return $fragment->parse('gridblock2/builder/block.php');
	}

	public static function getRatioPresets(): array
	{	$presets = [];

		$db = rex_sql::factory();
		$db->setQuery("SELECT id, title, columns, preview, cssclass FROM ".rex::getTable('2034_gridtemplates')." WHERE status = 'checked' ORDER BY prio ASC, columns ASC, title ASC");
		foreach ($db as $row) {
			$preset = self::buildRatioPreset($row);
			if ($preset !== null) {
				$presets[$preset['id']] = $preset;
			}
		}

		return $presets;
	}

	//Eine einzelne Spaltenvorlage als Preset (Schluessel im Katalog = Vorlagen-ID).
	private static function buildRatioPreset(rex_sql $row): ?array
	{	$columns = (int) $row->getValue('columns');
		$widths = [];
		$preview = rex_var::toArray((string) $row->getValue('preview'));
		$cols = is_array($preview) ? ($preview['columns'] ?? null) : null;
		if (is_array($cols)) {
			foreach ($cols as $c) {
				$w = (int) ($c['width'] ?? 0);
				if ($w > 0) {
					$widths[] = $w;
				}
			}
		}
		if ($columns < 1) {
			$columns = count($widths);
		}
		if ($columns < 1) {
			return null;
		}

		$equal = round(100 / $columns, 2);
		while (count($widths) < $columns) {
			$widths[] = $equal;
		}
		$widths = array_slice($widths, 0, $columns);

		$id = (int) $row->getValue('id');
		$title = trim((string) $row->getValue('title'));
		return ['id' => $id, 'widths' => $widths, 'label' => ($title !== '') ? $title : (string) $id, 'cssClass' => (string) $row->getValue('cssclass'), 'columns' => $columns];
	}

	private static array $ratioTemplateCache = [];

	public static function getRatioTemplate(int $templateId): ?array
	{	if ($templateId <= 0) {
			return null;
		}
		if (!array_key_exists($templateId, self::$ratioTemplateCache)) {
			$db = rex_sql::factory();
			$db->setQuery("SELECT id, title, columns, preview, cssclass FROM ".rex::getTable('2034_gridtemplates')." WHERE id = ".$templateId);
			self::$ratioTemplateCache[$templateId] = ($db->getRows() > 0) ? self::buildRatioPreset($db) : null;
		}
		return self::$ratioTemplateCache[$templateId];
	}

	public static function resolveColumnsNode(array $node): array
	{	$layout = (array) ($node['layout'] ?? []);
		if (($layout['mode'] ?? 'ratio') !== 'ratio') {
			return $node;
		}

		$preset = self::getRatioTemplate((int) ($layout['templateId'] ?? 0));
		if ($preset === null) {
			$key = implode('-', array_map(static fn ($w) => (int) round((float) $w), array_values((array) ($layout['columns'] ?? []))));
			foreach (self::getRatioPresets() as $candidate) {
				if (implode('-', array_map(static fn ($w) => (int) round((float) $w), $candidate['widths'])) === $key) {
					$preset = $candidate;
					break;
				}
			}
		}
		if ($preset === null) {
			return $node;
		}

		$layout['templateId'] = $preset['id'];
		$layout['columns'] = $preset['widths'];
		$node['layout'] = $layout;
		$node['resolvedClass'] = $preset['cssClass'];

		$slots = array_values((array) ($node['slots'] ?? []));
		$target = $preset['columns'];
		while (count($slots) < $target) {
			$slots[] = ['settings' => [], 'children' => []];
		}
		if (count($slots) > $target) {
			$extra = array_splice($slots, $target);
			$children = (array) ($slots[$target - 1]['children'] ?? []);
			foreach ($extra as $extraSlot) {
				$children = array_merge($children, (array) ($extraSlot['children'] ?? []));
			}
			$slots[$target - 1]['children'] = $children;
		}
		$node['slots'] = $slots;

		return $node;
	}

	public static function getLegacyTemplateOptions(): array
	{	$options = [];

		$db = rex_sql::factory();
		$db->setQuery("SELECT id, title, columns, preview FROM ".rex::getTable('2034_gridtemplates')." ORDER BY prio ASC, columns ASC, title ASC");
		foreach ($db as $row) {
			$columns = (int) $row->getValue('columns');

			$preview = rex_var::toArray((string) $row->getValue('preview'));
			$cols = is_array($preview) ? ($preview['columns'] ?? null) : null;
			$widths = is_array($cols) ? array_map(static function ($c) { return (int) ($c['width'] ?? 0); }, $cols) : [];
			$widths = array_values(array_filter($widths, static function ($w) { return $w > 0; }));
			if (!$widths && $columns > 0) {
				$widths = array_fill(0, $columns, round(100 / $columns, 2));
			}

			$options[(int) $row->getValue('id')] = [
				'label' => (string) $row->getValue('title'),
				'columns' => $columns,
				'widths' => $widths,
			];
		}

		return $options;
	}

	//Ob ueberhaupt eine Spaltenaufteilung waehlbar ist - true nur, wenn mindestens eine freigeschaltete Spaltenvorlage existiert.
	public static function isColumnsUsable(): bool
	{	return self::getRatioPresets() !== [];
	}

	public static function getGapMode(): string
	{	$mode = (string) (rex_gridblock2::getConfig('gb_builder_gap_mode') ?? '');
		if ($mode === 'native' || $mode === 'cs') {
			return $mode;
		}
		return ((string) (rex_gridblock2::getConfig('gb_builder_gap_cs_field') ?? '') === '') ? 'native' : 'cs';
	}

	private static function getGapCsMapping(): ?array
	{	if (self::getGapMode() !== 'cs') {
			return null;
		}
		$raw = (string) (rex_gridblock2::getConfig('gb_builder_gap_cs_field') ?? '');
		if ($raw === '' || strpos($raw, '|') === false) {
			return null;
		}
		[$scope, $field] = array_map('trim', explode('|', $raw, 2));
		return ($scope !== '' && $field !== '') ? ['scope' => $scope, 'field' => $field] : null;
	}

	public static function getMappedCsFieldKeys(string $scope): array
	{	if (self::$suppressCsFieldHiding) {
			return [];
		}
		$mapping = self::getGapCsMapping();
		return ($mapping !== null && $mapping['scope'] === $scope) ? [$mapping['field']] : [];
	}

	public static function getCsFieldChoices(): array
	{	$choices = ['template' => [], 'columns' => [], 'section' => []];
		self::$suppressCsFieldHiding = true;
		foreach (array_keys($choices) as $scope) {
			$gcs = new Gridblock2Builder_ContentSettings();
			$gcs->getAllSettings($scope);
			foreach ((array) ($gcs->aSettings['showOptions'] ?? []) as $key) {
				$option = $gcs->aSettings['options'][$key] ?? null;
				$type = (string) ($option['type'] ?? '');
				if ($option === null || $type === '' || $type === 'html' || $type === 'group') {
					continue;
				}
				$choices[$scope][$key] = (string) ($option['label'] ?? $key);
			}
			asort($choices[$scope]);
		}
		self::$suppressCsFieldHiding = false;
		return $choices;
	}

	//Optionsliste fürs native Spalten-Gap-Dropdown.
	private static function getGapOptions(): array
	{	$mapping = self::getGapCsMapping();
		if ($mapping !== null) {
			$gcs = new Gridblock2Builder_ContentSettings();
			$gcs->getAllSettings($mapping['scope']);
			$data = (array) ($gcs->aSettings['options'][$mapping['field']]['data'] ?? []);
			if ($data) {
				$options = [];
				foreach ($data as $value => $label) {
					$options[(string) $value] = ['label' => (string) $label, 'value' => ''];
				}
				return $options;
			}
		}

		if (self::getGapMode() === 'cs') {
			return [];
		}

		$lines = preg_split('/\r\n|\r|\n/', (string) (rex_gridblock2::getConfig('gb_builder_gap_native_fallback') ?? '')) ?: [];
		$options = [];
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || substr_count($line, '=') < 2) {
				continue;
			}
			[$key, $label, $value] = array_map('trim', explode('=', $line, 3));
			$options[$key] = ['label' => (rex_i18n::hasMsg($label) ? rex_i18n::msg($label) : $label), 'value' => $value];
		}

		return $options;
	}

	public static function getDefaultGapValue(): string
	{	$mapping = self::getGapCsMapping();
		if ($mapping !== null) {
			$gcs = new Gridblock2Builder_ContentSettings();
			$gcs->getAllSettings($mapping['scope']);
			$option = $gcs->aSettings['options'][$mapping['field']] ?? null;
			$default = $option['default'] ?? null;
			if (is_string($default) && $default !== '' && $default !== 'gridblockcontentsettingsdefault') {
				return $default;
			}
			$data = (array) ($option['data'] ?? []);
			return $data ? (string) array_key_first($data) : '';
		}

		$options = self::getGapOptions();
		return $options ? (string) array_key_first($options) : '';
	}


	public static function buildNativeGridCss(): string
	{
		$css = ".gridblock{--gb-col-width:100%;--gb-row-gap:0px;--gb-col-count:1;width:100%}\n"
			.".gridblock .row{display:flex;flex-wrap:wrap;gap:var(--gb-row-gap);width:100%}\n"
			.".gridblock .row>*{min-width:0;flex:0 0 100%}\n";

		foreach (self::getGapOptions() as $key => $option) {
			$value = trim((string) ($option['value'] ?? ''));
			if ($key === '' || $value === '' || !preg_match('/^[A-Za-z0-9_-]+$/', (string) $key) || preg_match('/[{}<>]/', $value)) {
				continue;
			}
			$css .= '.gridblock .row.gb-el-columns-gap-'.$key.'{gap:'.$value.';--gb-row-gap:'.$value.'}'."\n";
		}

		$css .= '@media (min-width: 768px){'.self::buildBreakpointDeclaration().'}'."\n";

		foreach (self::getSystemElementCssFiles() as $file) {
			$style = trim((string) file_get_contents($file));
			if ($style !== '') {
				$css .= "\n".$style."\n";
			}
		}

		return $css;
	}

	//style.css der System-Elemente (Spalten, Sektion), die an die generierte Grid-CSS angehaengt werden
	public static function getSystemElementCssFiles(): array
	{	$files = [];
		foreach (['system/column', 'system/section'] as $typeDir) {
			$dir = self::resolveElementDir($typeDir);
			if ($dir !== null && is_file($dir.'/style.css')) {
				$files[] = $dir.'/style.css';
			}
		}

		return $files;
	}

	//true, wenn die generierte Grid-CSS fehlt oder aelter als eine style.css der System-Elemente ist
	public static function nativeGridCssIsOutdated(): bool
	{	$path = rex_addon::get('gridblock2')->getAssetsPath(self::NATIVE_GRID_CSS_ASSET_PATH);
		if (!is_file($path)) {
			return true;
		}

		$generated = (int) filemtime($path);
		foreach (self::getSystemElementCssFiles() as $file) {
			if ((int) filemtime($file) > $generated) {
				return true;
			}
		}

		return false;
	}

	public static function buildBreakpointDeclaration(): string
	{	$colBasis = 'calc(var(--gb-col-width) - (var(--gb-row-gap) * (var(--gb-col-count) - 1) / var(--gb-col-count)))';
		return '.gridblock .row{flex-wrap:nowrap}.gridblock .row>*{--gb-col-basis:'.$colBasis.';flex:0 1 var(--gb-col-basis)}';
	}

	public static function getPreviewContainerQueryCss(): string
	{	return ':host{container-type:inline-size}'
			.'@container gbvp (min-width: 768px){'.self::buildBreakpointDeclaration().'}'
			.'@container gbvp (max-width: 767.98px){.gridblock .row{flex-wrap:wrap}.gridblock .row>*{flex:0 0 100%}}';
	}

	public const NATIVE_GRID_CSS_ASSET_PATH = 'css/gb-native-grid.generated.css';

	public static function writeNativeGridCssFile(): bool
	{	$path = rex_addon::get('gridblock2')->getAssetsPath(self::NATIVE_GRID_CSS_ASSET_PATH);
		return rex_file::put($path, self::buildNativeGridCss());
	}

	public static function getNativeGridCssUrl(): string
	{	$absolutePath = rex_addon::get('gridblock2')->getAssetsPath(self::NATIVE_GRID_CSS_ASSET_PATH);
		return '/'.str_replace(DIRECTORY_SEPARATOR, '/', rex_path::relative($absolutePath));
	}

	public const ELEMENTS_CSS_ASSET_PATH = 'css/gb-elements.generated.css';

	public static function buildElementsCss(): string
	{	$css = '';
		foreach (array_keys(self::discoverCustomElementTypes()) as $type) {
			$style = trim(self::getCustomElementPreviewCss((string) $type));
			if ($style !== '') {
				$css .= '/* '.$type.' */'."\n".$style."\n\n";
			}
		}

		return $css;
	}

	public static function writeElementsCssFile(): bool
	{	$path = rex_addon::get('gridblock2')->getAssetsPath(self::ELEMENTS_CSS_ASSET_PATH);
		return rex_file::put($path, self::buildElementsCss());
	}

	//true, wenn die generierte Datei fehlt oder aelter als eine der style.css-Dateien ist
	public static function elementsCssIsOutdated(): bool
	{	$path = rex_addon::get('gridblock2')->getAssetsPath(self::ELEMENTS_CSS_ASSET_PATH);
		if (!is_file($path)) {
			return true;
		}

		$generated = (int) filemtime($path);
		foreach (array_keys(self::discoverCustomElementTypes()) as $type) {
			$dir = self::resolveElementDir('custom/'.$type);
			if ($dir !== null && is_file($dir.'/style.css') && (int) filemtime($dir.'/style.css') > $generated) {
				return true;
			}
		}

		return false;
	}

	//URL der gesammelten Element-CSS (inkl. Cache-Buster) fuer Templates: als href eines link-Tags mit rel stylesheet ausgeben schreibt die Datei bei Bedarf neu
	public static function getElementsCssUrl(): string
	{	if (self::elementsCssIsOutdated()) {
			self::writeElementsCssFile();
		}

		$absolutePath = rex_addon::get('gridblock2')->getAssetsPath(self::ELEMENTS_CSS_ASSET_PATH);
		$url = '/'.str_replace(DIRECTORY_SEPARATOR, '/', rex_path::relative($absolutePath));

		return $url.(is_file($absolutePath) ? '?v='.filemtime($absolutePath) : '');
	}

	private static function renderColumnsQuickswitchMenu(array $layout): string
	{	if (($layout['mode'] ?? 'ratio') !== 'ratio') {
			return '';
		}

		$currentWidths = array_values((array) ($layout['columns'] ?? [50, 50]));
		$currentCount = count($currentWidths);
		if ($currentCount < 2) {
			return '';
		}
		$currentId = (int) ($layout['templateId'] ?? 0);

		$ratios = self::getRatioPresets();
		$op = '';
		foreach ($ratios as $key => $preset) {
			if ((int) $preset['id'] === $currentId || $preset['columns'] !== $currentCount) {
				continue;
			}
			$op .= '<li role="presentation"><a href="javascript:;" role="menuitem" data-ratio="'.rex_escape((string) $preset['id']).'" title="'.rex_escape($preset['label']).'"><span class="gb-ratio-preview-inline">';
			foreach ($preset['widths'] as $w) {
				$op .= '<span style="flex-grow: '.(float) $w.';"></span>';
			}
			$op .= '</span><span class="gb-builder-columns-quickswitch-menu-label">'.rex_escape($preset['label']).'</span></a></li>';
		}

		return $op;
	}

	private static function renderColumnsRatioForm(array $layout, bool $compact = false, string $uID = ''): string
	{	$currentGap = (string) ($layout['gap'] ?? 'md');
		$op = '<div class="gb-builder-columns-settings-row">';

		$buildPreview = static function (array $widths, string $label): string {
			$bars = '<span class="gb-ratio-preview-inline">';
			foreach ($widths as $w) {
				$bars .= '<span style="flex-grow: '.(float) $w.';"></span>';
			}
			$bars .= '</span> '.rex_escape($label);
			return $bars;
		};

		$buildRadioGroup = static function (string $name, string $inputClass, array $options, string $currentValue, bool $hasCurrentValue, string $fallbackLabel = ''): string {
			$op = '<div class="gb-builder-radiobtn-group">';
			foreach ($options as $value => $optionHtml) {
				$checked = ((string) $value === $currentValue) ? ' checked' : '';
				$op .= '<label class="btn btn-default"><input type="radio" class="'.rex_escape($inputClass).'" name="'.rex_escape($name).'" value="'.rex_escape((string) $value).'" autocomplete="off"'.$checked.'> '.$optionHtml.'</label>';
			}
			if (!$hasCurrentValue && $fallbackLabel !== '') {
				$op .= '<label class="btn btn-default"><input type="radio" class="'.rex_escape($inputClass).'" name="'.rex_escape($name).'" value="'.rex_escape($currentValue).'" autocomplete="off" checked> '.rex_escape($fallbackLabel).'</label>';
			}
			$op .= '</div>';
			return $op;
		};

		if (($layout['mode'] ?? 'ratio') === 'ratio') {
			$ratios = self::getRatioPresets();
			$currentColumns = array_values((array) ($layout['columns'] ?? [50, 50]));
			$currentRatioKey = (string) ((int) ($layout['templateId'] ?? 0));
			$hasCurrentKey = isset($ratios[(int) $currentRatioKey]);
			$fallbackLabel = count($currentColumns).' '.rex_i18n::msg('a2034_builder_columns').' ('.implode(' / ', array_map(static function ($w) { return $w.'%'; }, $currentColumns)).')';

			$op .= '<dl class="rex-form-group form-group rex-form-group-vertical"><dt>'.rex_i18n::msg('a2034_builder_columns_ratio').'</dt><dd>';
			if ($compact) {
				$options = [];
				foreach ($ratios as $key => $preset) {
					$options[(string) $preset['id']] = $buildPreview($preset['widths'], $preset['label']);
				}
				$op .= $buildRadioGroup('gb-builder-columns-ratio-radio-'.$uID, 'gb-builder-columns-ratio-radio-input', $options, $currentRatioKey, $hasCurrentKey, $fallbackLabel);
				$op .= '<input type="hidden" class="gb-builder-columns-ratio-select" value="'.rex_escape($currentRatioKey).'">';
			} else {
				$op .= '<select class="gb-builder-columns-ratio-select selectpicker form-control" data-live-search="true" data-width="100%">';
				foreach ($ratios as $key => $preset) {
					$sel = ((string) $preset['id'] === $currentRatioKey) ? ' selected="selected"' : '';
					$content = $buildPreview($preset['widths'], $preset['label']);
					$op .= '<option value="'.rex_escape((string) $preset['id']).'"'.$sel.' data-content="'.rex_escape($content).'">'.rex_escape($preset['label']).'</option>';
				}
				//aktuelle Aufteilung in keinem Preset enthalten?
				if (!$hasCurrentKey) {
					$content = $buildPreview($currentColumns, $fallbackLabel);
					$op .= '<option value="'.rex_escape($currentRatioKey).'" selected="selected" data-content="'.rex_escape($content).'">'.rex_escape($fallbackLabel).'</option>';
				}
				$op .= '</select>';
			}
			$op .= '</dd></dl>';
		}

		if (($layout['mode'] ?? 'ratio') === 'template') {
			$templates = self::getLegacyTemplateOptions();
			$currentTemplateId = (int) ($layout['templateId'] ?? 0);
			$hasCurrentTemplate = isset($templates[$currentTemplateId]);
			$fallbackLabel = rex_i18n::msg('a2034_builder_columns').' (ID: '.$currentTemplateId.')';

			$op .= '<dl class="rex-form-group form-group rex-form-group-vertical"><dt>'.rex_i18n::msg('a2034_builder_columns_ratio').'</dt><dd>';
			if ($compact) {
				$options = [];
				foreach ($templates as $id => $template) {
					$options[$id] = $buildPreview($template['widths'], $template['label']);
				}
				$op .= $buildRadioGroup('gb-builder-columns-template-radio-'.$uID, 'gb-builder-columns-template-radio-input', $options, (string) $currentTemplateId, $hasCurrentTemplate, ($currentTemplateId > 0) ? $fallbackLabel : '');
				$op .= '<input type="hidden" class="gb-builder-columns-template-select" value="'.$currentTemplateId.'">';
			} else {
				$op .= '<select class="gb-builder-columns-template-select selectpicker form-control" data-live-search="true" data-width="100%">';
				foreach ($templates as $id => $template) {
					$sel = ($id === $currentTemplateId) ? ' selected="selected"' : '';
					$content = $buildPreview($template['widths'], $template['label']);
					$op .= '<option value="'.$id.'"'.$sel.' data-content="'.rex_escape($content).'">'.rex_escape($template['label']).'</option>';
				}
				//aktuelle Vorlage inzwischen geloescht?
				if (!$hasCurrentTemplate && $currentTemplateId > 0) {
					$op .= '<option value="'.$currentTemplateId.'" selected="selected">'.rex_escape($fallbackLabel).'</option>';
				}
				$op .= '</select>';
			}
			$op .= '</dd></dl>';
		}

		$gapOptions = self::getGapOptions();
		$hasCurrentGapKey = isset($gapOptions[$currentGap]);

		$op .= '<dl class="rex-form-group form-group rex-form-group-vertical"><dt>'.rex_i18n::msg('a2034_builder_columns_gap').'</dt><dd>';
		if ($compact) {
			$options = [];
			foreach ($gapOptions as $key => $option) {
				$options[$key] = rex_escape($option['label']);
			}
			$op .= $buildRadioGroup('gb-builder-columns-gap-radio-'.$uID, 'gb-builder-columns-gap-radio-input', $options, $currentGap, $hasCurrentGapKey, $hasCurrentGapKey ? '' : $currentGap);
			$op .= '<input type="hidden" class="gb-builder-columns-gap-select" value="'.rex_escape($currentGap).'">';
			foreach ($gapOptions as $key => $option) {
				$op .= '<input type="hidden" class="gb-builder-columns-gap-value" data-gapkey="'.rex_escape($key).'" data-gapvalue="'.rex_escape($option['value']).'">';
			}
		} else {
			$op .= '<select class="gb-builder-columns-gap-select form-control">';
			foreach ($gapOptions as $key => $option) {
				$sel = ($key === $currentGap) ? ' selected="selected"' : '';
				$op .= '<option value="'.rex_escape($key).'" data-gapvalue="'.rex_escape($option['value']).'"'.$sel.'>'.rex_escape($option['label']).'</option>';
			}
			//aktueller Gap-Key in keinen Optionen enthalten?
			if (!$hasCurrentGapKey) {
				$op .= '<option value="'.rex_escape($currentGap).'" selected="selected">'.rex_escape($currentGap).'</option>';
			}
			$op .= '</select>';
		}
		$op .= '</dd></dl>';
		$op .= '</div>';

		return $op;
	}

	//Rendert EINEN einzelnen, leeren Spalten-Slot inkl. korrekt indiziertem Settings-Formular - genutzt beim nachträglichen Erweitern der Spaltenzahl.
	public static function renderBlankColumnSlot(string $uid, int $slotIndex, int $childDepth): string
	{	$rexVars = $_SESSION['gridRexVars'] ?? [];
		$slotFragment = new rex_fragment();
		$slotFragment->setVar('index', $slotIndex, false);
		$slotFragment->setVar('depth', $childDepth, false);
		$slotFragment->setVar('listHtml', self::renderChildList([], [], $rexVars, $childDepth), false);
		$slotFragment->setVar('settingsFormHtml', self::renderSettingsForm($uid, $slotIndex + 1, []), false);
		return $slotFragment->parse('gridblock2/builder/columnslot.php');
	}

	private static function renderChildList(array $nodes, array $moduleValues, array $rexVars, int $depth): string
	{	$fragment = new rex_fragment();
		$fragment->setVar('nodes', $nodes, false);
		$fragment->setVar('moduleValues', $moduleValues, false);
		$fragment->setVar('rexVars', $rexVars, false);
		$fragment->setVar('depth', $depth, false);
		$fragment->setVar('maxDepth', self::getMaxDepth(), false);
		return $fragment->parse('gridblock2/builder/list.php');
	}

	private static function renderSettingsForm(string $nodeUid, int $scopeSlot, array $currentValues, string $rootScope = 'template', ?array $modulesContext = null): string
	{
		if (!rex_addon::get('gridblock2')->isAvailable() || $nodeUid === '') {
			return '';
		}

		$sType = $scopeSlot ? 'column_'.$scopeSlot : $rootScope;

		$oSettings = new Gridblock2ContentSettings;
		$oSettings->iSettingsId = self::SLOT_SETTINGS.']['.$nodeUid;
		$oSettings->modulesContext = $modulesContext;
		return (string) ($oSettings->getGridblock2ContentSettingsForm([$sType => $currentValues], 0, $scopeSlot, $rootScope) ?? '');
	}

	public static function renderBlockSettingsForm(string $nodeUid, array $currentValues, array $context): string
	{	if (!Gridblock2Builder_ContentSettings::hasModulesScope()) {
			return '';
		}

		return self::renderSettingsForm($nodeUid, 0, $currentValues, 'modules', $context);
	}

	private static function combineSettingsForms(string $mainHtml, string $blockHtml): string
	{	if ($blockHtml === '') {
			return $mainHtml;
		}
		if ($mainHtml === '') {
			return $blockHtml;
		}

		return $mainHtml.'<div class="gb-builder-block-settings-extra"><div class="gb-builder-block-settings-extra-title">'.rex_i18n::msg('a2034_builder_block_settings').'</div>'.$blockHtml.'</div>';
	}

	private static function renderBlockSettingsKeep(string $nodeUid, array $blockSettings): string
	{	$html = '';
		$walk = static function (array $values, string $prefix) use (&$walk, &$html): void {
			foreach ($values as $key => $value) {
				$name = $prefix.'['.$key.']';
				if (is_array($value)) {
					$walk($value, $name);
				} elseif (is_scalar($value)) {
					$html .= '<input type="hidden" name="'.rex_escape($name).'" value="'.rex_escape((string) $value).'">';
				}
			}
		};
		$walk($blockSettings, 'REX_INPUT_VALUE['.self::SLOT_SETTINGS.']['.$nodeUid.'][modules]');

		return $html;
	}

	public static function buildBlockSettingsObject(array $raw, array $context): object
	{	$gcs = new Gridblock2Builder_ContentSettings();
		$gcs->modulesContext = $context;
		$gcs->getAllSettings('modules');

		return self::resolveContentSettingsScope($gcs, $raw, 'modules');
	}

	public static function attachBlockSettings(object $cs, array $raw, array $context): void
	{	if (Gridblock2Builder_ContentSettings::hasModulesScope()) {
			$cs->modules = self::buildBlockSettingsObject($raw, $context);
		}
	}


	/* ── Frontend-Ausgabe (rekursiv) ─────────────────────────────────────── */

	public static function renderOutput(array $structure, array $moduleValues, array $rexVars, bool $publicView = false, bool $hasGridblockAncestor = false): string
	{	Gridblock2Builder_Preview::setIframeContext($publicView);
		$op = '';
		foreach ($structure as $node) {
			$op .= self::renderNodeOutput($node, $moduleValues, $rexVars, $publicView);
		}

		if (rex::isBackend() && !$publicView && !$hasGridblockAncestor) {
			return '<div class="gb-slicelist-output">'.$op.'</div>';
		}

		return $op;
	}

	private static function renderNodeOutput(array $node, array $moduleValues, array $rexVars, bool $publicView = false): string
	{
		switch ($node['type'] ?? 'module') {
			case 'section':
				if (!(int) ($node['status'] ?? 1) && ($publicView || !rex::isBackend())) {
					return '';
				}
				$children = (array) ($node['children'] ?? []);
				//section.php liefert immer ein eigenes .gridblock - Kinder erben den Kontext daher IMMER als true, unabhaengig davon, ob diese Sektion selbst einen geerbt hatte.
				$inner = self::renderOutput($children, $moduleValues, $rexVars, $publicView, true);
				return self::renderSection((array) ($node['settings'] ?? []), $inner, 'gb-el-section', (array) ($node['blockSettings'] ?? []));

			case 'columns':
				if (!(int) ($node['status'] ?? 1) && ($publicView || !rex::isBackend())) {
					return '';
				}
				return self::renderColumnsOutput($node, $moduleValues, $rexVars, $publicView);

			case 'element':
				$elementType = (string) ($node['elementType'] ?? '');
				$status = (int) ($node['status'] ?? 1);
				$hideOffline = $publicView || !rex::isBackend();
				$html = self::renderCustomElementOutput($node, $publicView);

				if (rex::isBackend() && !$publicView && $elementType !== '' && !(!$status && $hideOffline)) {
					$types = self::discoverCustomElementTypes();
					$label = (string) ($types[$elementType]['label'] ?? $elementType);
					return self::wrapBackendListPanel($html, $label, $status);
				}

				return $html;

			default:
				return self::renderModuleOutput($node, $moduleValues, $rexVars, $publicView);
		}
	}

	public static function renderCustomElementOutput(array $node, bool $publicView = false): string
	{	$elementType = (string) ($node['elementType'] ?? '');
		$status = (int) ($node['status'] ?? 1);
		$hideOffline = $publicView || !rex::isBackend();
		if ($elementType === '' || (!$status && $hideOffline)) {
			return '';
		}

		self::discoverCustomElementTypes();
		if (isset(self::$unmetRequirements[$elementType])) {
			return '';
		}

		$values = (array) ($node['values'] ?? []);
		$cs = new stdClass();
		self::attachBlockSettings($cs, (array) ($node['blockSettings'] ?? []), ['kind' => 'custom', 'ids' => [$elementType]]);
		return self::renderElementFile('custom/'.$elementType, 'output.php', ['values' => $values, 'addon' => rex_addon::get('gridblock2'), 'cs' => $cs]);
	}

	private static function renderModuleOutput(array $node, array $moduleValues, array $rexVars, bool $publicView = false): string
	{	$uID = (string) ($node['uid'] ?? '');
		$moduleID = (int) ($node['id'] ?? 0);
		$status = (int) ($node['status'] ?? 1);
		$hideOffline = $publicView || !rex::isBackend();
		if ($uID === '' || $moduleID <= 0 || (!$status && $hideOffline)) {
			return '';
		}

		$editor = new rex_article_content_gridblock2();
		$editor->setValues($moduleValues[$uID] ?? [], $uID);
		$modOP = $editor->getModuleOutput($moduleID, $uID, $rexVars, (array) ($node['blockSettings'] ?? []));

		if (rex::isBackend() && !$publicView) {
			$moduleName = '';
			$db = rex_sql::factory();
			$db->setQuery('SELECT name FROM '.rex::getTable('module').' WHERE id = "'.$moduleID.'"');
			if ($db->getRows() > 0) {
				$moduleName = $db->getValue('name');
			}

			return self::wrapBackendListPanel($modOP, $moduleName, $status);
		}

		return $modOP;
	}

	private static function wrapBackendListPanel(string $html, string $label, int $status): string
	{	$css = (!$status) ? 'gridblock-panel-offline' : '';

		$wrapped = '<div class="gridblock-panel '.$css.'">';
		$wrapped .= '<header class="gridblock-panel-header">'.$label;
		$wrapped .= '<i class="gridblock-panel-offline-icon rex-icon fa-eye-slash rex-offline"></i>';
		$wrapped .= '</header>';
		$wrapped .= '<div class="gridblock-panel-body">'.$html.'</div>';
		$wrapped .= '</div>';

		return $wrapped;
	}

	private static function renderColumnsOutput(array $node, array $moduleValues, array $rexVars, bool $publicView = false): string
	{	$node = self::resolveColumnsNode($node);
		$layout = (array) ($node['layout'] ?? ['mode' => 'ratio', 'columns' => [50, 50], 'gap' => self::getDefaultGapValue()]);
		$slots = (array) ($node['slots'] ?? []);

		$templateId = (($layout['mode'] ?? 'ratio') === 'template') ? (int) ($layout['templateId'] ?? 0) : 0;
		$templateMeta = ($templateId > 0) ? self::loadGridTemplateMeta($templateId) : null;

		$slotHtml = [];
		foreach ($slots as $i => $slot) {
			$slotRexVars = $rexVars;
			$slotRexVars['grid_colNR'] = $i + 1;
			if ($templateMeta !== null) {
				$slotRexVars['grid_tmplID'] = $templateId;
				$slotRexVars['grid_tmplPREV'] = $templateMeta['preview'];
				$slotRexVars['grid_tmplCOLS'] = $templateMeta['columns'];
			}
			$slotHtml[$i + 1] = self::renderOutput((array) ($slot['children'] ?? []), $moduleValues, $slotRexVars, $publicView, true);
		}

		if ($templateMeta !== null) {
			$templateSettings = (array) ($node['settings'] ?? []);
			$contentsettings = self::buildLegacyContentSettings($templateSettings, $slots, $templateId);
			return self::renderLegacyTemplateWrapper($templateId, $slotHtml, $rexVars, $contentsettings, $templateMeta);
		}

		$widths = (array) ($layout['columns'] ?? []);
		$gap = (string) ($layout['gap'] ?? 'md');
		$templateSettings = (array) ($node['settings'] ?? []);
		$columnSettingsList = array_map(static fn (array $slot) => (array) ($slot['settings'] ?? []), $slots);
		$cs = self::buildContentSettingsObject($templateSettings, $columnSettingsList, 'template');
		self::attachBlockSettings($cs, (array) ($node['blockSettings'] ?? []), ['kind' => 'system', 'ids' => ['column']]);

		$columnsHtml = '';
		$i = 0;
		foreach ($slotHtml as $idx => $html) {
			$w = $widths[$idx - 1] ?? round(100 / max(count($slotHtml), 1), 2);
			$columnsHtml .= self::renderElementFile('system/column', 'column.php', ['cs' => $cs, 'index' => $i + 1, 'content' => $html, 'width' => (float) $w, 'nativeClass' => '', 'columnCount' => count($slotHtml)]);
			++$i;
		}

		$columnsrowClass = (string) ($node['resolvedClass'] ?? '');

		return self::renderElementFile('system/column', 'columnsrow.php', ['cs' => $cs, 'gap' => $gap, 'content' => $columnsHtml, 'nativeClass' => 'gb-el-columns gb-el-columns-gap-'.rex_escape($gap), 'columnCount' => count($slotHtml), 'widths' => $widths, 'columnsrowClass' => $columnsrowClass]);
	}

	private static function renderSection(array $sectionSettings, string $innerHtml, string $cssClass, array $blockSettings = []): string
	{	$cs = self::buildContentSettingsObject($sectionSettings, [], 'section');
		self::attachBlockSettings($cs, $blockSettings, ['kind' => 'system', 'ids' => ['section']]);

		return self::renderElementFile('system/section', 'output.php', ['cs' => $cs, 'content' => $innerHtml, 'nativeClass' => $cssClass]);
	}

	private static function resolveElementDir(string $typeDir): ?string
	{	$customKey = str_starts_with($typeDir, 'custom/') ? substr($typeDir, 7) : null;
		if ($customKey !== null && !self::isValidCustomElementKey($customKey)) {
			return null;
		}

		$dataDir = rex_addon::get('gridblock2')->getDataPath('elements/'.$typeDir);
		if (is_dir($dataDir)) {
			return $dataDir;
		}

		if ($customKey !== null && str_contains($customKey, '.')) {
			[$addonName, $elementName] = explode('.', $customKey, 2);
			if (rex_addon::exists($addonName) && rex_addon::get($addonName)->isAvailable()) {
				$foreignDir = rex_addon::get($addonName)->getPath('gridblock2/elements/'.$elementName);
				return is_dir($foreignDir) ? $foreignDir : null;
			}
			return null;
		}

		$addonDir = rex_addon::get('gridblock2')->getPath('data/elements/'.$typeDir);
		return is_dir($addonDir) ? $addonDir : null;
	}

	private static function renderElementFile(string $typeDir, string $file, array $gbVars): string
	{	$dir = self::resolveElementDir($typeDir);
		if ($dir === null || !is_file($dir.'/'.$file)) {
			return '';
		}

		$gbVars['isNative'] = $gbVars['isNative'] ?? (((string) (rex_gridblock2::getConfig('gb_builder_native_grid_css_backend') ?? 'checked')) === 'checked');
		$_gb = (object) $gbVars;
		ob_start();
		include $dir.'/'.$file;

		return (string) ob_get_clean();
	}

	public static function getSystemElementSourceCode(string $typeDir, string $file): string
	{	$dir = self::resolveElementDir($typeDir);
		if ($dir === null || !is_file($dir.'/'.$file)) {
			return '';
		}

		return (string) rex_file::get($dir.'/'.$file);
	}

	public static function saveSystemElementOverride(string $typeDir, string $file, string $code): bool
	{	$targetPath = rex_addon::get('gridblock2')->getDataPath('elements/'.$typeDir.'/'.$file);

		if (!rex_dir::create(dirname($targetPath))) {
			return false;
		}

		return (bool) rex_file::put($targetPath, $code);
	}

	public static function checkSystemElementsAvailable(): array
	{	$missing = [];

		$columnDir = self::resolveElementDir('system/column');
		if ($columnDir === null || !is_file($columnDir.'/columnsrow.php')) {
			$missing[] = 'system/column/columnsrow.php';
		}
		if ($columnDir === null || !is_file($columnDir.'/column.php')) {
			$missing[] = 'system/column/column.php';
		}

		return $missing;
	}

	//Baut das $contentsettings-Objekt im selben ['template' => object, 'column_N' => object]-Format, das die Ausgabedateien (elements/section.php etc.) erwarten.
	private static function buildContentSettingsObject(array $rawSettings, array $columnSettingsList, string $ownScope): object
	{	$gcs = new Gridblock2Builder_ContentSettings();
		$cs = new stdClass();

		$gcs->getAllSettings($ownScope);
		$cs->$ownScope = self::resolveContentSettingsScope($gcs, $rawSettings, $ownScope);

		if (!empty($columnSettingsList)) {
			$gcs->getAllSettings('columns');
			foreach ($columnSettingsList as $i => $colSettings) {
				$cs->{'column_'.($i + 1)} = self::resolveContentSettingsScope($gcs, (array) $colSettings, 'columns');
			}
		}

		return $cs;
	}

	private static function resolveContentSettingsScope(Gridblock2Builder_ContentSettings $gcs, array $raw, string $scope): object
	{	$resolved = new stdClass();
		$keys = array_unique(array_merge((array) ($gcs->aSettings['showOptions'] ?? []), self::getMappedCsFieldKeys($scope)));
		foreach ($keys as $key) {
			if (($gcs->aSettings['options'][$key]['type'] ?? '') === 'html') {
				continue;
			}
			$val = $raw[$key] ?? null;
			$useDefault = ($val === null || $val === 'gridblockcontentsettingsdefault' || !empty($gcs->aSettings['options'][$key]['disabled']));
			$resolved->$key = $useDefault ? $gcs->getDefault($key) : $val;
		}

		return $resolved;
	}


	public static function previewSection(array $sectionSettings, array $blockSettings = []): string
	{	return self::renderSection($sectionSettings, '<slot></slot>', 'gb-el-section', $blockSettings);
	}

	//Die Spalten-Zeile ist alleinige, unbedingte Eigentuemerin von ".gridblock > .tmpl" - unabhaengig vom Baum-Kontext, kein Wrapper-Flag mehr noetig.
	public static function previewColumnsRow(array $templateSettings, string $gap, array $widths = [50, 50], array $columnSettingsList = [], ?string $columnsrowClass = null, array $blockSettings = []): string
	{
		$gapMapping = self::getGapCsMapping();
		if ($gapMapping !== null) {
			$templateSettings[$gapMapping['field']] = $gap;
		}
		$cs = self::buildContentSettingsObject($templateSettings, $columnSettingsList, 'template');
		self::attachBlockSettings($cs, $blockSettings, ['kind' => 'system', 'ids' => ['column']]);

		$columnsHtml = '';
		foreach ($widths as $i => $w) {
			$columnsHtml .= self::renderElementFile('system/column', 'column.php', ['cs' => $cs, 'index' => $i + 1, 'content' => '<slot name="col-'.($i + 1).'"></slot>', 'width' => (float) $w, 'nativeClass' => '', 'columnCount' => count($widths)]);
		}

		if ($columnsrowClass === null) {
			$ratioKey = implode('-', array_map(static fn ($w) => (int) round((float) $w), $widths));
			$columnsrowClass = '';
			foreach (self::getRatioPresets() as $candidate) {
				if (implode('-', array_map(static fn ($w) => (int) round((float) $w), $candidate['widths'])) === $ratioKey) {
					$columnsrowClass = (string) $candidate['cssClass'];
					break;
				}
			}
		}
		return self::renderElementFile('system/column', 'columnsrow.php', ['cs' => $cs, 'gap' => $gap, 'content' => $columnsHtml, 'nativeClass' => 'gb-el-columns gb-el-columns-gap-'.rex_escape($gap), 'columnCount' => count($widths), 'widths' => $widths, 'columnsrowClass' => $columnsrowClass]);
	}


	private static function buildLegacyContentSettings(array $templateSettings, array $slots, int $templateId)
	{
		if (!rex_addon::get('gridblock2')->isAvailable()) {
			return $templateSettings;
		}

		$aArr = ['template' => $templateSettings];
		foreach ($slots as $i => $slot) {
			$aArr['column_'.($i + 1)] = (array) ($slot['settings'] ?? []);
		}

		return (new Gridblock2ContentSettings)->parseGridblock2ContentSettings($aArr, $templateId);
	}

	private static function loadGridTemplateMeta(int $templateId): ?array
	{	$db = rex_sql::factory();
		$db->setQuery("SELECT columns, preview FROM ".rex::getTable('2034_gridtemplates')." WHERE id = '".$templateId."'");
		if ($db->getRows() <= 0) {
			return null;
		}

		$preview = (string) $db->getValue('preview');
		$preview = (!empty($preview)) ? str_replace(["\n\r", "\n", "\r"], ' ', $preview) : $preview;
		$preview = preg_replace('/\s+/', ' ', $preview);

		return ['columns' => (int) $db->getValue('columns'), 'preview' => $preview];
	}

	public static function getLegacyTemplateWidths(int $templateId): array
	{	$meta = self::loadGridTemplateMeta($templateId);
		if ($meta === null) {
			return [];
		}

		$preview = rex_var::toArray($meta['preview']);
		$cols = is_array($preview) ? ($preview['columns'] ?? null) : null;
		$widths = is_array($cols) ? array_map(static function ($c) { return (int) ($c['width'] ?? 0); }, $cols) : [];
		$widths = array_values(array_filter($widths, static function ($w) { return $w > 0; }));

		if (!$widths && $meta['columns'] > 0) {
			//preview-JSON fehlt/kaputt - anhand der bekannten (gegenprüfbaren) Spaltenanzahl gleichmässig verteilen
			$widths = array_fill(0, $meta['columns'], round(100 / $meta['columns'], 2));
		}

		return $widths;
	}

	private static function renderLegacyTemplateWrapper(int $templateId, array $slotHtml, array $rexVars, $contentsettings, array $templateMeta): string
	{	$db = rex_sql::factory();
		$db->setQuery("SELECT template FROM ".rex::getTable('2034_gridtemplates')." WHERE id = '".$templateId."'");
		if ($db->getRows() <= 0) {
			return '';
		}

		$op = $db->getValue('template');
		$selColumns = $templateMeta['columns'];
		$selPreview = $templateMeta['preview'];

		if (trim((string) $op) === '') {
			return implode('', array_map(static function ($html) { return (string) $html; }, $slotHtml));
		}

		$op = preg_replace('/REX_ARTICLE_ID/', (string) ($rexVars['artID'] ?? ''), $op);
		$op = preg_replace('/REX_CLANG_ID/', (string) ($rexVars['clangID'] ?? ''), $op);
		$op = preg_replace('/REX_CTYPE_ID/', (string) ($rexVars['ctypeID'] ?? ''), $op);
		$op = preg_replace('/REX_SLICE_ID/', (string) ($rexVars['sliceID'] ?? ''), $op);
		$op = preg_replace('/REX_GRID_TEMPLATE_ID/', (string) $templateId, $op);
		$op = preg_replace('/REX_GRID_TEMPLATE_PREVIEW/', $selPreview, $op);
		$op = preg_replace('/REX_GRID_TEMPLATE_COLUMNS/', (string) $selColumns, $op);

		for ($i = 1; $i <= $selColumns; ++$i) {
			$modOP = $slotHtml[$i] ?? '';
			$modOP = str_replace('\\', '\\\\', $modOP);
			$modOP = str_replace('$', '\$', $modOP);
			$modOP = str_replace(['<?php', '<?='], ['&lt;?php', '&lt;?='], $modOP);
			$modOP = preg_replace('/<\?(?!xml\b)/i', '&lt;?', $modOP);
			$op = preg_replace("/REX_GRID\[(\s)*(id=)?".$i."(\s)*\]/", $modOP, $op);
		}

		ob_start();
		try {
			ob_implicit_flush(false);
			$sandbox = function () use ($op, $templateId, $contentsettings) {
				require rex_stream::factory('gridblock_builder/template/'.$templateId, $op);
			};
			$sandbox();
		} finally {
			$content = ob_get_clean();
		}

		return $content;
	}
}
?>
