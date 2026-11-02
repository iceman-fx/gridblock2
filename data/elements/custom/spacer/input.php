<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Eingabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

$hoehe = (string) (@$_gb->values['hoehe'] ?: '40');
?>

<div class="rex-form-group form-group">
	<label for="<?php echo $_gb->uid; ?>_hoehe">Höhe</label>
	<select name="hoehe" id="<?php echo $_gb->uid; ?>_hoehe" class="form-control">
		<option value="20" <?php echo ($hoehe === '20') ? 'selected' : ''; ?>>Klein (20px)</option>
		<option value="40" <?php echo ($hoehe === '40') ? 'selected' : ''; ?>>Mittel (40px)</option>
		<option value="80" <?php echo ($hoehe === '80') ? 'selected' : ''; ?>>Groß (80px)</option>
		<option value="120" <?php echo ($hoehe === '120') ? 'selected' : ''; ?>>Sehr groß (120px)</option>
	</select>
</div>
