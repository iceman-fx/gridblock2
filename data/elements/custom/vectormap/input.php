<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Eingabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */
$uid = $_gb->uid;
$v = $_gb->values;
$val = static fn (string $key, string $default = ''): string => (string) ($v[$key] ?? $default);

$mapStyle = $val('map_style', 'liberty');
$theme = $val('theme');
$zoom = max(1, min(20, (int) $val('zoom', '14')));
$controlsPosition = $val('controls_position', 'top-right');

$zoomHints = [1 => 'Welt', 3 => 'Kontinent', 5 => 'Land', 8 => 'Region', 11 => 'Stadt', 14 => 'Stadtteil', 16 => 'Straßen', 18 => 'Gebäude', 20 => 'maximal'];
$zoomChoices = [];
for ($i = 1; $i <= 20; $i++) {
	$zoomChoices[$i] = isset($zoomHints[$i]) ? $i.' - '.$zoomHints[$i] : (string) $i;
}
$positionChoices = ['top-right' => 'Oben rechts', 'top-left' => 'Oben links', 'bottom-right' => 'Unten rechts', 'bottom-left' => 'Unten links'];
$styleChoices = ['liberty' => 'Liberty', 'bright' => 'Bright', 'positron' => 'Positron', 'satellite' => 'Satellite'];
$hasPicker = class_exists('\KLXM\VectorMaps\Picker\PickerWidget');
$hasThemes = class_exists('\KLXM\VectorMaps\ThemeManager');
$themeChoices = $hasThemes
	? \KLXM\VectorMaps\ThemeManager::getThemeChoices(true)
	: ['' => 'Kein Theme', 'dark' => 'Dark', 'warm' => 'Warm', 'mono' => 'Mono'];
?>
<script>
(function () {
	if (window.gbVectorMapSync) { return; }
	window.gbVectorMapSync = true;
	document.addEventListener('change', function (e) {
		var sel = e.target.closest ? e.target.closest('select[data-gb-vm-sync]') : null;
		if (!sel) { return; }
		var scope = sel.closest('.gb-builder-block-form') || sel.closest('.modal-body') || document;
		var input = scope.querySelector('input[data-vector-picker]');
		if (input) { input.setAttribute(sel.getAttribute('data-gb-vm-sync') === 'style' ? 'data-vector-picker-style' : 'data-vector-picker-theme', sel.value); }
	});

	var pickerSelector = 'input[data-vector-picker].vm-initialized';
	new MutationObserver(function (mutations) {
		mutations.forEach(function (m) {
			if (m.target.matches && m.target.matches(pickerSelector)) { m.target._gbVmLive = true; }
		});
	}).observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class'] });

	function revivePickers()
	{	Array.prototype.forEach.call(document.querySelectorAll(pickerSelector), function (input) {
			if (input._gbVmLive) { return; }
			var wrapper = input.parentNode;
			if (wrapper && wrapper.classList.contains('input-group') && wrapper.querySelector(':scope > .input-group-btn')) {
				wrapper.parentNode.insertBefore(input, wrapper);
				wrapper.parentNode.removeChild(wrapper);
			}
			input.classList.remove('vm-initialized');
		});
	}
	if (window.jQuery) {
		jQuery(document).on('rex:ready', function () { Promise.resolve().then(revivePickers); });
	}
})();
</script>

<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" class="active"><a href="#<?php echo $uid; ?>_tab_content" role="tab" data-toggle="tab" aria-controls="<?php echo $uid; ?>_tab_content">Inhalt</a></li>
	<li role="presentation"><a href="#<?php echo $uid; ?>_tab_map" role="tab" data-toggle="tab" aria-controls="<?php echo $uid; ?>_tab_map">Karte</a></li>
</ul>
<style>
.gbv-pair{display:flex;flex-wrap:wrap;gap:0 20px}
.gbv-pair>.gbv-col{flex:1 1 240px;min-width:0}
</style>
<div class="tab-content" style="padding-top: 15px;">
	<div role="tabpanel" class="tab-pane active" id="<?php echo $uid; ?>_tab_content">
		<div class="form-group">
			<label for="<?php echo $uid; ?>_location">Koordinaten</label>
			<?php if ($hasPicker): ?>
				<?php echo \KLXM\VectorMaps\Picker\PickerWidget::factory('location', $uid.'_location')
					->setValue($val('location'))
					->setPlaceholder('52.520008,13.404954')
					->setMapStyle($mapStyle)
					->setTheme($theme)
					->setThemeChoices($themeChoices)
					->addClass('form-control')
					->parse(); ?>
			<?php else: ?>
				<input type="text" name="location" id="<?php echo $uid; ?>_location" class="form-control" placeholder="52.520008,13.404954" value="<?php echo rex_escape($val('location')); ?>">
				<p class="help-block small">Addon "vector_maps" ist nicht installiert - Koordinaten bitte manuell als "lat,lng" eintragen.</p>
			<?php endif; ?>
		</div>

		<div class="gbv-pair">
			<div class="gbv-col">
				<div class="form-group">
					<label for="<?php echo $uid; ?>_popup_title">Marker-Popup: Titel</label>
					<input type="text" name="popup_title" id="<?php echo $uid; ?>_popup_title" class="form-control" value="<?php echo rex_escape($val('popup_title')); ?>">
				</div>
				<div class="form-group">
					<label for="<?php echo $uid; ?>_popup_text">Marker-Popup: Text</label>
					<textarea name="popup_text" id="<?php echo $uid; ?>_popup_text" class="form-control" rows="4"><?php echo rex_escape($val('popup_text')); ?></textarea>
				</div>
			</div>
			<div class="gbv-col">
				<div class="form-group">
					<label for="<?php echo $uid; ?>_info_html">Infofenster (HTML, optional)</label>
					<textarea name="info_html" id="<?php echo $uid; ?>_info_html" class="form-control" rows="7"><?php echo rex_escape($val('info_html')); ?></textarea>
					<p class="help-block small">Optionales Infofenster direkt in der Karte, bleibt leer = kein Infofenster.</p>
				</div>
			</div>
		</div>
	</div>

	<div role="tabpanel" class="tab-pane" id="<?php echo $uid; ?>_tab_map">
		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label for="<?php echo $uid; ?>_style">Kartenstil</label>
					<select name="map_style" id="<?php echo $uid; ?>_style" class="form-control" data-gb-vm-sync="style">
						<?php foreach ($styleChoices as $key => $label): ?>
						<option value="<?php echo rex_escape($key); ?>"<?php echo ($mapStyle === $key) ? ' selected' : ''; ?>><?php echo rex_escape($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label for="<?php echo $uid; ?>_theme">Theme</label>
					<select name="theme" id="<?php echo $uid; ?>_theme" class="form-control" data-gb-vm-sync="theme">
						<?php foreach ($themeChoices as $key => $label): ?>
						<option value="<?php echo rex_escape((string) $key); ?>"<?php echo ($theme === (string) $key) ? ' selected' : ''; ?>><?php echo rex_escape((string) $label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label for="<?php echo $uid; ?>_zoom">Zoom</label>
					<select name="zoom" id="<?php echo $uid; ?>_zoom" class="form-control">
						<?php foreach ($zoomChoices as $key => $label): ?>
						<option value="<?php echo (int) $key; ?>"<?php echo ($zoom === (int) $key) ? ' selected' : ''; ?>><?php echo rex_escape($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label for="<?php echo $uid; ?>_height">Höhe</label>
					<input type="text" name="height" id="<?php echo $uid; ?>_height" class="form-control" value="<?php echo rex_escape($val('height', '420')); ?>">
					<p class="help-block small">Zahl (Pixel) oder CSS-Wert wie 520px.</p>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label>Bedienelemente</label>
					<div class="checkbox"><label><input type="checkbox" name="locate" value="checked" <?php echo $val('locate'); ?>> Standort-Button anzeigen</label></div>
					<div class="checkbox"><label><input type="checkbox" name="show_satellite" value="checked" <?php echo $val('show_satellite'); ?>> Satelliten-Umschalter anzeigen</label></div>
					<div class="checkbox"><label><input type="checkbox" name="fullscreen" value="checked" <?php echo $val('fullscreen'); ?>> Vollbild-Button anzeigen</label></div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group">
					<label for="<?php echo $uid; ?>_controls_position">Position Bedienelemente</label>
					<select name="controls_position" id="<?php echo $uid; ?>_controls_position" class="form-control">
						<?php foreach ($positionChoices as $key => $label): ?>
						<option value="<?php echo rex_escape($key); ?>"<?php echo ($controlsPosition === $key) ? ' selected' : ''; ?>><?php echo rex_escape($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
	</div>
</div>
