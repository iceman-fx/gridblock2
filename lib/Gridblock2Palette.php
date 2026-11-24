<?php
/*
	Redaxo-Addon Gridblock²
	Logik der Farbpalette
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class Gridblock2Palette
{
	public const FIELD = 'gb_builder_palette';

	public const EXAMPLE = '{"primary": {"label": "Primärfarbe", "class": ".bgCol-primary", "color": "var(--col-primary)", "text_color": "#ffffff"}}';

	private const ENTRY_FIELDS = ['label', 'class', 'color', 'text_color', 'hover_color', 'hover_text_color'];

	private static array $cache = [];

	public static function normalizeValue(string $raw): ?string
	{	$raw = trim($raw);
		if (preg_match('/^var\(\s*(--[A-Za-z0-9_-]+)\s*\)$/', $raw, $m)) {
			return 'var('.$m[1].')';
		}
		if (preg_match('/^--[A-Za-z0-9_-]+$/', $raw)) {
			return 'var('.$raw.')';
		}
		if (strcasecmp($raw, 'transparent') === 0) {
			return 'transparent';
		}
		if (preg_match('/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $raw)) {
			return strtolower($raw);
		}
		if (preg_match('/^(?:rgb|rgba|hsl|hsla)\(\s*[0-9a-zA-Z.%\s,\/+-]{1,60}\)$/', $raw)) {
			return $raw;
		}

		return null;
	}

	public static function normalizeClass(string $raw): ?string
	{	$parts = preg_split('/\s+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
		if ($parts === []) {
			return null;
		}
		foreach ($parts as $part) {
			if (!preg_match('/^\.?[A-Za-z_][A-Za-z0-9_-]*$/', $part)) {
				return null;
			}
		}

		return implode(' ', array_map(static fn (string $part): string => ltrim($part, '.'), $parts));
	}

	public static function isClassValue(string $value): bool
	{	$value = trim($value);

		return $value !== '' && strcasecmp($value, 'transparent') !== 0 && preg_match('/^[A-Za-z_][A-Za-z0-9_-]*(?:\s+[A-Za-z_][A-Za-z0-9_-]*)*$/', $value) === 1;
	}

	private static function topLevelKeys(string $json): array
	{	$keys = [];
		$depth = 0;
		$length = strlen($json);
		$i = 0;
		while ($i < $length) {
			$char = $json[$i];
			if ($char === '"') {
				$start = $i + 1;
				$i++;
				while ($i < $length && $json[$i] !== '"') {
					if ($json[$i] === '\\') {
						$i++;
					}
					$i++;
				}
				$string = substr($json, $start, $i - $start);
				$next = $i + 1;
				while ($next < $length && ctype_space($json[$next])) {
					$next++;
				}
				if ($depth === 1 && isset($json[$next]) && $json[$next] === ':') {
					$keys[] = $string;
				}
				$i++;
				continue;
			}
			if ($char === '{' || $char === '[') {
				$depth++;
			} elseif ($char === '}' || $char === ']') {
				$depth--;
			}
			$i++;
		}

		return $keys;
	}

	public static function parse(string $text): array
	{	$entries = [];
		$errors = [];
		$fieldNames = [
			'color' => ['bg', 'a2034_palette_field_bg'],
			'text_color' => ['fg', 'a2034_palette_field_fg'],
			'hover_color' => ['hbg', 'a2034_palette_field_hbg'],
			'hover_text_color' => ['hfg', 'a2034_palette_field_hfg'],
		];

		$text = trim($text);
		if ($text === '') {
			return ['entries' => [], 'errors' => []];
		}

		$data = json_decode($text, true);
		if (!is_array($data) || ($data !== [] && array_is_list($data))) {
			return ['entries' => [], 'errors' => [rex_i18n::msg('a2034_palette_err_json')]];
		}

		foreach (array_count_values(self::topLevelKeys($text)) as $key => $count) {
			if ($count > 1) {
				$errors[] = rex_i18n::msg('a2034_palette_err_dup', (string) $key);
			}
		}

		foreach ($data as $key => $row) {
			$key = (string) $key;
			if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,39}$/', $key)) {
				$errors[] = rex_i18n::msg('a2034_palette_err_key', $key);
				continue;
			}
			if (!is_array($row) || ($row !== [] && array_is_list($row))) {
				$errors[] = rex_i18n::msg('a2034_palette_err_entry', $key);
				continue;
			}

			$valid = true;
			foreach ($row as $field => $value) {
				if (!in_array((string) $field, self::ENTRY_FIELDS, true)) {
					$errors[] = rex_i18n::msg('a2034_palette_err_field', $key, (string) $field);
					$valid = false;
				} elseif (!is_string($value)) {
					$errors[] = rex_i18n::msg('a2034_palette_err_type', $key, (string) $field);
					$valid = false;
				}
			}
			if (!$valid) {
				continue;
			}

			$label = trim((string) ($row['label'] ?? ''));
			if ($label === '') {
				$errors[] = rex_i18n::msg('a2034_palette_err_label', $key);
				continue;
			}

			$class = '';
			$rawClass = trim((string) ($row['class'] ?? ''));
			if ($rawClass !== '') {
				$class = (string) self::normalizeClass($rawClass);
				if ($class === '') {
					$errors[] = rex_i18n::msg('a2034_palette_err_class', $key);
					continue;
				}
			}

			$entry = ['key' => $key, 'label' => $label, 'class' => $class, 'bg' => '', 'fg' => '', 'hbg' => '', 'hfg' => '', 'value' => ''];
			foreach ($fieldNames as $field => [$target, $labelKey]) {
				$raw = trim((string) ($row[$field] ?? ''));
				if ($raw === '') {
					continue;
				}
				$normalized = self::normalizeValue($raw);
				if ($normalized === null) {
					$errors[] = rex_i18n::msg('a2034_palette_err_value', $key, rex_i18n::msg($labelKey));
					$valid = false;
					continue;
				}
				$entry[$target] = $normalized;
			}
			if (!$valid) {
				continue;
			}

			if ($class === '' && $entry['bg'] === '') {
				$errors[] = rex_i18n::msg('a2034_palette_err_color', $key);
				continue;
			}

			$entry['value'] = ($class !== '') ? $class : $entry['bg'];
			$entries[$key] = $entry;
		}

		return ['entries' => $entries, 'errors' => $errors];
	}

	public static function merge(string $base, string $own): string
	{	$baseData = json_decode(trim($base), true);
		$ownData = json_decode(trim($own), true);
		if (!is_array($ownData) || $ownData === []) {
			return $base;
		}
		if (!is_array($baseData) || $baseData === []) {
			return $own;
		}

		return (string) json_encode(array_merge($baseData, $ownData), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public static function getEntries(): array
	{	$text = Gridblock2Builder_MultiSite::getValue(self::FIELD);
		$context = Gridblock2Builder_Preview::getPreviewContext();
		$cacheKey = md5($text).'|'.$context['articleId'].'|'.$context['clangId'].'|'.$context['domainId'];
		if (!isset(self::$cache[$cacheKey])) {
			$extended = rex_extension::registerPoint(new rex_extension_point('GRIDBLOCK2_PALETTE', $text, $context));
			if (is_array($extended)) {
				$extended = json_encode($extended, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
			$parsed = self::parse(is_string($extended) ? $extended : $text);
			if ($parsed['errors'] !== []) {
				rex_logger::logError(E_USER_WARNING, 'Gridblock2 Projektfarben (GRIDBLOCK2_PALETTE): '.implode(' ', $parsed['errors']), __FILE__, __LINE__);
			}
			self::$cache[$cacheKey] = $parsed['entries'];
		}

		return self::$cache[$cacheKey];
	}

	//Fuer Felder mit "palette": "colors" (Rahmen, Trenner u. a.): jeder Eintrag liefert seine Farbe statt der Klasse; Eintraege ohne Farbe entfallen
	public static function colorEntries(array $entries): array
	{	$result = [];
		foreach ($entries as $key => $entry) {
			if ((string) $entry['bg'] === '') {
				continue;
			}
			$entry['value'] = $entry['bg'];
			$result[$key] = $entry;
		}

		return $result;
	}

	public static function findByKey(string $key): ?array
	{	return self::getEntries()[$key] ?? null;
	}

	public static function findByValue(string $value): ?array
	{	$value = strtolower(trim($value));
		if ($value === '') {
			return null;
		}
		foreach (self::getEntries() as $entry) {
			if (strtolower((string) $entry['value']) === $value) {
				return $entry;
			}
		}

		return null;
	}

	public static function previewVarsCss(): string
	{	return Gridblock2Builder_Preview::collectHostVariables().Gridblock2Builder_Preview::collectExtensionCss(true);
	}

	private static bool $jsQueued = false;

	private static bool $cssEmitted = false;

	private static bool $varsEmitted = false;

	private static ?string $varsJson = null;

	public static function assetUrls(): array
	{	$addon = rex_addon::get('gridblock2');

		return [
			'js' => rex_gridblock2_helper::getTimebuster($addon->getAssetsUrl('js/gb-palette.min.js')),
			'css' => rex_gridblock2_helper::getTimebuster($addon->getAssetsUrl('css/gb-palette.min.css')),
		];
	}

	private static function queueJs(): array
	{	$urls = self::assetUrls();
		if (!self::$jsQueued) {
			self::$jsQueued = true;
			if (class_exists('rex_view')) {
				rex_view::addJsFile($urls['js']);
			}
		}

		return $urls;
	}

	private static function queueJsOnly(): string
	{	self::queueJs();

		return '';
	}

	public static function assets(): string
	{	if (self::$varsJson === null) {
			self::$varsJson = (string) json_encode(['css' => self::previewVarsCss()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		}
		self::$varsEmitted = true;
		$markup = '<script type="application/json" class="gb-pal-vars">'.self::$varsJson.'</script>';
		$urls = self::queueJs();
		if (!self::$cssEmitted) {
			self::$cssEmitted = true;
			$markup = '<link rel="stylesheet" href="'.rex_escape($urls['css']).'" data-gb-palette-css="1">'.$markup;
		}

		return $markup;
	}

	public static function resolve(string $value, string $role = 'bg'): string
	{	$value = trim($value);
		if ($value === '' || $value[0] !== '@') {
			return $value;
		}
		$entry = self::getEntries()[substr($value, 1)] ?? null;

		return $entry ? (string) ($entry[$role] ?? '') : '';
	}

	public static function select(string $name, array $options = []): string
	{	$entries = (isset($options['entries']) && is_array($options['entries'])) ? $options['entries'] : self::getEntries();
		$indValue = (string) ($options['ind'] ?? 'ind');
		self::queueJs();
		$optionsHtml = '';
		foreach ($entries as $entry) {
			$optionsHtml .= '<option value="'.rex_escape((string) $entry['key']).'" data-gb-bg="'.rex_escape((string) $entry['bg']).'" data-gb-fg="'.rex_escape((string) $entry['fg']).'">'.rex_escape((string) $entry['label']).'</option>';
		}
		$optionsHtml .= '<option value="'.rex_escape($indValue).'" data-gb-ind="1">Individuell</option>';

		return '<select name="'.rex_escape($name).'" class="selectpicker form-control" data-gb-palette data-live-search="true" data-width="100%">'.$optionsHtml.'</select>';
	}

	public static function field(string $name, string $id, string $value, array $options = []): string
	{	$role = (string) ($options['role'] ?? 'bg');
		$mode = (string) ($options['mode'] ?? 'solid');
		$placeholder = (string) ($options['placeholder'] ?? '#rrggbb oder rgba(…)');
		$byValue = (($options['format'] ?? 'key') === 'value');
		$entries = (isset($options['entries']) && is_array($options['entries'])) ? $options['entries'] : self::getEntries();
		$input = (isset($options['input']) && is_string($options['input']))
			? $options['input']
			: '<input type="text" name="'.rex_escape($name).'" id="'.rex_escape($id).'" class="form-control" data-colorpicker'.($mode !== 'solid' ? ' data-colorpicker-mode="'.rex_escape($mode).'"' : '').' placeholder="'.rex_escape($placeholder).'" value="'.rex_escape($value).'">';
		if ($entries === []) {
			return $input;
		}

		$optionsHtml = '';
		$head = '<option value=""'.($value === '' ? ' selected="selected"' : '').'>- keine Farbe -</option>';
		$matched = false;
		if ($byValue) {
			$match = null;
			if ($value !== '') {
				foreach ($entries as $entry) {
					if (strcasecmp((string) $entry['value'], $value) === 0) {
						$match = (string) $entry['value'];
						break;
					}
				}
				if ($match === null) {
					foreach ($entries as $entry) {
						if ((string) $entry['class'] !== '' && strcasecmp((string) $entry['class'], $value) === 0) {
							$match = (string) $entry['value'];
							break;
						}
					}
				}
			}
			$alt = ($value !== '' && $match === null && self::isClassValue($value));
			$ind = ($value !== '' && $match === null && !$alt);
			if ($alt) {
				$head .= '<option value="'.rex_escape($value).'" data-gb-alt="1" selected="selected">Alt: '.rex_escape($value).'</option>';
			}
			foreach ($entries as $entry) {
				$selected = '';
				if ($match !== null && !$matched && (string) $entry['value'] === $match) {
					$selected = ' selected="selected"';
					$matched = true;
				}
				$optionsHtml .= '<option value="'.rex_escape((string) $entry['value']).'" data-gb-bg="'.rex_escape((string) $entry['bg']).'" data-gb-fg="'.rex_escape((string) $entry['fg']).'"'.$selected.'>'.rex_escape((string) $entry['label']).'</option>';
			}
		} else {
			$other = ($role === 'fg') ? 'bg' : 'fg';
			foreach ($entries as $entry) {
				$cell = (string) $entry[$role];
				if ($cell === '') {
					continue;
				}
				$optionValue = '@'.$entry['key'];
				$selected = (!$matched && $value === $optionValue) ? ' selected="selected"' : '';
				$matched = $matched || $selected !== '';
				$optionsHtml .= '<option value="'.rex_escape($optionValue).'" data-gb-bg="'.rex_escape($cell).'" data-gb-fg="'.rex_escape((string) $entry[$other]).'"'.$selected.'>'.rex_escape((string) $entry['label']).'</option>';
			}
			$alt = ($value !== '' && $value[0] === '@' && !$matched);
			$ind = ($value !== '' && $value[0] !== '@');
			if ($alt) {
				$head .= '<option value="'.rex_escape($value).'" data-gb-alt="1" selected="selected">Alt: '.rex_escape(substr($value, 1)).'</option>';
			}
		}
		$optionsHtml .= '<option value="__ind__" data-gb-ind="1"'.($ind ? ' selected="selected"' : '').'>Individuell</option>';

		return '<div data-gb-palette-wrap><select class="selectpicker form-control" data-gb-palette data-gb-palette-field data-live-search="true" data-width="100%">'.$head.$optionsHtml.'</select>'
			.(self::$varsEmitted ? self::queueJsOnly() : self::assets())
			.'<div data-gb-palette-ind'.($ind ? '' : ' style="display:none"').'>'.$input.'</div></div>';
	}
}
