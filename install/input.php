<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Eingabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/* GRID_MODULE_IDENTIFIER | DONT REMOVE */


//Vorgaben
$grid = new rex_gridblock2();
$grid->getSliceValues("REX_SLICE_ID");
$grid->getModules();

$sliceID 		= (int) ($grid->rexVars['sliceID'] ?? 0);
$sliceValues 	= Gridblock2Builder_Tree::loadSliceValues($sliceID);

$structure 		= Gridblock2Builder_Tree::loadStructure($sliceValues);
$moduleValues 	= Gridblock2Builder_Tree::loadModuleValues($sliceValues);
$hideInfoTexts	= rex_gridblock2::getConfig('hideinfotexts');


//Ausgabe
?>


<?php if ($hideInfoTexts != 'checked'): ?>
Bitte wählen Sie die gewünschte Layoutvorlage aus und hinterlegen Ihre Inhalte in den entsprechenden Spalten. Speichern Sie Ihre Änderungen mit "Block speichern" bzw. "Block übernehmen".
<br><br>
<?php endif; ?>


<?php echo Gridblock2Builder_Tree::renderRoot($structure, $moduleValues, $grid->rexVars); ?>


<?php if ($hideInfoTexts != 'checked'): ?>
<br><br>
<strong>Quickinfo:</strong><br>
Mit diesem Modul legen Sie an der entsprechenden Stelle verschiedene Inhalte in einem Spaltenraster an.
<br><br>
<?php endif; ?>