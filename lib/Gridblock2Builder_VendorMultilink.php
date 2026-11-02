<?php
/*
	Redaxo-Addon Gridblock²
	Builder-Engine: Multilink-Vendor
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class Gridblock2Builder_VendorMultilink
{
	private const ALL_TYPES = ['link', 'url', 'media', 'email', 'tel'];

	private const SCHEME_PREFIX = ['tel' => 'tel:', 'email' => 'mailto:'];

	private const TYPE_META = [
		'link'  => ['label' => 'CMS-Seite',        'icon' => 'fa-link'],
		'url'   => ['label' => 'Externe URL',      'icon' => 'fa-globe'],
		'media' => ['label' => 'Medienpool-Datei', 'icon' => 'fa-file'],
		'email' => ['label' => 'E-Mail',           'icon' => 'fa-envelope'],
		'tel'   => ['label' => 'Telefon',          'icon' => 'fa-phone'],
	];


	public static function makeUid(): string
	{	return 'ml'.bin2hex(random_bytes(4));
	}


	public static function render(string $name, string|array|null $existing, array $options = []): string
	{	$options = array_merge(['types' => self::ALL_TYPES, 'mediaTypes' => '', 'mediaCategory' => 0], $options);

		$allowedTypes = array_values(array_unique(array_filter($options['types'], static fn ($t) => in_array($t, self::ALL_TYPES, true))));
		if (empty($allowedTypes)):
			$allowedTypes = self::ALL_TYPES;
		endif;

		$data       = self::decodeExisting($existing);
		$storedType = (string) ($data['type'] ?? '');
		$activeType = in_array($storedType, $allowedTypes, true) ? $storedType : $allowedTypes[0];
		$value      = (string) ($data['value'] ?? '');

		if ($activeType === 'tel' || $activeType === 'email'):
			$value = self::stripPrefix($activeType, $value);
		endif;

		$uid = self::makeUid();

		$telValue   = $activeType === 'tel'   ? $value : '';
		$emailValue = $activeType === 'email' ? $value : '';
		$urlValue   = $activeType === 'url'   ? $value : '';
		$linkValue  = $activeType === 'link'  ? $value : '';
		$mediaValue = $activeType === 'media' ? $value : '';

		$linkWidgetHtml  = in_array('link', $allowedTypes, true)
			? rex_var_link::getWidget($uid.'_link', $uid.'_link', $linkValue, [])
			: '';
		$mediaWidgetHtml = in_array('media', $allowedTypes, true)
			? rex_var_media::getWidget($uid.'_media', $uid.'_media', $mediaValue, self::buildMediaArgs($options))
			: '';

		$href        = (self::SCHEME_PREFIX[$activeType] ?? '').$value;
		$outputValue = json_encode(['type' => $activeType, 'value' => $value, 'href' => $href], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		ob_start();
			include rex_path::addon('gridblock2', 'lib/templates/multilink.php');
		return ob_get_clean();
	}


	private static function buildMediaArgs(array $options): array
	{	$args = [];
		if ($options['mediaTypes'] !== ''):
			$args['types'] = $options['mediaTypes'];
		endif;
		if ($options['mediaCategory']):
			$args['category'] = (int) $options['mediaCategory'];
		endif;
		return $args;
	}


	private static function stripPrefix(string $type, string $value): string
	{	$prefix = self::SCHEME_PREFIX[$type] ?? '';
		if ($prefix === '' || $value === ''):
			return $value;
		endif;
		return preg_replace('/^'.preg_quote($prefix, '/').'/i', '', $value);
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


	public static function typeMeta(string $type): array
	{	return self::TYPE_META[$type] ?? ['label' => $type, 'icon' => 'fa-circle'];
	}
}
