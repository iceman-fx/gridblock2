<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Hilfsklasse des Custom-Elements "Karten"
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

if (!class_exists('GbCardsKit')) {
	class GbCardsKit
	{
		public const STYLES = [
			'klassisch' => 'Klassisch (Karte mit Rahmen und Schatten)',
			'offen' => 'Offen (Bild und Text ohne Karte)',
			'kachel' => 'Bildkachel',
			'kachelbtn' => 'Bildkachel mit Button',
			'kompakt' => 'Kompakt (Symbol, Text und Pfeil)',
		];

		public const WIDTHS = [
			'' => 'Automatisch (je nach Darstellung)',
			'schmal' => 'Schmal (220 px)',
			'normal' => 'Normal (300 px)',
			'breit' => 'Breit (400 px)',
			'sehr-breit' => 'Sehr breit (560 px)',
			'voll' => 'Volle Breite',
		];

		public const RATIOS = [
			'' => 'Automatisch (je nach Darstellung)',
			'1-1' => '1:1 (Quadrat)',
			'4-3' => '4:3',
			'3-2' => '3:2',
			'16-9' => '16:9 (Breitbild)',
			'21-9' => '21:9 (Panorama)',
			'original' => 'Original (Seitenverhältnis des Bildes)',
		];

		public const IMGSIZES = [
			'' => 'Volle Breite (Symbole bleiben kompakt)',
			'mittel' => 'Mittel (120 px)',
			'klein' => 'Klein (64 px)',
		];

		public const POSITIONS = [
			'oben' => 'Oben',
			'links' => 'Links',
			'rechts' => 'Rechts',
		];

		public const GAPS = [
			'12' => 'Klein (12px)',
			'24' => 'Mittel (24px)',
			'40' => 'Groß (40px)',
		];

		public static function pick(string $value, array $allowed, string $default): string
		{
			return in_array($value, $allowed, true) ? $value : $default;
		}

		public static function link(string $json): array
		{
			$data = json_decode($json, true);
			$data = is_array($data) ? $data : [];
			$type = (string) ($data['type'] ?? '');
			$value = (string) ($data['value'] ?? '');
			$href = match ($type) {
				'tel', 'email', 'url' => trim((string) ($data['href'] ?? '')),
				'anchor' => preg_match('/^#[A-Za-z0-9_:.-]+$/', trim((string) ($data['href'] ?? ''))) ? trim((string) $data['href']) : '',
				'link' => ((int) $value > 0) ? rex_getUrl((int) $value) : '',
				'media' => ($value !== '') ? rex_url::media($value) : '',
				default => '',
			};
			if (preg_match('/^\s*(?:javascript|data|vbscript):/i', $href)) {
				$href = '';
			}

			return ['href' => $href, 'blank' => $type === 'url'];
		}

		public static function isIcon(string $file): bool
		{
			return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'svg';
		}

		public static function mediaAlt(string $file): string
		{
			$media = ($file !== '' && class_exists('rex_media')) ? rex_media::get($file) : null;
			if (!$media || trim((string) $media->getValue('med_decorative'), '|0 ') !== '') {
				return '';
			}

			return trim((string) $media->getValue('med_alt')) ?: trim((string) $media->getTitle());
		}

		public static function palette(): array
		{
			return class_exists('Gridblock2Palette') ? Gridblock2Palette::getEntries() : [];
		}

		public static function cssValue(string $raw): string
		{
			$raw = trim($raw);
			if ($raw === '') {
				return '';
			}
			$normalized = class_exists('Gridblock2Palette') ? Gridblock2Palette::normalizeValue($raw) : null;
			if ($normalized !== null) {
				return $normalized;
			}

			return (preg_match('/^(?:linear|radial|conic)-gradient\([#(),.%\sA-Za-z0-9-]{1,300}\)$/', $raw) && stripos($raw, 'url') === false) ? $raw : '';
		}

		public static function colorField(string $name, string $id, string $value, array $options = []): string
		{
			if (method_exists('Gridblock2Palette', 'field')) {
				return Gridblock2Palette::field($name, $id, $value, $options);
			}
			$mode = (string) ($options['mode'] ?? 'solid');

			return '<input type="text" name="'.rex_escape($name).'" id="'.rex_escape($id).'" class="form-control" data-colorpicker'.($mode !== 'solid' ? ' data-colorpicker-mode="'.rex_escape($mode).'"' : '').' placeholder="'.rex_escape((string) ($options['placeholder'] ?? '')).'" value="'.rex_escape($value).'">';
		}

		public static function resolveColor(string $value, string $role = 'bg'): string
		{
			if (method_exists('Gridblock2Palette', 'resolve')) {
				return self::cssValue(Gridblock2Palette::resolve($value, $role));
			}
			$value = trim($value);
			if ($value !== '' && $value[0] === '@') {
				$value = (string) (self::palette()[substr($value, 1)][$role] ?? '');
			}

			return self::cssValue($value);
		}
	}
}
