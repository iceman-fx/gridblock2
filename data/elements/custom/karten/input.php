<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Eingabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

$v = (array) ($_gb->values ?? []);
$gap = (string) ($v['gap'] ?? '24');
?>

<div class="rex-form-group form-group">
	<label>Karten</label>
	<?php
	$rowMarkup = <<<'HTML'
		<div class="rex-form-group form-group">
			<label>Bild / Icon (auch SVG möglich)</label>
			REX_MEDIA[id="bild" types="jpg,jpeg,png,webp,svg"]
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
	echo Gridblock2Builder_Tree::renderRepeater('cards', $v['cards'] ?? '', $rowMarkup);
	?>
</div>


<div class="rex-form-group form-group">
	<label for="<?php echo $_gb->uid; ?>_gap">Abstand zwischen den Karten</label>
	<select name="gap" id="<?php echo $_gb->uid; ?>_gap" class="form-control">
		<option value="12" <?php echo ($gap === '12') ? 'selected' : ''; ?>>Klein (12px)</option>
		<option value="24" <?php echo ($gap === '24') ? 'selected' : ''; ?>>Mittel (24px)</option>
		<option value="40" <?php echo ($gap === '40') ? 'selected' : ''; ?>>Groß (40px)</option>
	</select>
</div>

