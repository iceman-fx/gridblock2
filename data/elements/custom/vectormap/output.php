<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */
$v = $_gb->values;
$location = trim((string) ($v['location'] ?? ''));
if ($location === '' || !str_contains($location, ',')) {
	return;
}

[$lat, $lng] = array_map('trim', explode(',', $location, 2));
$zoom = max(1, min(20, (int) ($v['zoom'] ?? 14)));
$height = trim((string) ($v['height'] ?? '420')) ?: '420';
$mapStyle = in_array((string) ($v['map_style'] ?? ''), ['liberty', 'bright', 'positron', 'satellite'], true) ? (string) $v['map_style'] : 'liberty';
$theme = trim((string) ($v['theme'] ?? ''));
$controlsPosition = in_array((string) ($v['controls_position'] ?? ''), ['top-right', 'top-left', 'bottom-right', 'bottom-left'], true) ? (string) $v['controls_position'] : 'top-right';
$infoHtml = trim((string) ($v['info_html'] ?? ''));

$popupParts = [];
if (trim((string) ($v['popup_title'] ?? '')) !== '') {
	$popupParts[] = '<strong>'.rex_escape(trim((string) $v['popup_title'])).'</strong>';
}
if (trim((string) ($v['popup_text'] ?? '')) !== '') {
	$popupParts[] = nl2br(rex_escape(trim((string) $v['popup_text'])));
}

$markers = json_encode([['lat' => $lat, 'lng' => $lng, 'popup' => implode('<br>', $popupParts)]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$flag = static fn (string $key): bool => ((string) ($v[$key] ?? '')) === 'checked';
?>
<div class="gb-el-vectormap" data-location="<?php echo rex_escape($location); ?>">
	<vector-map
		center="<?php echo rex_escape($location); ?>"
		zoom="<?php echo $zoom; ?>"
		controls-position="<?php echo rex_escape($controlsPosition); ?>"
		height="<?php echo rex_escape($height); ?>"
		map-style="<?php echo rex_escape($mapStyle); ?>"
		<?php echo ($theme !== '') ? 'theme="'.rex_escape($theme).'"' : ''; ?>
		<?php echo ($infoHtml !== '') ? 'info-html="'.rex_escape($infoHtml).'" info-position="top-left" info-closable' : ''; ?>
		<?php echo $flag('locate') ? 'locate' : ''; ?>
		<?php echo $flag('show_satellite') ? 'show-satellite' : ''; ?>
		<?php echo $flag('fullscreen') ? 'fullscreen' : ''; ?>
		markers='<?php echo rex_escape((string) $markers); ?>'>
	</vector-map>
</div>
