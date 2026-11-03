<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Hilfsklasse des Custom-Elements "Call-to-Action Button" (Hover-Effekte)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

if (!class_exists('GbCtaKit')) {
	class GbCtaKit
	{
		//Hover-Effekte: Schluessel => Bezeichnung (leer = Standard-Hover aus style.css)
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
					'hbg' => $hbg !== '' ? $hbg : ($fill ? 'var(--cta-bg, linear-gradient(135deg, var(--cta-fill-a), var(--cta-fill-b)))' : 'transparent'),
					'hfg' => $hfg !== '' ? $hfg : ($fill ? 'var(--cta-fg, #fff)' : (($hbg !== '' && $hbg !== 'transparent') ? (($autoSwap && $fg !== '') ? $fg : 'var(--cta-on-accent)') : 'var(--cta-accent)')),
					'hbd' => (!$fill && $hbg !== '' && $hbg !== 'transparent') ? $hbg : '',
					'ring' => false,
				];
			}
			if (!$autoSwap || $bg === '' || $bg === 'transparent') {
				return null;
			}

			return $fill
				? ['hbg' => $fg !== '' ? $fg : '#fff', 'hfg' => $bg, 'hbd' => '', 'ring' => true]
				: ['hbg' => $bg, 'hfg' => $fg !== '' ? $fg : 'var(--cta-on-accent)', 'hbd' => $bg, 'ring' => false];
		}

		public static function hoverAttrs(?array $hover): array
		{
			if ($hover === null) {
				return ['style' => '', 'class' => ''];
			}
			$style = '--cta-hbg:'.rex_escape($hover['hbg']).';--cta-hfg:'.rex_escape($hover['hfg']).';';
			if ($hover['hbd'] !== '') {
				$style .= '--cta-hbd:'.rex_escape($hover['hbd']).';';
			}

			return ['style' => $style, 'class' => ' gb-el-cta-hov'.($hover['ring'] ? ' gb-el-cta-ring' : '')];
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

				$style = ($shown !== '') ? '--cta-accent:'.rex_escape($shown).';' : '';
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
					$style .= ($hoverClass !== '' ? '--cta-fg:' : 'color:').rex_escape((string) $entry['fg']).';';
				}

				return ['style' => $style, 'class' => ' '.rex_escape($class).$hoverClass];
			}

			$bg = (string) $entry['bg'];
			$fg = (string) $entry['fg'];
			$accent = $bg !== 'transparent' ? $bg : $fg;
			$style = '--cta-bg:'.rex_escape($bg).';';
			if ($fg !== '') {
				$style .= '--cta-fg:'.rex_escape($fg).';';
			}
			if ($accent !== '') {
				$style .= '--cta-accent:'.rex_escape($accent).';';
			}
			$hover = self::hoverAttrs(self::hoverColors($stil, (string) $entry['hbg'], (string) $entry['hfg'], $bg, $fg, true));

			return ['style' => $style.$hover['style'], 'class' => ' gb-el-cta-pal'.$hover['class']];
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
					$style .= '--cta-bg:'.rex_escape($bgcolor).';--cta-accent:'.rex_escape(self::accentFrom($bgcolor)).';';
				}
				if ($textcolor !== '') {
					$style .= '--cta-fg:'.rex_escape($textcolor).';';
				}
			} elseif ($textcolor !== '') {
				$style .= '--cta-accent:'.rex_escape($textcolor).';';
			}
			$attrs = self::hoverAttrs($hover);

			return ['style' => $style.$attrs['style'], 'class' => ' gb-el-cta-pal'.$attrs['class']];
		}
	}
}
