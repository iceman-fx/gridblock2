<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Hilfsklasse des Custom-Elements "Bildercollage"
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

if (!class_exists('GbCollageKit')) {
	class GbCollageKit
	{
		public const MAX_IMAGES = 10;

		public const SHADOW = 'rgba(50,50,93, 0.25) 0px 13px 27px -5px, rgba(0,0,0, 0.3) 0px 8px 16px -8px';

		public const RATIOS = [
			'1-1' => ['1/1', '1:1'],
			'3-2' => ['3/2', '3:2'],
			'4-3' => ['4/3', '4:3'],
			'8-5' => ['8/5', '8:5'],
			'16-9' => ['16/9', '16:9'],
			'21-9' => ['21/9', '21:9'],
			'2-3' => ['2/3', '2:3 (Vertikal)'],
			'3-4' => ['3/4', '3:4 (Vertikal)'],
			'5-8' => ['5/8', '5:8 (Vertikal)'],
			'9-16' => ['9/16', '9:16 (Vertikal)'],
			'9-21' => ['9/21', '9:21 (Vertikal)'],
		];

		public const RADII = [
			'keine' => ['0px', '- keine Eckenrundung -'],
			'abgerundet' => ['15px', 'Abgerundet'],
			'pille' => ['99999px', 'Pille'],
			'rund' => ['50%', 'Rund'],
		];

		public const PARALLAX = [
			'none' => '- kein Parallax -',
			'slow-up' => 'Langsam ↑',
			'medium-up' => 'Mittel ↑',
			'fast-up' => 'Schnell ↑',
			'slow-down' => 'Langsam ↓',
			'medium-down' => 'Mittel ↓',
			'fast-down' => 'Schnell ↓',
		];

		public const HOVERS = [
			'' => '- Standard -',
			'icG-hover-grey' => 'Bild ausgrauen',
			'icG-hover-color' => 'Bild einfärben',
			'icG-hover-blur' => 'Bild weichzeichnen',
			'icG-hover-scaleup' => 'Bild vergrößern',
		];

		public static function pick(string $value, array $allowed, string $default): string
		{
			return in_array($value, $allowed, true) ? $value : $default;
		}

		public static function num(float $value): string
		{
			$text = rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');

			return ($text === '' || $text === '-0') ? '0' : $text;
		}

		public static function ratioValue(string $key): float
		{
			$parts = explode('/', self::RATIOS[$key][0] ?? '3/2');
			$height = (float) ($parts[1] ?? 1);

			return $height > 0 ? ((float) $parts[0] / $height) : 1.5;
		}

		public static function isSvg(string $file): bool
		{
			return preg_match('/\.svg$/i', $file) === 1;
		}

		public static function isImage(string $file): bool
		{
			return preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $file) === 1;
		}

		public static function images(string $json): array
		{
			$data = json_decode($json, true);
			if (!is_array($data)) {
				return [];
			}
			$list = (isset($data['images']) && is_array($data['images'])) ? $data['images'] : $data;
			$images = [];
			foreach ($list as $item) {
				if (!is_array($item)) {
					continue;
				}
				$file = trim((string) ($item['file'] ?? ''));
				if ($file === '' || $file === '..' || preg_match('/[\/\\\\\x00-\x1F]/', $file) === 1) {
					continue;
				}
				$images[] = [
					'file' => $file,
					'x' => max(-100.0, min(100.0, (float) ($item['x'] ?? 0))),
					'y' => max(-100.0, min(2000.0, (float) ($item['y'] ?? 0))),
					'width' => max(1.0, min(100.0, (float) ($item['width'] ?? 50))),
					'z' => max(0, min(999, (int) ($item['z'] ?? 1))),
					'format' => self::pick((string) ($item['format'] ?? ''), array_keys(self::RATIOS), '3-2'),
					'radius' => self::pick((string) ($item['radius'] ?? ''), array_keys(self::RADII), 'keine'),
					'shadow' => !empty($item['shadow']),
					'parallax' => self::pick((string) ($item['parallax'] ?? ''), array_keys(self::PARALLAX), 'none'),
				];
				if (count($images) >= self::MAX_IMAGES) {
					break;
				}
			}

			return $images;
		}

		public static function layoutHeight(array $images): float
		{
			$max = 0.0;
			foreach ($images as $image) {
				$bottom = $image['y'] + $image['width'] / self::ratioValue($image['format']);
				if ($bottom > $max) {
					$max = $bottom;
				}
			}

			return $max;
		}

		public static function typeExists(string $type): bool
		{
			static $types = null;
			if ($types === null) {
				$types = [];
				if (rex_addon::get('media_manager')->isAvailable()) {
					foreach (rex_sql::factory()->getArray('SELECT name FROM '.rex::getTable('media_manager_type')) as $row) {
						$types[(string) $row['name']] = true;
					}
				}
			}

			return isset($types[$type]);
		}

		public static function mediaUrl(string $type, string $file): string
		{
			return self::typeExists($type) ? rex_media_manager::getUrl($type, $file) : rex_url::media($file);
		}

		public static function svgDimensions(string $file): array
		{
			$result = ['width' => '', 'height' => ''];
			$path = rex_path::media($file);
			if (!is_file($path)) {
				return $result;
			}
			$xml = @simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NONET);
			if ($xml === false) {
				return $result;
			}
			$viewBox = isset($xml['viewBox']) ? preg_split('/[\s,]+/', trim((string) $xml['viewBox'])) : [];
			$width = (count($viewBox) === 4) ? ((float) $viewBox[2] - (float) $viewBox[0]) : 0.0;
			$height = (count($viewBox) === 4) ? ((float) $viewBox[3] - (float) $viewBox[1]) : 0.0;
			if ($width <= 0 || $height <= 0) {
				$width = (float) ($xml['width'] ?? 0);
				$height = (float) ($xml['height'] ?? 0);
			}
			if ($width > 0 && $height > 0) {
				$result['width'] = self::num($width);
				$result['height'] = self::num($height);
			}

			return $result;
		}
	}
}
