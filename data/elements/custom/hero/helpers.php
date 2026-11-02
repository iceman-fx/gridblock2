<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Hilfsklasse des Custom-Elements "Hero"
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

if (!class_exists('GbHeroKit')) {
	class GbHeroKit
	{
		public const LAYOUTS = [
			'nebeneinander' => 'Text und Bild nebeneinander',
			'zentriert' => 'Zentriert, Bild über oder unter dem Text',
			'vollflaechig' => 'Vollflächiges Bild oder Video',
			'karten' => 'Text mit Bild & Infokarten',
		];

		public const RATIOS = [
			'1-1' => '1:1 (Quadrat)',
			'16-9' => '16:9 (Breitbild)',
			'21-9' => '21:9 (Panorama)',
			'3-1' => '3:1 (Panorama, sehr flach)',
			'4-3' => '4:3',
			'3-2' => '3:2',
			'8-5' => '8:5',
			'5-8' => '5:8 (Hochformat)',
			'2-3' => '2:3 (Hochformat)',
			'3-4' => '3:4 (Hochformat)',
			'4-5' => '4:5 (Hochformat)',
		];

		//Abstand des Textes zum Rand bei "Vollflaechig": Schluessel => Bezeichnung (die Werte stehen in style.css)
		public const INSETS = [
			'standard' => 'Standard',
			'gross' => 'Groß',
			'sehr-gross' => 'Sehr groß',
		];

		public const OVERLAYS = [
			'keine' => 'Keine',
			'leicht' => 'Leicht',
			'mittel' => 'Mittel',
			'stark' => 'Stark',
			'verlauf' => 'Verlauf von unten',
			'verlauf-ecke' => 'Verlauf von links unten',
			'verlauf-ecke-rechts' => 'Verlauf von rechts unten',
		];

		//Maximale Breite des Heros (Pixel => Bezeichnung); ohne Auswahl keine Begrenzung.
		public const MAX_WIDTHS = [
			'1440' => 'Breit (1440 px)',
			'1200' => 'Normal (1200 px)',
			'960' => 'Schmal (960 px)',
		];

		//Ausrichtung des Bildes (Bildausschnitt): Schluessel => [Bezeichnung, CSS object-position]
		public const FOCUS = [
			'mitte' => ['Mitte (Standard)', 'center'],
			'oben-links' => ['Links oben', 'left top'],
			'oben' => ['Mitte oben', 'center top'],
			'oben-rechts' => ['Rechts oben', 'right top'],
			'links' => ['Links mittig', 'left center'],
			'rechts' => ['Rechts mittig', 'right center'],
			'unten-links' => ['Links unten', 'left bottom'],
			'unten' => ['Mitte unten', 'center bottom'],
			'unten-rechts' => ['Rechts unten', 'right bottom'],
		];

		//Sprachen fuer die Untertitel-Auswahl: Code => Bezeichnung
		public const SUBTITLE_LANGS = [
			'de' => 'Deutsch',
			'en' => 'English',
			'fr' => 'Français',
			'it' => 'Italiano',
			'es' => 'Español',
			'sl' => 'Slovenščina',
		];

		public const MAX_BUTTONS = 3;

		public const EFFECTS = [
			'' => '- keine -',
			'vergroessern' => 'Vergrößern',
			'pulsieren' => 'Pulsieren',
			'huepfen' => 'Hüpfen',
			'gummiband' => 'Gummiband',
			'schweben' => 'Schweben',
			'fuellung-links' => 'Füllung von links',
			'glanz' => 'Glanzstreifen',
			'welle' => 'Welle',
			'unterstreichen' => 'Unterstreichung',
		];

		public static function effect(string $value): string
		{
			return array_key_exists($value, self::EFFECTS) ? $value : '';
		}
		public const MAX_CARDS = 3;

		public static function pick(string $value, array $allowed, string $default): string
		{
			return in_array($value, $allowed, true) ? $value : $default;
		}

		public static function href(string $linkJson): string
		{
			$data = json_decode($linkJson, true) ?: [];
			$value = (string) ($data['value'] ?? '');

			return match ($data['type'] ?? '') {
				'tel', 'email', 'url' => (string) ($data['href'] ?? ''),
				'link' => ((int) $value > 0) ? rex_getUrl((int) $value) : '',
				'media' => ($value !== '') ? rex_url::media($value) : '',
				default => '',
			};
		}

		public static function mediaUrl(string $file): string
		{
			return rex_url::media($file);
		}

		public static function mediaAlt(string $file): string
		{
			if ($file === '' || !class_exists('rex_media')) {
				return '';
			}
			$media = rex_media::get($file);

			return $media ? trim((string) $media->getValue('med_description')) : '';
		}

		//*Wort* in der Ueberschrift hervorheben; $color optional (Farbwert), sonst Standard aus dem CSS
		public static function heading(string $text, string $color): string
		{
			$html = rex_escape($text);
			$style = ($color !== '') ? ' style="color:'.rex_escape($color).';"' : '';

			return (string) preg_replace('/\*([^*\r\n]+)\*/u', '<span class="gb-el-hero-accent"'.$style.'>$1</span>', $html);
		}

		//*Wort* im Kartentext fett darstellen
		public static function bold(string $text): string
		{
			return (string) preg_replace('/\*([^*\r\n]+)\*/u', '<strong>$1</strong>', rex_escape($text));
		}

		public const BLOCK_POSITIONS = [
			'oben-links' => ['Oben links', 'links', 'oben'],
			'oben' => ['Oben mitte', 'mitte', 'oben'],
			'oben-rechts' => ['Oben rechts', 'rechts', 'oben'],
			'links' => ['Mitte links', 'links', 'mitte'],
			'mitte' => ['Mitte', 'mitte', 'mitte'],
			'rechts' => ['Mitte rechts', 'rechts', 'mitte'],
			'unten-links' => ['Unten links', 'links', 'unten'],
			'unten' => ['Unten mitte', 'mitte', 'unten'],
			'unten-rechts' => ['Unten rechts', 'rechts', 'unten'],
		];

		public static function resolveColor(string $value, string $role): string
		{
			$value = trim($value);
			if ($value === '' || $value[0] !== '@') {
				return $value;
			}
			$entry = self::palette()[substr($value, 1)] ?? null;

			return $entry ? (string) ($entry[$role] ?? '') : '';
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

		public static function palette(): array
		{
			return class_exists('Gridblock2Palette') ? Gridblock2Palette::getEntries() : [];
		}

		public static function cssValue(string $raw): string
		{
			return (class_exists('Gridblock2Palette') ? Gridblock2Palette::normalizeValue($raw) : null) ?? '';
		}

		public static function accentFrom(string $bgcolor): string
		{
			if ($bgcolor === '' || stripos($bgcolor, 'gradient(') === false) {
				return $bgcolor;
			}

			return preg_match('/#[0-9a-f]{3,8}\b|rgba?\([^)]*\)|hsla?\([^)]*\)/i', $bgcolor, $first) ? $first[0] : '';
		}

		public static function hoverColors(string $stil, string $hbg, string $hfg, string $bg, string $fg, bool $autoSwap): ?array
		{
			if ($stil === 'link') {
				return null;
			}
			$fill = $stil === 'fill';
			if ($hbg !== '' || $hfg !== '') {
				return [
					'hbg' => $hbg !== '' ? $hbg : ($fill ? 'var(--gb-hero-btn-bg, linear-gradient(135deg, var(--gb-hero-btn-a), var(--gb-hero-btn-b)))' : 'transparent'),
					'hfg' => $hfg !== '' ? $hfg : ($fill ? 'var(--gb-hero-btn-fg, #fff)' : 'var(--gb-hero-btn-accent)'),
					'hbd' => (!$fill && $hbg !== '' && $hbg !== 'transparent') ? $hbg : '',
					'ring' => false,
				];
			}
			if (!$autoSwap || $bg === '' || $bg === 'transparent') {
				return null;
			}

			return $fill
				? ['hbg' => $fg !== '' ? $fg : '#fff', 'hfg' => $bg, 'hbd' => '', 'ring' => true]
				: ['hbg' => $bg, 'hfg' => $fg !== '' ? $fg : 'var(--gb-hero-btn-on)', 'hbd' => $bg, 'ring' => false];
		}

		public static function hoverAttrs(?array $hover): array
		{
			if ($hover === null) {
				return ['style' => '', 'class' => ''];
			}
			$style = '--gb-hero-btn-hbg:'.rex_escape($hover['hbg']).';--gb-hero-btn-hfg:'.rex_escape($hover['hfg']).';';
			if ($hover['hbd'] !== '') {
				$style .= '--gb-hero-btn-hbd:'.rex_escape($hover['hbd']).';';
			}

			return ['style' => $style, 'class' => ' gb-el-hero-btn-hov'.($hover['ring'] ? ' gb-el-hero-btn-ring' : '')];
		}

		public static function paletteAttrs(array $entry, string $stil): ?array
		{
			$class = (string) ($entry['class'] ?? '');
			if ($class !== '' && $stil !== 'fill') {
				if ((string) $entry['bg'] === '') {
					return null;
				}
				$class = '';
			}
			if ($class !== '') {
				$shown = ((string) $entry['bg'] !== '' && (string) $entry['bg'] !== 'transparent') ? (string) $entry['bg'] : (string) $entry['fg'];

				$style = ($shown !== '') ? '--gb-hero-btn-accent:'.rex_escape($shown).';' : '';
				$hbgCell = (string) $entry['hbg'];
				$hfgCell = (string) $entry['hfg'];
				$hoverClass = '';
				if (($hbgCell !== '' || $hfgCell !== '') && ($hbgCell !== '' || (string) $entry['bg'] !== '')) {
					$hover = self::hoverAttrs([
						'hbg' => $hbgCell !== '' ? $hbgCell : (string) $entry['bg'],
						'hfg' => $hfgCell !== '' ? $hfgCell : ((string) $entry['fg'] !== '' ? (string) $entry['fg'] : 'inherit'),
						'hbd' => '',
						'ring' => false,
					]);
					$style .= $hover['style'];
					$hoverClass = $hover['class'];
				}
				if ((string) $entry['fg'] !== '') {
					$style .= ($hoverClass !== '' ? '--gb-hero-btn-fg:' : 'color:').rex_escape((string) $entry['fg']).';';
				}

				return ['style' => $style, 'class' => ' '.rex_escape($class).$hoverClass];
			}

			$bg = (string) $entry['bg'];
			$fg = (string) $entry['fg'];
			$accent = $bg !== 'transparent' ? $bg : $fg;
			$style = '--gb-hero-btn-bg:'.rex_escape($bg).';';
			if ($fg !== '') {
				$style .= '--gb-hero-btn-fg:'.rex_escape($fg).';';
			}
			if ($accent !== '') {
				$style .= '--gb-hero-btn-accent:'.rex_escape($accent).';';
			}
			$hover = self::hoverAttrs(self::hoverColors($stil, (string) $entry['hbg'], (string) $entry['hfg'], $bg, $fg, true));

			return ['style' => $style.$hover['style'], 'class' => ' gb-el-hero-btn-pal'.$hover['class']];
		}

		public static function individualAttrs(array $row, string $stil, string $bgcolor, string $textcolor): ?array
		{
			$hover = self::hoverColors($stil, self::cssValue((string) ($row['hbgcolor'] ?? '')), self::cssValue((string) ($row['htextcolor'] ?? '')), $bgcolor, $textcolor, false);
			if ($hover === null) {
				return null;
			}
			$style = '';
			if ($stil === 'fill') {
				if ($bgcolor !== '') {
					$style .= '--gb-hero-btn-bg:'.rex_escape($bgcolor).';--gb-hero-btn-accent:'.rex_escape(self::accentFrom($bgcolor)).';';
				}
				if ($textcolor !== '') {
					$style .= '--gb-hero-btn-fg:'.rex_escape($textcolor).';';
				}
			} elseif ($textcolor !== '') {
				$style .= '--gb-hero-btn-accent:'.rex_escape($textcolor).';';
			}
			$attrs = self::hoverAttrs($hover);

			return ['style' => $style.$attrs['style'], 'class' => ' gb-el-hero-btn-pal'.$attrs['class']];
		}

		public static function buttons(array $rows, string $effect = ''): string
		{
			$out = '';
			$count = 0;
			$palette = self::palette();
			foreach ($rows as $row) {
				$row = (array) $row;
				$title = trim((string) ($row['titel'] ?? ''));
				if (!empty($row['disabled']) || $title === '') {
					continue;
				}
				if (++$count > self::MAX_BUTTONS) {
					break;
				}

				$href = self::href((string) ($row['link'] ?? ''));
				$newTab = ($href !== '') && !empty($row['newtab']);
				$style = self::pick((string) ($row['stil'] ?? 'fill'), ['fill', 'outline', 'link'], 'fill');
				$icon = trim((string) ($row['icon'] ?? ''));
				$iconRight = ((string) ($row['iconposition'] ?? 'links')) === 'rechts';

				$inline = '';
				$classes = '';
				$bg = trim((string) ($row['bgcolor'] ?? ''));
				$fg = trim((string) ($row['textcolor'] ?? ''));

				$farbe = (string) ($row['farbe'] ?? '');
				$paletteKey = ($palette !== [] && $farbe !== '' && $farbe !== 'ind');
				$attrs = null;
				if ($paletteKey) {
					if (isset($palette[$farbe])) {
						$attrs = self::paletteAttrs($palette[$farbe], $style);
					}
				} else {
					$attrs = self::individualAttrs($row, $style, $bg, $fg);
				}

				if (!($paletteKey && $attrs !== null)) {
					$classes .= ' gb-el-hero-btn-default';
				}

				if ($attrs !== null) {
					$inline .= $attrs['style'];
					$classes .= $attrs['class'];
				} elseif (!$paletteKey) {
					if ($bg !== '' && $style === 'fill') {
						$inline .= 'background:'.rex_escape($bg).';';
					}
					if ($fg !== '' && !($effect !== '' && $style !== 'fill')) {
						$inline .= 'color:'.rex_escape($fg).';';
					}
					if ($effect !== '') {
						$accent = '';
						if ($style === 'fill' && $bg !== '') {
							$accent = self::accentFrom($bg);
						} elseif ($style !== 'fill' && $fg !== '') {
							$accent = $fg;
						}
						if ($accent !== '') {
							$inline .= '--gb-hero-btn-accent:'.rex_escape($accent).';';
						}
					}
				}

				$iconHtml = ($icon !== '') ? '<i class="'.rex_escape($icon).'" aria-hidden="true"></i> ' : '';
				$inner = $iconRight ? rex_escape($title).' '.$iconHtml : $iconHtml.rex_escape($title);

				$label = trim((string) ($row['linkdesc'] ?? ''));
				if ($newTab) {
					$label = ($label !== '' ? $label : $title).' (öffnet in neuem Tab)';
				}

				$tag = ($href !== '') ? 'a' : 'span';
				$attributes = ($href !== '') ? ' href="'.rex_escape($href).'"' : '';
				$attributes .= $newTab ? ' target="_blank" rel="noopener"' : '';
				$attributes .= ($href !== '' && $label !== '') ? ' aria-label="'.rex_escape($label).'"' : '';
				$attributes .= ($inline !== '') ? ' style="'.$inline.'"' : '';

				$out .= '<'.$tag.' class="gb-el-hero-btn gb-el-hero-btn-'.$style.$classes.'"'.$attributes.'>'.$inner.'</'.$tag.'>';
			}

			return $out;
		}

		public static function buttonColorsMarkup(array $palette): string
		{
			$own = '<div class="gbh-pair"><div class="rex-form-group form-group" data-gbh-row="fill"><label>Hintergrundfarbe</label>'
				.'<input type="text" name="bgcolor" value="{{bgcolor}}" class="form-control" data-colorpicker data-colorpicker-mode="both" placeholder="#rrggbb oder rgba(…)"></div>'
				.'<div class="rex-form-group form-group"><label>Textfarbe <small class="text-muted">(bei „Umrandet“ auch Rahmenfarbe)</small></label>'
				.'<input type="text" name="textcolor" value="{{textcolor}}" class="form-control" data-colorpicker placeholder="#rrggbb oder rgba(…)"></div></div>';
			$hover = '<div class="gbh-pair"><div class="rex-form-group form-group"><label>Hover-Hintergrund <small class="text-muted">(optional)</small></label>'
				.'<input type="text" name="hbgcolor" value="{{hbgcolor}}" class="form-control" data-colorpicker placeholder="#rrggbb oder rgba(…)"></div>'
				.'<div class="rex-form-group form-group"><label>Hover-Text <small class="text-muted">(optional)</small></label>'
				.'<input type="text" name="htextcolor" value="{{htextcolor}}" class="form-control" data-colorpicker placeholder="#rrggbb oder rgba(…)"></div></div>';
			if ($palette === []) {
				return $own.'<details class="gbh-hoverbox" data-gbh-row="fill outline"><summary>Hover-Farben <small class="text-muted">(optional)</small></summary>'.$hover.'</details>';
			}

			$options = '';
			foreach ($palette as $entry) {
				$options .= '<option value="'.rex_escape($entry['key']).'" data-gb-bg="'.rex_escape($entry['bg']).'" data-gb-fg="'.rex_escape($entry['fg']).'">'.rex_escape($entry['label']).'</option>';
			}
			$options .= '<option value="ind" data-gb-ind="1">Individuell</option>';

			return '<div class="gbh-pair gbh-colorpair"><div class="rex-form-group form-group"><label>Farbe</label>'
				.'<select name="farbe" class="selectpicker form-control" data-gb-palette data-live-search="true" data-width="100%">'.$options.'</select></div></div>'
				.'<div data-gbh-ind>'.$own.'<div data-gbh-row="fill outline">'.$hover.'</div></div>';
		}

		//Zeilenmarkup des Button-Repeaters im Editor (Felder wie beim CTA-Element)
		public static function buttonRowMarkup(array $palette = []): string
		{
			$markup = <<<'HTML'
				<div class="gbh-row">
					<div class="gbh-pair">
						<div class="rex-form-group form-group">
							<label>Titel <span class="text-danger">*</span></label>
							<input type="text" name="titel" value="{{titel}}" class="form-control">
						</div>
						<div class="rex-form-group form-group">
							<label>Stil</label>
							<select name="stil" class="form-control">
								<option value="fill">Gefüllt</option>
								<option value="outline">Umrandet</option>
								<option value="link">Textlink</option>
							</select>
						</div>
					</div>
					<div class="gbh-pair">
						<div class="rex-form-group form-group">
							<label>Link</label>
							REX_MULTILINK[id="link"]
							<div class="checkbox"><label><input type="checkbox" name="newtab" value="checked" {{newtab|checked}}> Link in neuem Fenster öffnen</label></div>
						</div>
						<div class="rex-form-group form-group">
							<label>Linkbeschreibung <small class="text-muted">(optional)</small></label>
							<input type="text" name="linkdesc" value="{{linkdesc}}" class="form-control" placeholder="z. B. Beratungstermin vereinbaren">
							<p class="help-block small">Für Screenreader, ersetzt den Titel (Leer = Titel). Bei neuem Fenster wird „(öffnet in neuem Tab)“ ergänzt.</p>
						</div>
					</div>
					<div class="gbh-pair">
						<div class="rex-form-group form-group">
							<label>Fontawesome-Icon <small class="text-muted">(optional)</small></label>
							<input type="text" name="icon" value="{{icon}}" class="form-control rex-fa-iconpicker" data-fa-add-weight="1" data-fa-preview-weight="S" data-fa-onselect="gb2HeroIconPicked" placeholder="z. B. fas fa-arrow-right">
						</div>
						<div class="rex-form-group form-group">
							<label>Position Icon</label>
							<select name="iconposition" class="form-control">
								<option value="links">Links</option>
								<option value="rechts">Rechts</option>
							</select>
						</div>
					</div>
						<!--GBH_COLORS-->
				</div>
				HTML;

			return str_replace('<!--GBH_COLORS-->', self::buttonColorsMarkup($palette), $markup);
		}

		//Zeilenmarkup des Karten-Repeaters im Editor (Feldnamen kbild/ktext: gleiche Namen wie die Hauptfelder bild/text wuerden sich beim Speichern ueberschreiben)
		public static function cardRowMarkup(): string
		{
			return <<<'HTML'
				<div class="rex-form-group form-group">
					<label>Bild / Icon <small class="text-muted">(optional)</small></label>
					REX_MEDIA[id="kbild" types="jpg,jpeg,png,webp,avif,gif,svg"]
				</div>
				<div class="rex-form-group form-group">
					<label>Text</label>
					<textarea name="ktext" rows="3" class="form-control">{{ktext}}</textarea>
					<p class="help-block small">*Wort* wird fett dargestellt.</p>
				</div>
				HTML;
		}
	}
}
