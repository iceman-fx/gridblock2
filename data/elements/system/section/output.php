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
?>


<div class="<?= rex_escape($_gb->nativeClass); ?>">
	<div class="gb-el-section-box<?= ($a['class'] !== '') ? ' '.$a['class'] : ''; ?>"<?= ($a['style'] !== '') ? ' style="'.rex_escape($a['style']).'"' : ''; ?>>
		<?= @$_gb->content; ?>
	</div>
</div>
