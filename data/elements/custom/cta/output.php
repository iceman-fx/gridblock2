<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

$values = (array) ($_gb->values ?? []);
require_once __DIR__.'/helpers.php';

$legacy = !array_key_exists('buttons', $values);
if ($legacy) {
	$rows = [[
		'titel' => $values['titel'] ?? '',
		'icon' => $values['icon'] ?? '',
		'iconposition' => $values['iconposition'] ?? 'links',
		'link' => $values['link'] ?? '',
		'bgcolor' => $values['bgcolor'] ?? '',
		'textcolor' => $values['textcolor'] ?? '',
	]];
} else {
	$rows = json_decode((string) $values['buttons'], true);
	$rows = is_array($rows) ? $rows : [];
}

$align = (string) ($values['align'] ?? 'links');
$align = in_array($align, ['mitte', 'rechts'], true) ? $align : 'links';
$radius = (string) ($values['radius'] ?? 'pille');
$radius = in_array($radius, ['abgerundet', 'eckig'], true) ? $radius : 'pille';

$wrapperClass = 'gb-el-cta-align gb-el-cta-align-'.$align.' gb-el-cta-radius-'.$radius;
if (!$legacy && in_array((string) ($values['shadow'] ?? ''), ['', 'aus'], true)) {
	$wrapperClass .= ' gb-el-cta-shadow-off';
}
if (!empty($values['full'])) {
	$wrapperClass .= ' gb-el-cta-full';
}
$effect = GbCtaKit::effect((string) ($values['effect'] ?? ''));
if ($effect !== '') {
	$wrapperClass .= ' gb-el-cta-fx-'.$effect;
}

$buttons = '';
$palette = GbCtaKit::palette();
foreach ($rows as $row) {
	$row = (array) $row;
	$titel = trim((string) ($row['titel'] ?? ''));
	if (!empty($row['disabled']) || $titel === '') {
		continue;
	}

	$linkData = json_decode((string) ($row['link'] ?? ''), true) ?: [];
	$linkType = (string) ($linkData['type'] ?? '');
	$linkValue = (string) ($linkData['value'] ?? '');
	$href = match ($linkType) {
		'tel', 'email', 'url' => (string) ($linkData['href'] ?? ''),
		'link' => ((int) $linkValue > 0) ? rex_getUrl((int) $linkValue) : '',
		'media' => ($linkValue !== '') ? rex_url::media($linkValue) : '',
		default => '',
	};
	$newTab = ($href !== '') && ($legacy ? $linkType === 'url' : !empty($row['newtab']));

	$stil = (string) ($row['stil'] ?? 'fill');
	$stil = in_array($stil, ['outline', 'link'], true) ? $stil : 'fill';

	$icon = trim((string) ($row['icon'] ?? ''));
	$iconposition = ((string) ($row['iconposition'] ?? 'links')) === 'rechts' ? 'rechts' : 'links';

	$style = '';
	$classes = '';
	$bgcolor = trim((string) ($row['bgcolor'] ?? ''));
	$textcolor = trim((string) ($row['textcolor'] ?? ''));

	$farbe = (string) ($row['farbe'] ?? '');
	$paletteKey = ($palette !== [] && $farbe !== '' && $farbe !== 'ind');
	$attrs = null;
	if ($paletteKey) {
		if (isset($palette[$farbe])) {
			$attrs = GbCtaKit::paletteAttrs($palette[$farbe], $stil);
		}
	} else {
		$attrs = GbCtaKit::individualAttrs($row, $stil, $bgcolor, $textcolor);
	}

	if (!($paletteKey && $attrs !== null)) {
		$classes .= ' gb-el-cta-default';
	}

	if ($attrs !== null) {
		$style .= $attrs['style'];
		$classes .= $attrs['class'];
	} elseif (!$paletteKey) {
		if ($bgcolor !== '' && $stil === 'fill') {
			$style .= 'background:'.rex_escape($bgcolor).';';
		}
		//Umrandet/Textlink mit Effekt: die Textfarbe kommt ueber --cta-accent, damit der Effekt die Textfarbe beim Hover aendern kann
		if ($textcolor !== '' && !($effect !== '' && $stil !== 'fill')) {
			$style .= 'color:'.rex_escape($textcolor).';';
		}

		if ($effect !== '') {
			$accent = '';
			if ($stil === 'fill' && $bgcolor !== '') {
				$accent = (stripos($bgcolor, 'gradient(') === false) ? $bgcolor : (preg_match('/#[0-9a-f]{3,8}\b|rgba?\([^)]*\)|hsla?\([^)]*\)/i', $bgcolor, $firstColor) ? $firstColor[0] : '');
			} elseif ($stil !== 'fill' && $textcolor !== '') {
				$accent = $textcolor;
			}
			if ($accent !== '') {
				$style .= '--cta-accent:'.rex_escape($accent).';';
			}
		}
	}

	$iconHtml = ($icon !== '') ? '<i class="'.rex_escape($icon).'" aria-hidden="true"></i> ' : '';
	$innerHtml = ($iconposition === 'rechts')
		? rex_escape($titel).' '.$iconHtml
		: $iconHtml.rex_escape($titel);

	$label = trim((string) ($row['linkdesc'] ?? ''));
	if ($newTab) {
		$label = ($label !== '' ? $label : $titel).' (öffnet in neuem Tab)';
	}

	$tag = ($href !== '') ? 'a' : 'span';
	$attributes = ($href !== '') ? ' href="'.rex_escape($href).'"' : '';
	$attributes .= $newTab ? ' target="_blank" rel="noopener"' : '';
	$attributes .= ($href !== '' && $label !== '') ? ' aria-label="'.rex_escape($label).'"' : '';
	$attributes .= ($style !== '') ? ' style="'.$style.'"' : '';

	$buttons .= "\t".'<'.$tag.' class="gb-el-cta gb-el-cta-style-'.$stil.' gb-el-cta-icon-'.$iconposition.$classes.'"'.$attributes.'>'.$innerHtml.'</'.$tag.'>'."\n";
}

if ($buttons === '') {
	return;
}

$faLink = '';
if (rex::isBackend() && rex_addon::get('fa_iconpicker')->isAvailable() && class_exists('rex_fa_iconpicker') && rex_fa_iconpicker::getCssPath() !== null) {
	$faLink = '<link rel="stylesheet" href="'.rex_escape(rtrim(rex::getServer(), '/').'/index.php?rex_media_type='.rex_i18n::msg('fa_iconpicker_mm_name').'&rex_media_file='.rex_fa_iconpicker::getActiveCssFileName()).'">'."\n";
}
?>
<?php echo $faLink; ?><div class="<?php echo $wrapperClass; ?>">
<?php echo $buttons; ?>
</div>
