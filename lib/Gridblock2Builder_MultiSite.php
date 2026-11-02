<?php
/*
	Redaxo-Addon Gridblock²
	Builder-Engine: MultiSite-Funktionen
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class Gridblock2Builder_MultiSite
{
	public const FIELDS = [
		'gb_builder_content_stylesheets',
		'gb_builder_content_scripts',
		'gb_builder_preview_wrapper_class',
		'gb_builder_module_css_overrides',
		'gb_builder_preview_visibility_fixes',
		'gb_builder_palette',
	];

	private static array $domainIdCache = [];

	public static function isEnabled(): bool
	{	return (string) (rex_gridblock2::getConfig('gb_builder_multisite') ?? '') === 'checked' && self::isYrewriteAvailable();
	}

	public static function isYrewriteAvailable(): bool
	{	return rex_addon::exists('yrewrite') && rex_addon::get('yrewrite')->isAvailable() && class_exists('rex_yrewrite');
	}

	public static function getDomains(): array
	{	$domains = [];
		if (!self::isYrewriteAvailable()) {
			return $domains;
		}

		foreach (rex_yrewrite::getDomains() as $domain) {
			$id = (int) $domain->getId();
			if ($id > 0) {
				$domains[$id] = (string) $domain->getName();
			}
		}
		asort($domains, SORT_NATURAL | SORT_FLAG_CASE);

		return $domains;
	}

	public static function getSettings(): array
	{	$raw = (string) (rex_gridblock2::getConfig('gb_builder_domains') ?? '');
		$data = ($raw !== '') ? json_decode($raw, true) : [];

		return is_array($data) ? $data : [];
	}

	public static function getCurrentDomainId(): int
	{	if (!self::isYrewriteAvailable()) {
			return 0;
		}

		$articleId = rex_request::get('gbart', 'int', 0) ?: rex_request::get('article_id', 'int', 0) ?: (int) ($_SESSION['gridRexVars']['artID'] ?? 0) ?: (int) rex_article::getCurrentId();
		$clangId = rex_request::get('gbclang', 'int', 0) ?: rex_request::get('clang', 'int', 0) ?: (int) ($_SESSION['gridRexVars']['clangID'] ?? 0) ?: (int) rex_clang::getCurrentId();
		return self::getDomainIdByArticle($articleId, $clangId);
	}

	public static function getDomainIdByArticle(int $articleId, int $clangId): int
	{	if (!self::isYrewriteAvailable()) {
			return 0;
		}

		$cacheKey = $articleId.'-'.$clangId;

		if (!array_key_exists($cacheKey, self::$domainIdCache)) {
			$domain = ($articleId > 0) ? rex_yrewrite::getDomainByArticleId($articleId, $clangId) : null;
			self::$domainIdCache[$cacheKey] = $domain ? (int) $domain->getId() : 0;
		}

		return self::$domainIdCache[$cacheKey];
	}

	public static function getValue(string $key): string
	{	$base = (string) (rex_gridblock2::getConfig($key) ?? '');
		if (!in_array($key, self::FIELDS, true) || !self::isEnabled()) {
			return $base;
		}

		$domainId = self::getCurrentDomainId();
		if ($domainId <= 0) {
			return $base;
		}

		$settings = self::getSettings()[(string) $domainId] ?? null;
		if (!is_array($settings)) {
			return $base;
		}

		$own = trim((string) ($settings[$key] ?? ''));
		if (($settings['mode'] ?? 'merge') === 'replace') {
			return $own;
		}
		if ($own === '') {
			return $base;
		}

		if ($key === Gridblock2Palette::FIELD) {
			return Gridblock2Palette::merge($base, $own);
		}

		if ($key === 'gb_builder_preview_wrapper_class') {
			return trim($base.' '.$own);
		}

		return ($key === 'gb_builder_module_css_overrides') ? $own."\n".$base : $base."\n".$own;
	}

	public static function sanitizePost(array $post): string
	{	$existing = self::getSettings();
		$result = [];

		foreach ($post as $id => $values) {
			if (!is_array($values) || !preg_match('/^[0-9]+$/', (string) $id) || !empty($values['remove'])) {
				continue;
			}

			$entry = is_array($existing[(string) $id] ?? null) ? $existing[(string) $id] : [];
			$entry['mode'] = array_key_exists('mode', $values) ? ((($values['mode'] ?? '') === 'replace') ? 'replace' : 'merge') : (($entry['mode'] ?? 'merge') === 'replace' ? 'replace' : 'merge');
			foreach (self::FIELDS as $field) {
				$entry[$field] = array_key_exists($field, $values) ? trim((string) $values[$field]) : (string) ($entry[$field] ?? '');
			}
			if (!empty($values['avail_present'])) {
				$entry['avail'] = Gridblock2Builder_Availability::buildDomainFromPost([
					'mode' => $values['avail_mode'] ?? '',
					'all' => $values['avail_all'] ?? '',
					'items' => $values['avail_items'] ?? [],
					'deny' => $values['avail_deny'] ?? [],
				]);
			}

			$hasPreview = false;
			foreach (self::FIELDS as $field) {
				$hasPreview = $hasPreview || $entry[$field] !== '';
			}
			$avail = $entry['avail'] ?? null;
			$hasAvail = is_array($avail) && ($avail['mode'] === 'replace' || $avail['deny'] !== [] || $avail['items'] !== []);

			if ($hasPreview || $entry['mode'] === 'replace' || $hasAvail) {
				if (!$hasAvail) {
					unset($entry['avail']);
				}
				$result[(string) $id] = $entry;
			}
		}

		return $result ? json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
	}
}
