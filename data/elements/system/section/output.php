<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe Sektion: generischer, optionaler Container um beliebigen Inhalt (Module, Custom-Elemente, Spalten)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

require_once __DIR__ . '/../_gbOutputHelper.php';

$a = GbOutputKit::attributes($_gb->cs, 'section');

$sectionId = substr((string) preg_replace('/[^A-Za-z0-9_-]/', '', ltrim(trim((string) ($_gb->cs->section->sectionId ?? '')), '#')), 0, 64);
$sectionId = (preg_match('/^[A-Za-z]/', $sectionId) && (rex::isFrontend() || rex_gridblock2::isBuilderPreview())) ? ' id="'.$sectionId.'"' : '';
?>


<div class="<?= rex_escape($_gb->nativeClass); ?>"<?= $sectionId; ?>>
	<div class="gb-el-section-box<?= ($a['class'] !== '') ? ' '.$a['class'] : ''; ?>"<?= ($a['style'] !== '') ? ' style="'.rex_escape($a['style']).'"' : ''; ?>>
		<?= @$_gb->content; ?>
	</div>
</div>
