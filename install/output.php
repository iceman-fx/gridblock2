<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/* GRID_MODULE_IDENTIFIER | DONT REMOVE */


//Vorgaben
$grid = new rex_gridblock2();
$grid->getSliceValues("REX_SLICE_ID");

$sliceID = (int) ($grid->rexVars['sliceID'] ?? 0);
$sliceValues = Gridblock2Builder_Tree::loadSliceValues($sliceID);


if (rex::isBackend()):
	//optionale Korrektur der .row-Darstellung im BE
	echo '<style>.rex-slice .panel-default .panel-body div.row { margin-left: 0px; margin-right: 0px; }</style>';
endif;


if (Gridblock2Builder_Tree::hasNewFormat($sliceValues)):
	$structure 		 = Gridblock2Builder_Tree::loadStructure($sliceValues);
	$moduleValues 	 = Gridblock2Builder_Tree::loadModuleValues($sliceValues);
	$useSlicePreview = rex::isBackend() && rex_gridblock2::getConfig('gb_builder_slicelist_preview') === 'checked';
		if ($useSlicePreview) { rex_gridblock2::isBuilderPreview(true); }
		
	$html = Gridblock2Builder_Tree::renderOutput($structure, $moduleValues, $grid->rexVars, $useSlicePreview);
	if ($useSlicePreview):
		$payload = Gridblock2Builder_Preview::renderIframeEmbedPayload($html, 0, '');
		echo '<div class="gb-slicepreview-host" data-slice-id="'.$sliceID.'">'.$payload.'</div>';
	else:
		echo $html;
	endif;
	
else:
	//Legacy-Format: unveränderter alter Ausgabepfad
	echo $grid->getModuleOutput();
endif;
?>
