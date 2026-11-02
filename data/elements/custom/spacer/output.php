<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

$hoehe = (int) ($_gb->values['hoehe'] ?? 40);
if ($hoehe <= 0) {
	$hoehe = 40;
}
?>
<div class="gb-el-spacer" style="height:<?php echo $hoehe; ?>px" aria-hidden="true"></div>
