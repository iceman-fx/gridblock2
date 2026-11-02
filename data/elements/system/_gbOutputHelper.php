<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Hilfsklasse der System-Elemente (Sektion, Spalten-Zeile, Spalte)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)

	setzt die Contentsettings-Werte einer Ebene
	in CSS-Klassen und einen Style um. Die Klassen (Abstände, Hintergrund) stehen in column/style.css.
	Liegt im data-Ordner und kann dort projektspezifisch angepasst werden.
*/

if (!class_exists('GbOutputKit')) {
	class GbOutputKit
	{
		private const CLASS_KEYS = ['bgColor|class', 'paddingTop', 'paddingBottom', 'marginTop', 'marginBottom', 'bgImagePos', 'class'];

		//$scope: 'section', 'template' (Spalten-Zeile) oder 'column' (mit Spaltennummer $col)
		//Rückgabe: ['class' => bereits maskierte Klassenliste, 'style' => Style-Wert ohne style=""]
		public static function attributes(object $cs, string $scope, int $col = 0): array
		{
			$empty = ['class' => '', 'style' => ''];
			if (!(rex::isFrontend() || rex_gridblock2::isBuilderPreview())) {
				return $empty;
			}

			$key = ($scope === 'column') ? 'column_'.$col : $scope;
			$values = $cs->$key ?? null;
			if (!is_object($values)) {
				return $empty;
			}

			$class = match ($scope) {
				'section' => rex_gridblock2_helper::getSectionClasses($cs, self::CLASS_KEYS),
				'template' => rex_gridblock2_helper::getTemplateClasses($cs, self::CLASS_KEYS),
				default => rex_gridblock2_helper::getColumnClasses($cs, $col, self::CLASS_KEYS),
			};

			$image = self::mediaFile((string) ($values->bgImage ?? ''));
			if ($image !== '') {
				$class = trim($class.' gb-bg-image');
			}

			return ['class' => $class, 'style' => self::background((string) ($values->bgColor ?? ''), $image)];
		}

		private static function mediaFile(string $file): string
		{
			return preg_match('/^[\w.\-]+$/', $file) ? $file : '';
		}

		private static function background(string $color, string $image): string
		{
			$color = trim($color);
			if ($color !== '' && (Gridblock2Palette::isClassValue($color) || !preg_match('/^[#\w(),.%\s\/\-]+$/', $color))) {
				$color = '';
			}

			$isGradient = $color !== '' && stripos($color, 'gradient(') !== false;
			$layers = [];
			if ($isGradient) {
				$layers[] = $color;
			}
			if ($image !== '') {
				$layers[] = "url('".rex_url::media($image)."')";
			}

			$style = ($color !== '' && !$isGradient) ? 'background-color:'.$color.';' : '';
			$style .= $layers ? 'background-image:'.implode(',', $layers).';' : '';

			return $style;
		}
	}
}
