<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Eingabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

$v = (array) ($_gb->values ?? []);
$uid = $_gb->uid;

$align = (string) ($v['align'] ?? 'links');
if (!in_array($align, ['links', 'mitte', 'rechts'], true)) {
	$align = 'links';
}
$radius = (string) ($v['radius'] ?? 'pille');
if (!in_array($radius, ['pille', 'abgerundet', 'eckig'], true)) {
	$radius = 'pille';
}
//Schatten ist die Voreinstellung (neues Element, Altbestand); gespeicherte Werte der frueheren Auswahl ("an"/"aus") bleiben lesbar
$shadow = !array_key_exists('buttons', $v) || !in_array((string) ($v['shadow'] ?? ''), ['', 'aus'], true);
$full = !empty($v['full']);
require_once __DIR__.'/helpers.php';
$effect = GbCtaKit::effect((string) ($v['effect'] ?? ''));
$palette = GbCtaKit::palette();

$sel =static fn (string $current, string $option): string => $current === $option ? ' selected' : '';

$rows = $v['buttons'] ?? '';
if (!array_key_exists('buttons', $v)) {
	$rows = [];
	$legacyLink = (string) ($v['link'] ?? '');
	$legacyLinkType = (string) ((json_decode($legacyLink, true) ?: [])['type'] ?? '');
	$rows[Gridblock2Builder_Tree::makeRepeaterUid()] = [
		'titel' => (string) ($v['titel'] ?? ''),
		'stil' => 'fill',
		'icon' => (string) ($v['icon'] ?? ''),
		'iconposition' => (string) ($v['iconposition'] ?? 'links'),
		'link' => $legacyLink,
		'newtab' => ($legacyLinkType === 'url') ? 'checked' : '',
		'bgcolor' => (string) ($v['bgcolor'] ?? ''),
		'textcolor' => (string) ($v['textcolor'] ?? ''),
	];
}
if ($palette !== []) {
	if (is_array($rows)) {
		if ($v !== []) {
			foreach ($rows as $rowUid => $rowData) {
				$rows[$rowUid]['farbe'] = 'ind';
			}
		}
	} elseif (trim((string) $rows) !== '') {
		$decodedRows = json_decode(htmlspecialchars_decode((string) $rows, ENT_QUOTES), true);
		if (is_array($decodedRows)) {
			foreach ($decodedRows as $rowUid => $rowData) {
				if (is_array($rowData) && !array_key_exists('farbe', $rowData)) {
					$decodedRows[$rowUid]['farbe'] = 'ind';
				}
			}
			$rows = $decodedRows;
		}
	}
}
$rowCount =is_array($rows) ? count($rows) : count((array) json_decode((string) $rows, true));
?>
<style>
.gbc-row [data-gbc-show]{display:none!important}
.gbc-row:has(select[name="stil"] option[value="fill"]:checked) [data-gbc-show~="fill"]{display:block!important}
.gbc-row:has(select[name="stil"] option[value="outline"]:checked) [data-gbc-show~="outline"]{display:block!important}
.gbc-row [data-gbc-ind]{display:none}
.gbc-row:has(select[name="farbe"] option[value="ind"]:checked) [data-gbc-ind]{display:block}
.gbc-pair:has(select[name="farbe"]){margin-top:10px}
.gbc-hoverbox{margin-bottom:15px}
.gbc-hoverbox>summary{display:flex;align-items:center;gap:8px;list-style:none;cursor:pointer;margin-bottom:10px;font-weight:600}
.gbc-hoverbox>summary::-webkit-details-marker{display:none}
.gbc-hoverbox>summary::before{content:"";flex:none;border-style:solid;border-width:5px 0 5px 8px;border-color:transparent transparent transparent currentColor;transition:transform .15s ease}
.gbc-hoverbox[open]>summary::before{transform:rotate(90deg)}
.gbc-hoverbox>summary small{font-weight:400}
.gbc-hoverbox>summary:hover,.gbc-hoverbox>summary:focus-visible{color:#337ab7;text-decoration:underline}
.gbc-hoverbox>summary:focus-visible{outline:2px solid #337ab7;outline-offset:2px;border-radius:2px}
.gb-pal-sw{display:inline-flex;flex:none;align-items:center;justify-content:center;width:24px;height:24px;margin-right:8px;border-radius:6px;font:600 11px/1 sans-serif;box-shadow:inset 0 0 0 1px rgba(0,0,0,.25)}
.bootstrap-select .text:has(>.gb-pal-sw),.bootstrap-select .filter-option-inner-inner:has(>.gb-pal-sw){display:flex;align-items:center}
.gb-pal-sw-t{background-image:repeating-conic-gradient(#c8c8c8 0 25%,#fff 0 50%);background-size:8px 8px}
.gb-pal-sw-ind{background:#fff;color:#444;font-size:13px}
.bootstrap-select>select[data-gb-palette]{display:none!important}
.gbc-pair{display:flex;flex-wrap:wrap;gap:0 20px}
.gbc-pair>.rex-form-group{flex:1 1 200px;min-width:0}
.gbc-checks .checkbox{margin-top:0;margin-bottom:0}
.gbc-checks .checkbox+.checkbox{margin-top:4px}
.gbc-settings{margin-top:45px}
</style>
<script>
//der Icon-Picker (Addon fa_iconpicker) setzt den Wert ohne Ereignis - der Repeater, die Live-Vorschau und die Aenderungs-Erkennung brauchen aber ein "change"
window.gb2CtaIconPicked = window.gb2CtaIconPicked || function (name, removed, field) {
	setTimeout(function () { field.dispatchEvent(new Event('change', { bubbles: true })); }, 0);
};
</script>

<div class="rex-form-group form-group gbc-root">
	<label>Buttons</label>
	<?php
	$rowMarkup = <<<'HTML'
		<div class="gbc-row">
			<div class="gbc-pair">
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
			<div class="gbc-pair">
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
			<div class="gbc-pair">
				<div class="rex-form-group form-group">
					<label>Fontawesome-Icon <small class="text-muted">(optional)</small></label>
					<input type="text" name="icon" value="{{icon}}" class="form-control rex-fa-iconpicker" data-fa-add-weight="1" data-fa-preview-weight="S" data-fa-onselect="gb2CtaIconPicked" placeholder="z. B. fas fa-arrow-right">
				</div>
				<div class="rex-form-group form-group">
					<label>Position Icon</label>
					<select name="iconposition" class="form-control">
						<option value="links">Links</option>
						<option value="rechts">Rechts</option>
					</select>
				</div>
			</div>
			<!--GBC_COLORS-->
		</div>
		HTML;
	$ownColors = <<<'HTML'
		<div class="gbc-pair">
			<div class="rex-form-group form-group" data-gbc-show="fill">
				<label>Hintergrundfarbe</label>
				<input type="text" name="bgcolor" value="{{bgcolor}}" class="form-control" data-colorpicker data-colorpicker-mode="both" placeholder="#rrggbb oder rgba(…)">
			</div>
			<div class="rex-form-group form-group">
				<label>Textfarbe <small class="text-muted">(bei „Umrandet“ auch Rahmenfarbe)</small></label>
				<input type="text" name="textcolor" value="{{textcolor}}" class="form-control" data-colorpicker placeholder="#rrggbb oder rgba(…)">
			</div>
		</div>
		HTML;
	$hoverFields = <<<'HTML'
		<div class="gbc-pair">
			<div class="rex-form-group form-group">
				<label>Hover-Hintergrund <small class="text-muted">(optional)</small></label>
				<input type="text" name="hbgcolor" value="{{hbgcolor}}" class="form-control" data-colorpicker placeholder="#rrggbb oder rgba(…)">
			</div>
			<div class="rex-form-group form-group">
				<label>Hover-Text <small class="text-muted">(optional)</small></label>
				<input type="text" name="htextcolor" value="{{htextcolor}}" class="form-control" data-colorpicker placeholder="#rrggbb oder rgba(…)">
			</div>
		</div>
		HTML;
	if ($palette === []) {
		$colorMarkup = $ownColors.'<details class="gbc-hoverbox" data-gbc-show="fill outline"><summary>Hover-Farben <small class="text-muted">(optional)</small></summary>'.$hoverFields.'</details>';
	} else {
		$options = '';
		foreach ($palette as $entry) {
			$options .= '<option value="'.rex_escape($entry['key']).'" data-gb-bg="'.rex_escape($entry['bg']).'" data-gb-fg="'.rex_escape($entry['fg']).'">'.rex_escape($entry['label']).'</option>';
		}
		$options .= '<option value="ind" data-gb-ind="1">Individuell</option>';
		$colorMarkup = '<div class="gbc-pair"><div class="rex-form-group form-group"><label>Farbe</label>'
			.'<select name="farbe" class="selectpicker form-control" data-gb-palette data-live-search="true" data-width="100%">'.$options.'</select></div></div>'
			.'<div data-gbc-ind>'.$ownColors.'<div data-gbc-show="fill outline">'.$hoverFields.'</div></div>';
	}
	$rowMarkup = str_replace('<!--GBC_COLORS-->', $colorMarkup, $rowMarkup);
	if ($palette !== []) {
		echo '<script type="application/json" class="gb-pal-vars">'.json_encode(['css' => Gridblock2Palette::previewVarsCss()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE).'</script>';
	}
	echo Gridblock2Builder_Tree::renderRepeater('buttons', $rows, $rowMarkup, [
		'collapsible' => true,
		'titleField' => 'titel',
		'titleFallback' => 'Button',
		'collapsed' => $rowCount > 1,
	]);
	?>
</div>

<div class="gbc-settings">
	<label>Einstellungen</label>
	<div class="gbc-pair">
		<div class="rex-form-group form-group">
			<label for="<?php echo $uid; ?>_align">Ausrichtung</label>
			<select name="align" id="<?php echo $uid; ?>_align" class="form-control">
				<option value="links"<?php echo $sel($align, 'links'); ?>>Links</option>
				<option value="mitte"<?php echo $sel($align, 'mitte'); ?>>Mitte</option>
				<option value="rechts"<?php echo $sel($align, 'rechts'); ?>>Rechts</option>
			</select>
		</div>
		<div class="rex-form-group form-group">
			<label for="<?php echo $uid; ?>_radius">Eckenrundung</label>
			<select name="radius" id="<?php echo $uid; ?>_radius" class="form-control">
				<option value="pille"<?php echo $sel($radius, 'pille'); ?>>Pille</option>
				<option value="abgerundet"<?php echo $sel($radius, 'abgerundet'); ?>>Abgerundet</option>
				<option value="eckig"<?php echo $sel($radius, 'eckig'); ?>>Eckig</option>
			</select>
		</div>
		<div class="rex-form-group form-group">
			<label for="<?php echo $uid; ?>_effect">Hover-Effekt</label>
			<select name="effect" id="<?php echo $uid; ?>_effect" class="form-control">
				<?php foreach (GbCtaKit::EFFECTS as $key => $label): ?>
				<option value="<?php echo $key; ?>"<?php echo $sel($effect, $key); ?>><?php echo rex_escape($label); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<div class="rex-form-group form-group gbc-checks">
		<div class="checkbox"><label><input type="checkbox" name="shadow" value="checked"<?php echo $shadow ? ' checked' : ''; ?>> Schatten <small class="text-muted">(nur bei „Gefüllt“)</small></label></div>
		<div class="checkbox"><label><input type="checkbox" name="full" value="checked"<?php echo $full ? ' checked' : ''; ?>> Volle Breite <small class="text-muted">(jeder Button über die gesamte Breite)</small></label></div>
	</div>
</div>
