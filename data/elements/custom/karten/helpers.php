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
			if ($file === '' || !class_exists('rex_media')) {
				return '';
			}
			$media = rex_media::get($file);
			if (!$media) {
				return '';
			}
			$alt = trim((string) $media->getValue('med_alt'));

			return $alt !== '' ? $alt : trim((string) $media->getTitle());
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

		public static function resolveColor(string $value, string $role = 'bg'): string
		{
			$value = trim($value);
			if ($value !== '' && $value[0] === '@') {
				$entry = self::palette()[substr($value, 1)] ?? null;
				$value = $entry ? (string) ($entry[$role] ?? '') : '';
			}

			return self::cssValue($value);
		}

		public static function colorField(array $palette, string $name, string $id, string $value, string $role, string $mode, string $placeholder): string
		{
			$input = '<input type="text" name="'.rex_escape($name).'" id="'.rex_escape($id).'" class="form-control" data-colorpicker'.($mode !== 'solid' ? ' data-colorpicker-mode="'.rex_escape($mode).'"' : '').' placeholder="'.rex_escape($placeholder).'" value="'.rex_escape($value).'">';
			if ($palette === []) {
				return $input;
			}

			$other = ($role === 'fg') ? 'bg' : 'fg';
			$options = '';
			$matched = false;
			foreach ($palette as $entry) {
				$cell = (string) $entry[$role];
				if ($cell === '') {
					continue;
				}
				$optionValue = '@'.$entry['key'];
				$selected = (!$matched && $value === $optionValue) ? ' selected="selected"' : '';
				$matched = $matched || $selected !== '';
				$options .= '<option value="'.rex_escape($optionValue).'" data-gb-bg="'.rex_escape($cell).'" data-gb-fg="'.rex_escape((string) $entry[$other]).'"'.$selected.'>'.rex_escape((string) $entry['label']).'</option>';
			}
			$alt = ($value !== '' && $value[0] === '@' && !$matched);
			$ind = ($value !== '' && $value[0] !== '@');
			$head = '<option value=""'.($value === '' ? ' selected="selected"' : '').'>- keine Farbe -</option>';
			if ($alt) {
				$head .= '<option value="'.rex_escape($value).'" data-gb-alt="1" selected="selected">Alt: '.rex_escape(substr($value, 1)).'</option>';
			}
			$options .= '<option value="__ind__" data-gb-ind="1"'.($ind ? ' selected="selected"' : '').'>Individuell</option>';

			return '<div data-gb-palette-wrap><select class="selectpicker form-control" data-gb-palette data-gb-palette-field data-live-search="true" data-width="100%">'.$head.$options.'</select>'
				.'<div data-gb-palette-ind'.($ind ? '' : ' style="display:none"').'>'.$input.'</div></div>';
		}
	}
}
