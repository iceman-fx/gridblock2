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

$layout = (string) ($v['layout'] ?? 'einzel');
if (!in_array($layout, ['einzel', 'raster', 'slider'], true)) {
	$layout = 'einzel';
}
$cols = (string) ($v['cols'] ?? '3');
if (!in_array($cols, ['1', '2', '3', '4'], true)) {
	$cols = '3';
}
$align = (string) ($v['align'] ?? 'links');
if (!in_array($align, ['links', 'mitte', 'rechts'], true)) {
	$align = 'links';
}
$imgpos = ((string) ($v['imgpos'] ?? 'links')) === 'rechts' ? 'rechts' : 'links';
$round = !empty($v['imground']);
$noBg = !empty($v['nobg']);

$sel = static fn (string $current, string $option): string => $current === $option ? ' selected' : '';

$rows = $v['zitate'] ?? '';
if (!array_key_exists('zitate', $v)) {
	$rows = [];
	$legacy = ['text' => (string) ($v['text'] ?? ''), 'author' => (string) ($v['author'] ?? ''), 'source' => (string) ($v['source'] ?? '')];
	if (trim($legacy['text']) !== '' || trim($legacy['author']) !== '' || trim($legacy['source']) !== '') {
		$rows[Gridblock2Builder_Tree::makeRepeaterUid()] = $legacy;
	} else {
		$rows[Gridblock2Builder_Tree::makeRepeaterUid()] = ['text' => '', 'author' => ''];
	}
}
$rowCount = is_array($rows) ? count($rows) : count((array) json_decode((string) $rows, true));
?>
<style>
.gbz-opts [data-gbz-show]{display:none!important}
.gbz-opts:has(select[name="layout"] option[value="raster"]:checked) [data-gbz-show~="cols"],
.gbz-opts:has(select[name="layout"] option[value="slider"]:checked) [data-gbz-show~="cols"],
.gbz-opts:has(select[name="layout"] option[value="einzel"]:checked) [data-gbz-show~="single"]{display:block!important}
.gbz-pair{display:flex;flex-wrap:wrap;gap:0 20px}
.gbz-pair>.rex-form-group{flex:1 1 200px;min-width:0}
.gbz-opts{margin-bottom:25px}
.gbz-settings{margin-top:45px}
</style>

<div class="gbz-opts">
	<div class="gbz-pair">
		<div class="rex-form-group form-group">
			<label for="<?php echo $uid; ?>_layout">Darstellung</label>
			<select name="layout" id="<?php echo $uid; ?>_layout" class="form-control">
				<option value="einzel"<?php echo $sel($layout, 'einzel'); ?>>Einzeln (ein Zitat, groß)</option>
				<option value="raster"<?php echo $sel($layout, 'raster'); ?>>Raster</option>
				<option value="slider"<?php echo $sel($layout, 'slider'); ?>>Streifen (scrollbar)</option>
			</select>
		</div>
		<div class="rex-form-group form-group" data-gbz-show="cols">
			<label for="<?php echo $uid; ?>_cols">Spalten</label>
			<select name="cols" id="<?php echo $uid; ?>_cols" class="form-control">
				<?php foreach (['1', '2', '3', '4'] as $n): ?>
				<option value="<?php echo $n; ?>"<?php echo $sel($cols, $n); ?>><?php echo $n; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<p class="help-block small" data-gbz-show="single">Bei „Einzeln“ erscheint nur das erste aktive Zitat der Liste.</p>
	<p class="help-block small" data-gbz-show="cols">Raster: höchstens so viele Spalten. Streifen: so viele Zitate gleichzeitig sichtbar. Auf schmalem Platz werden es automatisch weniger.</p>
</div>

<div class="rex-form-group form-group">
	<label>Zitate</label>
	<?php
	$rowMarkup = <<<'HTML'
		<div class="rex-form-group form-group">
			<label>Zitat <span class="text-danger">*</span></label>
			<textarea name="text" rows="4" class="form-control">{{text}}</textarea>
		</div>
		<div class="gbz-pair">
			<div class="rex-form-group form-group">
				<label>Autor <small class="text-muted">(optional)</small></label>
				<input type="text" name="author" value="{{author}}" class="form-control">
			</div>
			<div class="rex-form-group form-group">
				<label>Funktion <small class="text-muted">(optional)</small></label>
				<input type="text" name="role" value="{{role}}" class="form-control" placeholder="z. B. Geschäftsführerin, Musterfirma GmbH">
			</div>
		</div>
		<div class="rex-form-group form-group">
			<label>Quelle <small class="text-muted">(optional)</small></label>
			<input type="text" name="source" value="{{source}}" class="form-control" placeholder="z. B. Süddeutsche Zeitung, 12.03.2026">
		</div>
		<div class="rex-form-group form-group">
			<label>Link der Quelle <small class="text-muted">(optional, extern)</small></label>
			REX_MULTILINK[id="link" types="url"]
		</div>
		<div class="rex-form-group form-group">
			<label>Bild / Logo <small class="text-muted">(optional, wird quadratisch 1:1 dargestellt)</small></label>
			REX_MEDIA[id="bild" types="jpg,jpeg,png,webp,avif,gif,svg"]
		</div>
		HTML;
	echo Gridblock2Builder_Tree::renderRepeater('zitate', $rows, $rowMarkup, [
		'collapsible' => true,
		'titleField' => 'author,text',
		'titleFallback' => 'Zitat',
		'collapsed' => $rowCount > 1,
	]);
	?>
</div>

<div class="gbz-settings">
<label>Einstellungen</label>
<div class="gbz-pair">
	<div class="rex-form-group form-group">
		<label for="<?php echo $uid; ?>_align">Ausrichtung</label>
		<select name="align" id="<?php echo $uid; ?>_align" class="form-control">
			<option value="links"<?php echo $sel($align, 'links'); ?>>Links</option>
			<option value="mitte"<?php echo $sel($align, 'mitte'); ?>>Mitte</option>
			<option value="rechts"<?php echo $sel($align, 'rechts'); ?>>Rechts</option>
		</select>
	</div>
	<div class="rex-form-group form-group">
		<label for="<?php echo $uid; ?>_imgpos">Bild steht</label>
		<select name="imgpos" id="<?php echo $uid; ?>_imgpos" class="form-control">
			<option value="links"<?php echo $sel($imgpos, 'links'); ?>>Links vom Autor</option>
			<option value="rechts"<?php echo $sel($imgpos, 'rechts'); ?>>Rechts vom Autor</option>
		</select>
	</div>
</div>

<div class="rex-form-group form-group">
	<div class="checkbox"><label><input type="checkbox" name="imground" value="checked"<?php echo $round ? ' checked' : ''; ?>> Bilder rund darstellen <small class="text-muted">(Standard: eckig)</small></label></div>
</div>

<div class="rex-form-group form-group">
	<div class="checkbox"><label><input type="checkbox" name="nobg" value="checked"<?php echo $noBg ? ' checked' : ''; ?>> Hintergrundfarben ausschalten <small class="text-muted">(der feine Rahmen bleibt)</small></label></div>
</div>
</div>
