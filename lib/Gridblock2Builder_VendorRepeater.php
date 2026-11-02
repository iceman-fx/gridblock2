<?php
/*
	Redaxo-Addon Gridblock²
	Builder-Engine: Repeater-Vendor
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class Gridblock2Builder_VendorRepeater
{
	public const UID_TOKEN = 'FMREP_UID_TOKEN';

	private const REP_ID = 'REP_ID';


	public static function makeUid(): string
	{	return 'r'.bin2hex(random_bytes(4));
	}


	public static function render(string $name, string|array|null $existing, string $markup, array $options = []): string
	{	$options = array_merge(['duplicate' => true, 'offline' => true, 'sortable' => true, 'movebuttons' => false, 'collapsible' => false, 'collapsed' => false, 'titleField' => '', 'titleFallback' => 'Eintrag', 'maxRows' => 0], $options);

		$items = self::decodeExisting($existing);
		//Ausgangswert fuer das versteckte JSON-Feld: ohne ihn ist es bis zur Initialisierung des Repeater-Skripts leer,
		//und eine zu frueh abgefragte Live-Vorschau (z. B. Gridblock-Element) bekaeme dadurch keine Zeilen
		$initialJson = $items ? (string) json_encode($items, JSON_UNESCAPED_UNICODE) : '';

		$rows = [];
		foreach ($items as $uid => $row):
			$row = (array) $row;
			$rows[] = ['uid' => (string) $uid, 'html' => self::buildRowHtml($markup, (string) $uid, $row), 'disabled' => !empty($row['disabled'])];
		endforeach;

		if (empty($rows)):
			$firstUid = self::makeUid();
			$rows = [['uid' => $firstUid, 'html' => self::buildRowHtml($markup, $firstUid, []), 'isNew' => true]];
		endif;

		$emptyRowHtml = self::buildRowHtml($markup, self::UID_TOKEN, []);

		ob_start();
			include rex_path::addon('gridblock2', 'lib/templates/repeater.php');
		return ob_get_clean();
	}


	private static function decodeExisting(string|array|null $existing): array
	{	if (is_array($existing)):
			return $existing;
		endif;

		$raw = trim((string) $existing);
		if ($raw === ''):
			return [];
		endif;

		$decoded = json_decode(htmlspecialchars_decode($raw, ENT_QUOTES), true);
		return is_array($decoded) ? $decoded : [];
	}


	private static function buildRowHtml(string $markup, string $uid, array $row): string
	{	$html = self::suffixRadioNames($markup, $uid);
		$html = self::autoSelectOptions($html, $row);

		$html = preg_replace_callback('/REX_(MEDIA|MEDIALIST|LINK|LINKLIST|MULTILINK)\[([^\]]*)\]/', function (array $m) use ($uid, $row) {
			$attrs = self::parseAttributeList($m[2]);
			$field = $attrs['id'] ?? '';
			unset($attrs['id']);
			return $field === '' ? '' : self::renderWidget(strtolower($m[1]), $field, $attrs, $uid, $row);
		}, $html);

		$html = preg_replace_callback('/\{\{([A-Za-z_]\w*)(?:\|(checked)(?::([^}]*))?)?\}\}/', function (array $m) use ($uid, $row) {
			$field = $m[1];
			$isChecked = ($m[2] ?? '') === 'checked';
			$compareValue = $m[3] ?? null;

			if ($isChecked):
				$value = (string) ($row[$field] ?? '');
				return ($value === ($compareValue ?? 'checked')) ? 'checked="checked"' : '';
			endif;

			if ($field === self::REP_ID):
				return htmlspecialchars($uid, ENT_QUOTES);
			endif;

			return htmlspecialchars((string) ($row[$field] ?? ''), ENT_QUOTES);
		}, $html);

		return $html;
	}


	private static function suffixRadioNames(string $html, string $uid): string
	{	return preg_replace_callback('/<input\b[^>]*>/i', function (array $m) use ($uid) {
			$tag = $m[0];
			if (!preg_match('/\btype\s*=\s*(["\']?)radio\1/i', $tag)):
				return $tag;
			endif;

			return preg_replace_callback('/\bname\s*=\s*(["\'])([^"\']*)\1/i', function (array $nm) use ($uid) {
				return 'name='.$nm[1].$nm[2].'_'.$uid.$nm[1];
			}, $tag, 1);
		}, $html);
	}


	private static function autoSelectOptions(string $html, array $row): string
	{	return preg_replace_callback('/<select\b([^>]*)>(.*?)<\/select>/is', function (array $m) use ($row) {
			$selectAttrs = $m[1];
			$inner = $m[2];

			if (!preg_match('/\bname\s*=\s*(["\'])([^"\']*)\1/i', $selectAttrs, $nameMatch)):
				return $m[0];
			endif;

			$value = (string) ($row[$nameMatch[2]] ?? '');

			$inner = preg_replace_callback('/<option\b([^>]*)>/i', function (array $om) use ($value) {
				$optAttrs = $om[1];
				if (!preg_match('/\bvalue\s*=\s*(["\'])([^"\']*)\1/i', $optAttrs, $valueMatch)):
					return $om[0];
				endif;

				$optAttrs = preg_replace('/\s+selected(\s*=\s*(["\']?)selected\2)?/i', '', $optAttrs);
				if ($valueMatch[2] === $value):
					$optAttrs .= ' selected="selected"';
				endif;

				return '<option'.$optAttrs.'>';
			}, $inner);

			return '<select'.$selectAttrs.'>'.$inner.'</select>';
		}, $html);
	}


	private static function renderWidget(string $type, string $field, array $options, string $uid, array $row): string
	{	$value = (string) ($row[$field] ?? '');
		$widgetId = $uid.'_'.$field;

		return match ($type) {
			'media' => rex_var_media::getWidget($widgetId, $field, $value, $options),
			'medialist' => rex_var_medialist::getWidget($widgetId, $field, $value, $options),
			'link' => rex_var_link::getWidget($widgetId, $field, $value, $options),
			'linklist' => rex_var_linklist::getWidget($widgetId, $field, $value, $options),
			'multilink' => Gridblock2Builder_Tree::renderMultilink($field, $value, self::buildMultilinkOptions($options)),
			default => '',
		};
	}


	private static function buildMultilinkOptions(array $attrs): array
	{	$options = [];
		if (($attrs['types'] ?? '') !== ''):
			$options['types'] = array_values(array_filter(array_map('trim', explode(',', $attrs['types']))));
		endif;
		if (($attrs['mediatypes'] ?? '') !== ''):
			$options['mediaTypes'] = $attrs['mediatypes'];
		endif;
		if (($attrs['mediacategory'] ?? '') !== ''):
			$options['mediaCategory'] = (int) $attrs['mediacategory'];
		endif;
		return $options;
	}


	private static function parseAttributeList(string $attrString): array
	{	$attrs = [];
		if (preg_match_all('/([a-zA-Z_][\w-]*)\s*=\s*"([^"]*)"/', $attrString, $matches, PREG_SET_ORDER)):
			foreach ($matches as $match):
				$attrs[$match[1]] = $match[2];
			endforeach;
		endif;

		return $attrs;
	}
}
