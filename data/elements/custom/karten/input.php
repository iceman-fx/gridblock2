<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Eingabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

require_once __DIR__.'/helpers.php';

$v = (array) ($_gb->values ?? []);
$uid = $_gb->uid;

$val = static fn (string $key, string $default = ''): string => (string) ($v[$key] ?? $default);
$sel = static fn (string $current, string $option): string => $current === $option ? ' selected' : '';

$style = GbCardsKit::pick($val('style') === 'rand' ? 'kachel' : $val('style'), array_keys(GbCardsKit::STYLES), 'klassisch');
$width = GbCardsKit::pick($val('width'), array_map('strval', array_keys(GbCardsKit::WIDTHS)), '');
$align = GbCardsKit::pick($val('align'), ['links', 'mitte', 'rechts'], 'links');
$cardalign = GbCardsKit::pick($val('cardalign'), ['links', 'mitte', 'rechts'], 'links');
$ratio = GbCardsKit::pick($val('ratio'), array_map('strval', array_keys(GbCardsKit::RATIOS)), '');
$imgsize = GbCardsKit::pick($val('imgsize'), array_map('strval', array_keys(GbCardsKit::IMGSIZES)), '');
$imgpos = GbCardsKit::pick($val('imgpos'), array_keys(GbCardsKit::POSITIONS), 'oben');
$gap = GbCardsKit::pick($val('gap', '24'), array_map('strval', array_keys(GbCardsKit::GAPS)), '24');
$palette = GbCardsKit::palette();
$hasCards = trim((string) ($v['cards'] ?? '')) !== '';
$roundOn = array_key_exists('round_set', $v) ? !empty($v['round']) : ($val('radius') !== 'keine');
$shadowOn = array_key_exists('shadow_set', $v) ? !empty($v['shadow']) : empty($v['noshadow']);

$css = '.gbk-root [data-gbk]{display:none!important}';
foreach (array_keys(GbCardsKit::STYLES) as $key) {
	$css .= '.gbk-root:has(select[name="style"] option[value="'.$key.'"]:checked) [data-gbk~="'.$key.'"]{display:block!important}';
}
?>
<style>
<?php echo $css; ?>
.gbk-pair{display:flex;flex-wrap:wrap;gap:0 20px}
.gbk-pair>.rex-form-group,.gbk-pair>[data-gbk]{flex:1 1 220px;min-width:0}
.gbk-checks .checkbox{margin-top:0;margin-bottom:0}
.gbk-cards{margin-top:34px}
.gbk-groups{margin-top:30px}
.gbk-group{margin:0 0 10px;border:1px solid rgba(128,128,128,.35);border-radius:3px}
.gbk-group>summary{display:flex;align-items:center;gap:10px;padding:9px 14px;font-weight:600;cursor:pointer;list-style:none;background:rgba(128,128,128,.1)}
.gbk-group>summary::-webkit-details-marker{display:none}
.gbk-group>summary::before{content:"";flex:none;width:6px;height:6px;border:solid currentColor;border-width:0 2px 2px 0;transform:rotate(-45deg);transition:transform .15s}
.gbk-group[open]>summary::before{transform:rotate(45deg);margin-top:-3px}
.gbk-group[open]>summary{border-bottom:1px solid rgba(128,128,128,.35)}
.gbk-group-body{padding:14px 14px 0}
.gb-pal-sw{display:inline-flex;flex:none;align-items:center;justify-content:center;width:24px;height:24px;margin-right:8px;border-radius:6px;font:600 11px/1 sans-serif;box-shadow:inset 0 0 0 1px rgba(0,0,0,.25)}
.bootstrap-select .text:has(>.gb-pal-sw),.bootstrap-select .filter-option-inner-inner:has(>.gb-pal-sw){display:flex;align-items:center}
.gb-pal-sw-t{background-image:repeating-conic-gradient(#c8c8c8 0 25%,#fff 0 50%);background-size:8px 8px}
.gb-pal-sw-ind{background:#fff;color:#444;font-size:13px}
.bootstrap-select>select[data-gb-palette]{display:none!important}
</style>

<div class="gbk-root gbc-root">
	<?php if ($palette !== []): ?>
	<script type="application/json" class="gb-pal-vars"><?php echo json_encode(['css' => Gridblock2Palette::previewVarsCss()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?></script>
	<?php endif; ?>

	<div class="gbk-pair">
		<div class="rex-form-group form-group">
			<label for="<?php echo $uid; ?>_style">Darstellung</label>
			<select name="style" id="<?php echo $uid; ?>_style" class="form-control">
				<?php foreach (GbCardsKit::STYLES as $key => $label): ?>
				<option value="<?php echo $key; ?>"<?php echo $sel($style, $key); ?>><?php echo rex_escape($label); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="rex-form-group form-group">
			<label for="<?php echo $uid; ?>_width">Kartenbreite</label>
			<select name="width" id="<?php echo $uid; ?>_width" class="form-control">
				<?php foreach (GbCardsKit::WIDTHS as $key => $label): ?>
				<option value="<?php echo $key; ?>"<?php echo $sel($width, (string) $key); ?>><?php echo rex_escape($label); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<div class="rex-form-group form-group gbk-cards">
		<label>Karten</label>
		<?php
		$rowMarkup = <<<'HTML'
			<div class="rex-form-group form-group">
				<label>Bild oder Icon <small class="text-muted">(jpg, png, gif, webp, svg - die Bildbeschreibung kommt aus dem Medienpool)</small></label>
				REX_MEDIA[id="bild" types="jpg,jpeg,png,gif,webp,svg"]
			</div>
			<div class="rex-form-group form-group" data-gbk="klassisch offen kachel kachelbtn">
				<label>Label <small class="text-muted">(optional, kleines Etikett)</small></label>
				<input type="text" name="label" value="{{label}}" class="form-control">
			</div>
			<div class="rex-form-group form-group">
				<label>Überschrift</label>
				<input type="text" name="titel" value="{{titel}}" class="form-control">
			</div>
			<div class="rex-form-group form-group">
				<label>Text</label>
				<textarea name="text" rows="3" class="form-control">{{text}}</textarea>
			</div>
			<div class="rex-form-group form-group">
				<label>Link</label>
				REX_MULTILINK[id="link"]
			</div>
			HTML;
		echo Gridblock2Builder_Tree::renderRepeater('cards', $v['cards'] ?? '', $rowMarkup, [
			'collapsible' => true,
			'titleField' => 'titel,label,text',
			'titleFallback' => 'Karte',
			'collapsed' => $hasCards,
		]);
		?>
	</div>

	<div class="gbk-groups">
		<label>Einstellungen</label>
		<details class="gbk-group">
			<summary>Farben</summary>
			<div class="gbk-group-body">
				<div class="gbk-pair">
					<div class="rex-form-group form-group" data-gbk="klassisch kachel kachelbtn kompakt">
						<label for="<?php echo $uid; ?>_card_bg">Farbe der Karte <small class="text-muted">(optional)</small></label>
						<?php echo GbCardsKit::colorField($palette, 'card_bg', $uid.'_card_bg', $val('card_bg'), 'bg', 'solid', '#rrggbb oder rgba(…)'); ?>
					</div>
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_card_color">Farbe des Textes <small class="text-muted">(optional)</small></label>
						<?php echo GbCardsKit::colorField($palette, 'card_color', $uid.'_card_color', $val('card_color'), 'bg', 'solid', '#rrggbb oder rgba(…)'); ?>
					</div>
				</div>
				<div class="gbk-pair">
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_accent">Akzentfarbe <small class="text-muted">(Label, Button, Pfeil, Streifen)</small></label>
						<?php echo GbCardsKit::colorField($palette, 'accent', $uid.'_accent', $val('accent'), 'bg', 'solid', '#rrggbb oder rgba(…)'); ?>
					</div>
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_accent_text">Textfarbe auf Akzentfarbe <small class="text-muted">(optional)</small></label>
						<?php echo GbCardsKit::colorField($palette, 'accent_text', $uid.'_accent_text', $val('accent_text'), 'bg', 'solid', '#rrggbb oder rgba(…)'); ?>
					</div>
				</div>
			</div>
		</details>

		<details class="gbk-group">
			<summary>Sonstiges</summary>
			<div class="gbk-group-body">
				<div class="gbk-pair">
					<div class="rex-form-group form-group" data-gbk="klassisch">
						<label for="<?php echo $uid; ?>_imgsize">Bildgröße</label>
						<select name="imgsize" id="<?php echo $uid; ?>_imgsize" class="form-control">
							<?php foreach (GbCardsKit::IMGSIZES as $key => $label): ?>
							<option value="<?php echo $key; ?>"<?php echo $sel($imgsize, (string) $key); ?>><?php echo rex_escape($label); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="gbk-pair">
					<div class="rex-form-group form-group" data-gbk="klassisch offen kachel kachelbtn kompakt">
						<label for="<?php echo $uid; ?>_ratio">Bildformat</label>
						<select name="ratio" id="<?php echo $uid; ?>_ratio" class="form-control">
							<?php foreach (GbCardsKit::RATIOS as $key => $label): ?>
							<option value="<?php echo $key; ?>"<?php echo $sel($ratio, (string) $key); ?>><?php echo rex_escape($label); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_gap">Abstand zwischen den Karten</label>
						<select name="gap" id="<?php echo $uid; ?>_gap" class="form-control">
							<?php foreach (GbCardsKit::GAPS as $key => $label): ?>
							<option value="<?php echo $key; ?>"<?php echo $sel($gap, (string) $key); ?>><?php echo rex_escape($label); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="gbk-pair">
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_cardalign">Kartenausrichtung</label>
						<select name="cardalign" id="<?php echo $uid; ?>_cardalign" class="form-control">
							<option value="links"<?php echo $sel($cardalign, 'links'); ?>>Links</option>
							<option value="mitte"<?php echo $sel($cardalign, 'mitte'); ?>>Mittig</option>
							<option value="rechts"<?php echo $sel($cardalign, 'rechts'); ?>>Rechts</option>
						</select>
					</div>
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_align">Textausrichtung</label>
						<select name="align" id="<?php echo $uid; ?>_align" class="form-control">
							<option value="links"<?php echo $sel($align, 'links'); ?>>Links</option>
							<option value="mitte"<?php echo $sel($align, 'mitte'); ?>>Mittig</option>
							<option value="rechts"<?php echo $sel($align, 'rechts'); ?>>Rechts</option>
						</select>
					</div>
				</div>
				<div class="gbk-pair">
					<div class="rex-form-group form-group" data-gbk="kachel kachelbtn">
						<label for="<?php echo $uid; ?>_imgpos">Bildposition</label>
						<select name="imgpos" id="<?php echo $uid; ?>_imgpos" class="form-control">
							<?php foreach (GbCardsKit::POSITIONS as $key => $label): ?>
							<option value="<?php echo $key; ?>"<?php echo $sel($imgpos, $key); ?>><?php echo rex_escape($label); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="gbk-pair gbk-checks">
					<div class="rex-form-group form-group">
						<div class="checkbox"><label><input type="hidden" name="round_set" value="1"><input type="checkbox" name="round" value="checked"<?php echo $roundOn ? ' checked' : ''; ?>> Ecken abrunden</label></div>
					</div>
					<div class="rex-form-group form-group">
						<div class="checkbox"><label><input type="hidden" name="shadow_set" value="1"><input type="checkbox" name="shadow" value="checked"<?php echo $shadowOn ? ' checked' : ''; ?>> Schatten anzeigen</label></div>
					</div>
					<div class="rex-form-group form-group" data-gbk="kachel kachelbtn">
						<div class="checkbox"><label><input type="checkbox" name="stripe" value="checked"<?php echo !empty($v['stripe']) ? ' checked' : ''; ?>> Akzentstreifen am unteren Rand</label></div>
					</div>
				</div>
			</div>
		</details>
	</div>
</div>
