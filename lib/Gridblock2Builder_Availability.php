<?php
/*
	Redaxo-Addon Gridblock²
	Inhaltsmodulverfügbarkeit prüfen
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class Gridblock2Builder_Availability
{
	public static function isEnabled(): bool
	{	return (string) (rex_gridblock2::getConfig('gb_builder_multisite_elements') ?? '') === 'checked' && Gridblock2Builder_MultiSite::isYrewriteAvailable();
	}

	public static function moduleKey(int $moduleId): string
	{	return 'module:'.$moduleId;
	}

	public static function customKey(string $elementType): string
	{	return 'custom:'.$elementType;
	}

	public static function sectionKey(): string
	{	return 'system:section';
	}

	public static function getDomainRule(int $domainId): ?array
	{	$entry = Gridblock2Builder_MultiSite::getSettings()[(string) $domainId] ?? null;
		$avail = is_array($entry) ? ($entry['avail'] ?? null) : null;
		if (!is_array($avail)) {
			return null;
		}

		return [
			'mode' => (($avail['mode'] ?? 'merge') === 'replace') ? 'replace' : 'merge',
			'all' => !empty($avail['all']),
			'items' => array_values(array_map('strval', (array) ($avail['items'] ?? []))),
			'deny' => array_values(array_map('strval', (array) ($avail['deny'] ?? []))),
		];
	}

	public static function isAllowedForDomain(string $itemKey, int $domainId): bool
	{	if ($domainId <= 0) {
			return true;
		}

		$rule = self::getDomainRule($domainId);
		if ($rule === null) {
			return true;
		}

		if ($rule['mode'] === 'replace') {
			return $rule['all'] || in_array($itemKey, $rule['items'], true);
		}

		return !in_array($itemKey, $rule['deny'], true);
	}

	private static function isApplicable(array $rexVars): bool
	{	return self::isEnabled() && !Gridblock2Builder_Tree::isDemoVars($rexVars);
	}

	public static function isAllowed(string $itemKey, array $rexVars): bool
	{	if (!self::isApplicable($rexVars)) {
			return true;
		}

		return self::isAllowedForDomain($itemKey, Gridblock2Builder_MultiSite::getCurrentDomainId());
	}

	public static function isAllowedForArticle(string $itemKey, int $articleId, int $clangId): bool
	{	if (!self::isEnabled()) {
			return true;
		}

		return self::isAllowedForDomain($itemKey, Gridblock2Builder_MultiSite::getDomainIdByArticle($articleId, $clangId));
	}

	public static function isLockedForUser(string $itemKey, array $rexVars): bool
	{	$user = rex::getUser();
		if ($user === null || $user->isAdmin()) {
			return false;
		}

		return !self::isAllowed($itemKey, $rexVars);
	}

	public static function getCatalog(): array
	{	$modules = [];
		$sql = rex_sql::factory();
		$sql->setQuery('SELECT id, name FROM '.rex::getTable('module').' WHERE input NOT LIKE "%/* GRID_MODULE_IDENTIFIER | DONT REMOVE */%" ORDER BY name, id');
		foreach ($sql as $row) {
			$modules[self::moduleKey((int) $row->getValue('id'))] = (string) $row->getValue('name');
		}

		$elements = [];
		$sectionMeta = Gridblock2Builder_Tree::getSectionElementMeta();
		if ($sectionMeta['enabled']) {
			$elements[self::sectionKey()] = $sectionMeta['label'];
		}
		foreach (Gridblock2Builder_Tree::discoverCustomElementTypes() as $key => $meta) {
			if ($meta['enabled'] && !$meta['demoOnly']) {
				$elements[self::customKey($key)] = $meta['label'];
			}
		}

		return ['modules' => $modules, 'elements' => $elements];
	}

	public static function getEffectiveItems(int $domainId): array
	{	$catalog = self::getCatalog();
		$result = ['modules' => [], 'elements' => []];
		foreach (['modules', 'elements'] as $group) {
			foreach ($catalog[$group] as $key => $label) {
				if (self::isAllowedForDomain($key, $domainId)) {
					$result[$group][$key] = $label;
				}
			}
		}

		return $result;
	}

	public static function sanitizeKeys($values): array
	{	$keys = [];
		foreach ((array) $values as $value) {
			$value = (string) $value;
			if (preg_match('/^(module:[0-9]+|custom:[A-Za-z0-9_.-]+|system:section)$/', $value)) {
				$keys[] = $value;
			}
		}

		return array_values(array_unique($keys));
	}

	public static function buildDomainFromPost(array $values): array
	{	return [
			'mode' => (($values['mode'] ?? '') === 'replace') ? 'replace' : 'merge',
			'all' => !empty($values['all']),
			'items' => self::sanitizeKeys($values['items'] ?? []),
			'deny' => self::sanitizeKeys($values['deny'] ?? []),
		];
	}
}
