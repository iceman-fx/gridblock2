<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe Einzelne Spalte: Breite kommt aus der Spaltenvorlage ($_gb->width), die Zeile (columnsrow.php) umschließt alle Spalten
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

require_once __DIR__ . '/../_gbOutputHelper.php';

$a = GbOutputKit::attributes($_gb->cs, 'column', (int) $_gb->index);

$style = ($_gb->isNative ? '--gb-col-width:'.((float) $_gb->width).'%;' : '').$a['style'];
?>


<div class="col<?= ($_gb->nativeClass !== '') ? ' '.rex_escape($_gb->nativeClass) : ''; ?><?= ($a['class'] !== '') ? ' '.$a['class'] : ''; ?>"<?= ($style !== '') ? ' style="'.rex_escape($style).'"' : ''; ?>>
	<div><?= $_gb->content; ?></div>
</div>
