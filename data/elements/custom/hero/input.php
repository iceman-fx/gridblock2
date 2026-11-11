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
$isNew = !array_key_exists('layout', $v);

$val = static fn (string $key, string $default = ''): string => (string) ($v[$key] ?? $default);
$esc = static fn (string $key, string $default = ''): string => rex_escape($val($key, $default));
$sel = static fn (string $current, string $option): string => $current === $option ? ' selected' : '';

$layout = GbHeroKit::pick($val('layout'), array_keys(GbHeroKit::LAYOUTS), 'nebeneinander');
$headingLevel = GbHeroKit::pick($val('heading_level'), ['h1', 'h2', 'h3', 'normal'], 'h1');
$textAlign = GbHeroKit::pick($val('textalign'), ['links', 'mitte', 'rechts'], ($layout === 'zentriert') ? 'mitte' : 'links');
$blockPos = GbHeroKit::pick($val('blockpos'), array_keys(GbHeroKit::BLOCK_POSITIONS), ($layout === 'zentriert') ? 'mitte' : 'links');
$ratio = GbHeroKit::pick($val('ratio'), array_keys(GbHeroKit::RATIOS), '16-9');
$side = GbHeroKit::pick($val('media_side'), ['links', 'rechts'], 'rechts');
$stack = GbHeroKit::pick($val('media_stack'), ['ueber', 'unter'], 'unter');
$overlay = GbHeroKit::pick($val('overlay'), array_keys(GbHeroKit::OVERLAYS), 'mittel');
$inset = GbHeroKit::pick($val('inset'), array_keys(GbHeroKit::INSETS), 'standard');
$focus = GbHeroKit::pick($val('focus'), array_keys(GbHeroKit::FOCUS), 'mitte');
$cardPos = GbHeroKit::pick($val('card_pos'), ['aussen', 'innen'], 'aussen');
$cardV = GbHeroKit::pick($val('card_valign'), ['oben', 'mitte', 'unten'], 'mitte');
$big = !empty($v['heading_big']);
//Abrundung: neue Elemente und Altbestand sind abgerundet; "round_set" markiert Elemente, die mit dem Haekchen (name="round") gespeichert wurden
$round = array_key_exists('round_set', $v) ? !empty($v['round']) : empty($v['noround']);
$imgShadow = !empty($v['img_shadow']);
$bulletStyle = GbHeroKit::pick($val('bullet_style'), ['icon', 'text'], 'icon');
$bulletPos = GbHeroKit::pick($val('bullet_pos'), ['unten', 'mitte', 'oben'], 'unten');
$bulletLayout = GbHeroKit::pick($val('bullet_layout'), ['inline', 'stack'], 'inline');
$vttLang = GbHeroKit::pick($val('vtt_lang'), array_keys(GbHeroKit::SUBTITLE_LANGS), 'de');
$maxWidth = GbHeroKit::pick($val('maxwidth'), array_map('strval', array_keys(GbHeroKit::MAX_WIDTHS)), '');
$textMaxUnit = GbHeroKit::pick($val('text_maxu'), ['px', '%'], 'px');
$textBold = !empty($v['text_bold']);
$bulletBold = !empty($v['bullet_bold']);
$eyebrowBold = !empty($v['eyebrow_bold']);

$buttons = $v['buttons'] ?? '';
$cards = $v['cards'] ?? '';
$palette = GbHeroKit::palette();
$buttonEffect = GbHeroKit::effect($val('effect'));
$btnRadius = GbHeroKit::pick($val('btn_radius'), array_keys(GbHeroKit::BTN_RADII), 'pille');
$split = GbHeroKit::pick($val('split'), array_keys(GbHeroKit::SPLITS), '60-40');
$edge = GbHeroKit::pick($val('edge'), array_keys(GbHeroKit::EDGES), 'gerade');
if ($isNew) {
	$buttons = [Gridblock2Builder_Tree::makeRepeaterUid() => ['titel' => '', 'stil' => 'fill']];
	$cards = [Gridblock2Builder_Tree::makeRepeaterUid() => ['ktext' => '']];
} elseif ($palette !== [] && trim((string) $buttons) !== '') {
	$decodedButtons = json_decode(htmlspecialchars_decode((string) $buttons, ENT_QUOTES), true);
	if (is_array($decodedButtons)) {
		foreach ($decodedButtons as $buttonUid => $buttonData) {
			if (is_array($buttonData) && !array_key_exists('farbe', $buttonData)) {
				$decodedButtons[$buttonUid]['farbe'] = 'ind';
			}
		}
		$buttons = $decodedButtons;
	}
}

$css = '.gbh-root [data-gbh]{display:none!important}';
foreach (array_keys(GbHeroKit::LAYOUTS) as $key) {
	$css .= '.gbh-root:has(select[name="layout"] option[value="'.$key.'"]:checked) [data-gbh~="'.$key.'"]{display:block!important}';
}
$css .= '.gbh-root:has(select[name="bullet_style"] option[value="text"]:checked) [data-gbh-bs]{display:none!important}';
?>
<style>
<?php echo $css; ?>
.gbh-row [data-gbh-row]{display:none!important}
.gbh-row:has(select[name="stil"] option[value="fill"]:checked) [data-gbh-row~="fill"]{display:block!important}
.gbh-row:has(select[name="stil"] option[value="outline"]:checked) [data-gbh-row~="outline"]{display:block!important}
.gbh-row [data-gbh-ind]{display:none}
.gbh-row:has(select[name="farbe"] option[value="ind"]:checked) [data-gbh-ind]{display:block}
.gbh-colorpair{margin-top:10px}
.gbh-hoverbox{margin-bottom:15px}
.gbh-hoverbox>summary{display:flex;align-items:center;gap:8px;list-style:none;cursor:pointer;margin-bottom:10px;font-weight:600}
.gbh-hoverbox>summary::-webkit-details-marker{display:none}
.gbh-hoverbox>summary::before{content:"";flex:none;border-style:solid;border-width:5px 0 5px 8px;border-color:transparent transparent transparent currentColor;transition:transform .15s ease}
.gbh-hoverbox[open]>summary::before{transform:rotate(90deg)}
.gbh-hoverbox>summary small{font-weight:400}
.gbh-hoverbox>summary:hover,.gbh-hoverbox>summary:focus-visible{color:#337ab7;text-decoration:underline}
.gbh-hoverbox>summary:focus-visible{outline:2px solid #337ab7;outline-offset:2px;border-radius:2px}
.gb-pal-sw{display:inline-flex;flex:none;align-items:center;justify-content:center;width:24px;height:24px;margin-right:8px;border-radius:6px;font:600 11px/1 sans-serif;box-shadow:inset 0 0 0 1px rgba(0,0,0,.25)}
.bootstrap-select .text:has(>.gb-pal-sw),.bootstrap-select .filter-option-inner-inner:has(>.gb-pal-sw){display:flex;align-items:center}
.gb-pal-sw-t{background-image:repeating-conic-gradient(#c8c8c8 0 25%,#fff 0 50%);background-size:8px 8px}
.gb-pal-sw-ind{background:#fff;color:#444;font-size:13px}
.bootstrap-select>select[data-gb-palette]{display:none!important}
.gbh-pair{display:flex;flex-wrap:wrap;gap:0 20px}
.gbh-pair>.rex-form-group{flex:1 1 200px;min-width:0}
.gbh-pair>[data-gbh]{flex:1 1 200px}
.gbh-pair.gbh-pair-wide>.rex-form-group,.gbh-pair.gbh-pair-wide>[data-gbh]{flex-basis:300px}
.gbh-root>.nav-tabs{margin-top:28px}
.gbh-tabs{margin-top:15px}
.gbh-gap{margin-top:22px}
.gbh-sub{margin-top:30px}
.gbh-groups{margin-top:45px}
.gbh-group{margin:0 0 10px;border:1px solid rgba(128,128,128,.35);border-radius:3px}
.gbh-group>summary{display:flex;align-items:center;gap:10px;padding:9px 14px;font-weight:600;cursor:pointer;list-style:none;background:rgba(128,128,128,.1)}
.gbh-group>summary::-webkit-details-marker{display:none}
.gbh-group>summary::before{content:"";flex:none;width:6px;height:6px;border:solid currentColor;border-width:0 2px 2px 0;transform:rotate(-45deg);transition:transform .15s}
.gbh-group[open]>summary::before{transform:rotate(45deg);margin-top:-3px}
.gbh-group[open]>summary{border-bottom:1px solid rgba(128,128,128,.35)}
.gbh-group>summary small{font-weight:400}
.gbh-group-body{padding:14px 14px 0}
.gbh-checks .checkbox{margin-top:0;margin-bottom:0}
</style>
<script>
//der Icon-Picker (Addon fa_iconpicker) setzt den Wert ohne Ereignis - Repeater, Live-Vorschau und Aenderungs-Erkennung brauchen aber ein "change"
window.gb2HeroIconPicked = window.gb2HeroIconPicked || function (name, removed, field) {
	setTimeout(function () { field.dispatchEvent(new Event('change', { bubbles: true })); }, 0);
};
</script>

<div class="gbh-root gbc-root">
	<?php if ($palette !== []): ?>
	<script type="application/json" class="gb-pal-vars"><?php echo json_encode(['css' => Gridblock2Palette::previewVarsCss()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?></script>
	<?php endif; ?>
	<div class="gbh-pair">
		<div class="rex-form-group form-group">
			<label for="<?php echo $uid; ?>_layout">Darstellung</label>
			<select name="layout" id="<?php echo $uid; ?>_layout" class="form-control">
				<?php foreach (GbHeroKit::LAYOUTS as $key => $label): ?>
				<option value="<?php echo $key; ?>"<?php echo $sel($layout, $key); ?>><?php echo rex_escape($label); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="rex-form-group form-group" data-gbh="nebeneinander zentriert vollflaechig karten">
			<label for="<?php echo $uid; ?>_maxwidth">Maximale Breite</label>
			<select name="maxwidth" id="<?php echo $uid; ?>_maxwidth" class="form-control">
				<option value=""<?php echo $sel($maxWidth, ''); ?>>Volle Breite (keine Begrenzung)</option>
				<?php foreach (GbHeroKit::MAX_WIDTHS as $width => $label): ?>
				<option value="<?php echo $width; ?>"<?php echo $sel($maxWidth, (string) $width); ?>><?php echo rex_escape($label); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<p class="help-block small">Pro Seite nur einmal verwenden (die Überschrift ist die H1 der Seite). Die maximale Breite begrenzt den ganzen Hero und zentriert ihn.</p>

	<ul class="nav nav-tabs" role="tablist">
		<li role="presentation" class="active"><a href="#<?php echo $uid; ?>_tab_text" role="tab" data-toggle="tab" aria-controls="<?php echo $uid; ?>_tab_text">Text &amp; Buttons</a></li>
		<li role="presentation"><a href="#<?php echo $uid; ?>_tab_media" role="tab" data-toggle="tab" aria-controls="<?php echo $uid; ?>_tab_media">Bild &amp; Video</a></li>
	</ul>
	<div class="tab-content gbh-tabs">

		<div role="tabpanel" class="tab-pane active" id="<?php echo $uid; ?>_tab_text">
			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_heading">Überschrift <span class="text-danger">*</span></label>
				<input type="text" name="heading" id="<?php echo $uid; ?>_heading" class="form-control" value="<?php echo $esc('heading', $isNew ? 'Ihre Überschrift' : ''); ?>">
				<p class="help-block small">Ein Wort in *Sternchen* wird hervorgehoben.</p>
			</div>

			<div class="gbh-pair">
				<div class="rex-form-group form-group">
					<label for="<?php echo $uid; ?>_heading_level">Typ der Überschrift</label>
					<select name="heading_level" id="<?php echo $uid; ?>_heading_level" class="form-control">
						<option value="h1"<?php echo $sel($headingLevel, 'h1'); ?>>H1</option>
						<option value="h2"<?php echo $sel($headingLevel, 'h2'); ?>>H2</option>
						<option value="h3"<?php echo $sel($headingLevel, 'h3'); ?>>H3</option>
						<option value="normal"<?php echo $sel($headingLevel, 'normal'); ?>>Normal (einfacher Text, keine Überschrift)</option>
					</select>
				</div>
				<div class="rex-form-group form-group gbh-checks">
					<label>&nbsp;</label>
					<div class="checkbox"><label><input type="checkbox" name="heading_big" value="checked"<?php echo $big ? ' checked' : ''; ?>> Große Überschrift</label></div>
				</div>
			</div>

			<div class="rex-form-group form-group" style="margin-top:15px">
				<label for="<?php echo $uid; ?>_title_color">Farbe der Überschrift <small class="text-muted">(optional)</small></label>
				<?php echo GbHeroKit::colorField($palette, 'title_color', $uid.'_title_color', $val('title_color'), 'bg', 'solid', '#rrggbb oder rgba(…)'); ?>
			</div>

			<div class="rex-form-group form-group gbh-gap">
				<label for="<?php echo $uid; ?>_text">Text</label>
				<textarea name="text" id="<?php echo $uid; ?>_text" rows="4" class="form-control"><?php echo $esc('text'); ?></textarea>
			<div class="checkbox gbh-checks"><label><input type="checkbox" name="text_bold" value="checked"<?php echo $textBold ? ' checked' : ''; ?>> Text fett</label></div>
				<div class="rex-form-group form-group" style="margin-top:15px">
					<label for="<?php echo $uid; ?>_text_color">Farbe des Textes <small class="text-muted">(optional)</small></label>
					<?php echo GbHeroKit::colorField($palette, 'text_color', $uid.'_text_color', $val('text_color'), 'bg', 'solid', '#rrggbb oder rgba(…)'); ?>
				</div>
		</div>

			<details class="gbh-group" style="margin-top:30px">
				<summary>Buttons <small class="text-muted">(höchstens <?php echo GbHeroKit::MAX_BUTTONS; ?>)</small></summary>
				<div class="gbh-group-body">
			<div class="rex-form-group form-group">
				<?php
				echo Gridblock2Builder_Tree::renderRepeater('buttons', $buttons, GbHeroKit::buttonRowMarkup($palette), [
					'collapsible' => true,
					'titleField' => 'titel',
					'titleFallback' => 'Button',
					'collapsed' => true,
					'maxRows' => GbHeroKit::MAX_BUTTONS,
				]);
				?>
			</div>
		<div class="rex-form-group form-group">
			<label for="<?php echo $uid; ?>_effect">Hover-Effekt der Buttons</label>
				<select name="effect" id="<?php echo $uid; ?>_effect" class="form-control">
					<?php foreach (GbHeroKit::EFFECTS as $key => $label): ?>
					<option value="<?php echo $key; ?>"<?php echo $sel($buttonEffect, $key); ?>><?php echo rex_escape($label); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_btn_radius">Eckenrundung der Buttons</label>
				<select name="btn_radius" id="<?php echo $uid; ?>_btn_radius" class="form-control">
					<?php foreach (GbHeroKit::BTN_RADII as $key => $label): ?>
					<option value="<?php echo $key; ?>"<?php echo $sel($btnRadius, $key); ?>><?php echo rex_escape($label); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			</div>
		</details>

			<div class="gbh-groups" style="margin-top:30px">
		<details class="gbh-group"<?php echo $val('eyebrow') !== '' ? ' open' : ''; ?>>
			<summary>Dachzeile <small class="text-muted">(optional)</small></summary>
			<div class="gbh-group-body">
		<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_eyebrow">Dachzeile <small class="text-muted">(kleine Zeile über der Überschrift)</small></label>
				<input type="text" name="eyebrow" id="<?php echo $uid; ?>_eyebrow" class="form-control" value="<?php echo $esc('eyebrow'); ?>">
				<div class="checkbox gbh-checks"><label><input type="checkbox" name="eyebrow_bold" value="checked"<?php echo $eyebrowBold ? ' checked' : ''; ?>> Dachzeile fett</label></div>
			</div>
			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_eyebrow_color">Farbe der Dachzeile <small class="text-muted">(optional)</small></label>
				<?php echo GbHeroKit::colorField($palette, 'eyebrow_color', $uid.'_eyebrow_color', $val('eyebrow_color'), 'bg', 'solid', '#rrggbb oder rgba(…)'); ?>
			</div>
			</div>
		</details>
		<details class="gbh-group">
			<summary>Pluspunkte <small class="text-muted">(optional)</small></summary>
				<div class="gbh-group-body">
			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_bullets">Kurze Pluspunkte <small class="text-muted">(eine pro Zeile, optional)</small></label>
				<textarea name="bullets" id="<?php echo $uid; ?>_bullets" rows="3" class="form-control"><?php echo $esc('bullets'); ?></textarea>
				<div class="checkbox gbh-checks"><label><input type="checkbox" name="bullet_bold" value="checked"<?php echo $bulletBold ? ' checked' : ''; ?>> Pluspunkte fett</label></div>
			</div>

			<div class="gbh-pair">
				<div class="rex-form-group form-group">
					<label for="<?php echo $uid; ?>_bullet_style">Darstellung der Pluspunkte</label>
					<select name="bullet_style" id="<?php echo $uid; ?>_bullet_style" class="form-control">
						<option value="icon"<?php echo $sel($bulletStyle, 'icon'); ?>>Mit Häkchen oder Icon</option>
						<option value="text"<?php echo $sel($bulletStyle, 'text'); ?>>Nur Text, durch Punkte getrennt</option>
					</select>
				</div>
				<div class="rex-form-group form-group">
					<label for="<?php echo $uid; ?>_bullet_pos">Position der Pluspunkte</label>
					<select name="bullet_pos" id="<?php echo $uid; ?>_bullet_pos" class="form-control">
						<option value="unten"<?php echo $sel($bulletPos, 'unten'); ?>>Unter den Buttons (Standard)</option>
						<option value="mitte"<?php echo $sel($bulletPos, 'mitte'); ?>>Zwischen Text und Buttons</option>
					</select>
				</div>
			</div>

			<div class="rex-form-group form-group" data-gbh-bs="icon">
				<label for="<?php echo $uid; ?>_bullet_layout">Anordnung der Pluspunkte</label>
				<select name="bullet_layout" id="<?php echo $uid; ?>_bullet_layout" class="form-control">
					<option value="inline"<?php echo $sel($bulletLayout, 'inline'); ?>>Nebeneinander (Standard)</option>
					<option value="stack"<?php echo $sel($bulletLayout, 'stack'); ?>>Untereinander</option>
				</select>
			</div>

			<div class="rex-form-group form-group" data-gbh-bs="icon">
				<label for="<?php echo $uid; ?>_bullet_icon">Icon der Pluspunkte <small class="text-muted">(optional, gilt für alle Pluspunkte)</small></label>
				<input type="text" name="bullet_icon" id="<?php echo $uid; ?>_bullet_icon" class="form-control rex-fa-iconpicker" data-fa-add-weight="1" data-fa-preview-weight="S" data-fa-onselect="gb2HeroIconPicked" placeholder="z. B. fas fa-check" value="<?php echo $esc('bullet_icon'); ?>">
				<p class="help-block small">Leer = Häkchen.</p>
		</div>

		<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_bullet_color">Farbe der Pluspunkte <small class="text-muted">(optional, leer = Farbe des Textes)</small></label>
				<?php echo GbHeroKit::colorField($palette, 'bullet_color', $uid.'_bullet_color', $val('bullet_color'), 'bg', 'solid', '#rrggbb oder rgba(…)'); ?>
			</div>

			</div>
			</details>
			</div>
			<details class="gbh-group">
				<summary>Sonstiges</summary>
				<div class="gbh-group-body">
			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_accent">Hervorhebungsfarbe <small class="text-muted">(optional)</small></label>
				<?php echo GbHeroKit::colorField($palette, 'accent', $uid.'_accent', $val('accent'), 'bg', 'solid', '#rrggbb oder rgba(…)'); ?>
				<p class="help-block small">Für hervorgehobene Wörter der Überschrift (*Wort*), Häkchen, Icons und Trenner der Pluspunkte sowie den Akzentstreifen bei „Geteilt“.</p>
			</div>
			<div class="gbh-pair">
				<div class="rex-form-group form-group" data-gbh="zentriert vollflaechig">
					<label for="<?php echo $uid; ?>_blockpos">Position des Textblocks</label>
					<select name="blockpos" id="<?php echo $uid; ?>_blockpos" class="form-control">
						<?php foreach (GbHeroKit::BLOCK_POSITIONS as $key => $posItem): ?>
						<option value="<?php echo $key; ?>"<?php echo $sel($blockPos, $key); ?>><?php echo rex_escape($posItem[0]); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="rex-form-group form-group" data-gbh="zentriert vollflaechig">
					<label for="<?php echo $uid; ?>_textalign">Textausrichtung</label>
					<select name="textalign" id="<?php echo $uid; ?>_textalign" class="form-control">
						<option value="links"<?php echo $sel($textAlign, 'links'); ?>>Links</option>
						<option value="mitte"<?php echo $sel($textAlign, 'mitte'); ?>>Mitte</option>
						<option value="rechts"<?php echo $sel($textAlign, 'rechts'); ?>>Rechts</option>
					</select>
				</div>
			</div>
			<div class="gbh-pair">
			<div class="rex-form-group form-group">
				<label for="<?php echo $uid; ?>_text_maxw">Maximale Breite des Textblocks <small class="text-muted">(leer = keine Begrenzung)</small></label>
				<div style="display:flex;gap:8px">
					<input type="number" min="1" max="9999" step="1" name="text_maxw" id="<?php echo $uid; ?>_text_maxw" class="form-control" value="<?php echo $esc('text_maxw'); ?>">
					<span style="flex:0 0 90px">
						<select name="text_maxu" id="<?php echo $uid; ?>_text_maxu" class="form-control" aria-label="Einheit der maximalen Breite">
							<option value="px"<?php echo $sel($textMaxUnit, 'px'); ?>>px</option>
							<option value="%"<?php echo $sel($textMaxUnit, '%'); ?>>%</option>
						</select>
					</span>
				</div>
			</div>
		</div>
		<div class="rex-form-group form-group" data-gbh="vollflaechig geteilt">
			<label for="<?php echo $uid; ?>_inset">Horizontaler Abstand des Textes zum Rand</label>
				<select name="inset" id="<?php echo $uid; ?>_inset" class="form-control">
					<?php foreach (GbHeroKit::INSETS as $key => $label): ?>
					<option value="<?php echo $key; ?>"<?php echo $sel($inset, $key); ?>><?php echo rex_escape($label); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
				</div>
			</details>
	</div>

	<div role="tabpanel" class="tab-pane" id="<?php echo $uid; ?>_tab_media">
			<div class="rex-form-group form-group">
				<label>Bild</label>
				<?php echo rex_var_media::getWidget($uid.'_bild', 'bild', $val('bild'), ['types' => 'jpg,jpeg,png,webp,avif,gif,svg']); ?>
				<p class="help-block small">Alt-Text wird im Medienpool gepflegt. Sichtbaren Teil über die Ausrichtung festlegen.</p>
			</div>

			<details class="gbh-group" data-gbh="nebeneinander zentriert vollflaechig geteilt"<?php echo $val('video') !== '' ? ' open' : ''; ?>>
				<summary>Video <small class="text-muted">(optional)</small></summary>
				<div class="gbh-group-body">
			<div class="rex-form-group form-group">
				<label>Video <small class="text-muted">(nur MP4 aus dem Medienpool)</small></label>
				<?php echo rex_var_media::getWidget($uid.'_video', 'video', $val('video'), ['types' => 'mp4']); ?>
				<p class="help-block small">Ersetzt das Bild, das Bild dient als Standbild. „Vollflächig“: läuft stumm im Hintergrund (mit Pause-Knopf). Sonst im Player ohne Autostart.</p>
			</div>

			<div class="gbh-pair gbh-pair-wide">
				<div class="rex-form-group form-group" data-gbh="nebeneinander zentriert geteilt">
					<label>Untertitel <small class="text-muted">(optional, VTT-Datei)</small></label>
					<?php echo rex_var_media::getWidget($uid.'_vtt', 'vtt', $val('vtt'), ['types' => 'vtt']); ?>
				</div>
				<div class="rex-form-group form-group" data-gbh="nebeneinander zentriert geteilt">
					<label for="<?php echo $uid; ?>_vtt_lang">Sprache der Untertitel</label>
					<select name="vtt_lang" id="<?php echo $uid; ?>_vtt_lang" class="form-control">
						<?php foreach (GbHeroKit::SUBTITLE_LANGS as $key => $label): ?>
						<option value="<?php echo $key; ?>"<?php echo $sel($vttLang, $key); ?>><?php echo rex_escape($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

				</div>
			</details>

			<details class="gbh-group">
				<summary>Sonstiges</summary>
				<div class="gbh-group-body">
			<div class="gbh-pair">
				<div class="rex-form-group form-group">
					<label for="<?php echo $uid; ?>_ratio">Bildformat</label>
					<select name="ratio" id="<?php echo $uid; ?>_ratio" class="form-control">
						<?php foreach (GbHeroKit::RATIOS as $key => $label): ?>
						<option value="<?php echo $key; ?>"<?php echo $sel($ratio, $key); ?>><?php echo rex_escape($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="rex-form-group form-group">
					<label for="<?php echo $uid; ?>_focus">Ausrichtung des Bildes</label>
					<select name="focus" id="<?php echo $uid; ?>_focus" class="form-control">
						<?php foreach (GbHeroKit::FOCUS as $key => $focusItem): ?>
						<option value="<?php echo $key; ?>"<?php echo $sel($focus, $key); ?>><?php echo rex_escape($focusItem[0]); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="rex-form-group form-group" data-gbh="nebeneinander karten geteilt">
					<label for="<?php echo $uid; ?>_media_side">Position des Bildes</label>
					<select name="media_side" id="<?php echo $uid; ?>_media_side" class="form-control">
						<option value="links"<?php echo $sel($side, 'links'); ?>>Links vom Text</option>
						<option value="rechts"<?php echo $sel($side, 'rechts'); ?>>Rechts vom Text</option>
					</select>
				</div>
				<div class="rex-form-group form-group" data-gbh="zentriert">
					<label for="<?php echo $uid; ?>_media_stack">Position des Bildes</label>
					<select name="media_stack" id="<?php echo $uid; ?>_media_stack" class="form-control">
						<option value="ueber"<?php echo $sel($stack, 'ueber'); ?>>Über dem Text</option>
						<option value="unter"<?php echo $sel($stack, 'unter'); ?>>Unter dem Text</option>
					</select>
				</div>
				<div class="rex-form-group form-group" data-gbh="vollflaechig">
					<label for="<?php echo $uid; ?>_overlay">Abdunklung</label>
					<select name="overlay" id="<?php echo $uid; ?>_overlay" class="form-control">
						<?php foreach (GbHeroKit::OVERLAYS as $key => $label): ?>
						<option value="<?php echo $key; ?>"<?php echo $sel($overlay, $key); ?>><?php echo rex_escape($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="rex-form-group form-group" data-gbh="vollflaechig">
					<label for="<?php echo $uid; ?>_overlay_color">Farbe der Abdunklung <small class="text-muted">(optional)</small></label>
					<?php echo GbHeroKit::colorField($palette, 'overlay_color', $uid.'_overlay_color', $val('overlay_color'), 'bg', 'solid', '#rrggbb'); ?>
				</div>
				<div class="rex-form-group form-group" data-gbh="geteilt">
					<label for="<?php echo $uid; ?>_split">Verhältnis von Text und Bild</label>
					<select name="split" id="<?php echo $uid; ?>_split" class="form-control">
						<?php foreach (GbHeroKit::SPLITS as $key => $label): ?>
						<option value="<?php echo $key; ?>"<?php echo $sel($split, $key); ?>><?php echo rex_escape($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="rex-form-group form-group" data-gbh="geteilt">
					<label for="<?php echo $uid; ?>_edge">Kante des Bildes</label>
					<select name="edge" id="<?php echo $uid; ?>_edge" class="form-control">
						<?php foreach (GbHeroKit::EDGES as $key => $label): ?>
						<option value="<?php echo $key; ?>"<?php echo $sel($edge, $key); ?>><?php echo rex_escape($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="rex-form-group form-group gbh-checks" data-gbh="nebeneinander zentriert karten">
				<input type="hidden" name="round_set" value="1">
				<div class="checkbox"><label><input type="checkbox" name="round" value="checked"<?php echo $round ? ' checked' : ''; ?>> Abgerundete Ecken <small class="text-muted">(Bild und Video)</small></label></div>
				<div class="checkbox"><label><input type="checkbox" name="img_shadow" value="checked"<?php echo $imgShadow ? ' checked' : ''; ?>> Schatten am Bild <small class="text-muted">(Bild und Video)</small></label></div>
			</div>
			<p class="help-block small" data-gbh="geteilt">Das Bildformat gilt für die Bildspalte. Der Streifen der Kante hat die Hervorhebungsfarbe (Tab Text &amp; Buttons).</p>
			<p class="help-block small" data-gbh="vollflaechig">Das Bildformat ist die Mindesthöhe der Fläche. Kontrast des weißen Textes prüfen.</p>

			<div class="gbh-sub" data-gbh="karten">
				<div class="rex-form-group form-group">
					<label>Infokarten <small class="text-muted">(höchstens <?php echo GbHeroKit::MAX_CARDS; ?>)</small></label>
					<?php
					echo Gridblock2Builder_Tree::renderRepeater('cards', $cards, GbHeroKit::cardRowMarkup(), [
						'collapsible' => true,
						'titleField' => 'ktext',
						'titleFallback' => 'Karte',
						'collapsed' => true,
						'maxRows' => GbHeroKit::MAX_CARDS,
					]);
					?>
				</div>
				<div class="gbh-pair">
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_card_bg">Kartenhintergrund</label>
						<?php echo GbHeroKit::colorField($palette, 'card_bg', $uid.'_card_bg', $val('card_bg'), 'bg', 'both', '#rrggbb oder rgba(…)'); ?>
					</div>
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_card_color">Kartentext</label>
						<?php echo GbHeroKit::colorField($palette, 'card_color', $uid.'_card_color', $val('card_color'), 'fg', 'solid', '#rrggbb oder rgba(…)'); ?>
					</div>
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_card_pos">Position der Karten</label>
						<select name="card_pos" id="<?php echo $uid; ?>_card_pos" class="form-control">
							<option value="aussen"<?php echo $sel($cardPos, 'aussen'); ?>>Nach außen versetzt</option>
							<option value="innen"<?php echo $sel($cardPos, 'innen'); ?>>Innerhalb des Bildes</option>
						</select>
					</div>
					<div class="rex-form-group form-group">
						<label for="<?php echo $uid; ?>_card_valign">Vertikale Position der Karten</label>
						<select name="card_valign" id="<?php echo $uid; ?>_card_valign" class="form-control">
							<option value="oben"<?php echo $sel($cardV, 'oben'); ?>>Oben</option>
							<option value="mitte"<?php echo $sel($cardV, 'mitte'); ?>>Mitte</option>
							<option value="unten"<?php echo $sel($cardV, 'unten'); ?>>Unten</option>
						</select>
					</div>
				</div>
			</div>
				</div>
			</details>
		</div>

	</div>
</div>
