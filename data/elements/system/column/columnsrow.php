<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe Spalten-Zeile: .gridblock > .tmpl > .row > .col (Spalten kommen als $_gb->content aus column.php)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

require_once __DIR__ . '/../_gbOutputHelper.php';

$a = GbOutputKit::attributes($_gb->cs, 'template');

$cssNative   = ($_gb->isNative) ? rex_escape($_gb->nativeClass) : '';
$styleNative = ($_gb->isNative) ? ' style="--gb-col-count:'.(int) $_gb->columnCount.'"' : '';
?>


<div class="gridblock <?= rex_escape($_gb->columnsrowClass); ?>">
	<div class="tmpl<?= ($a['class'] !== '') ? ' '.$a['class'] : ''; ?>"<?= ($a['style'] !== '') ? ' style="'.rex_escape($a['style']).'"' : ''; ?>>
		<div class="row <?= $cssNative; ?>"<?= $styleNative; ?>>
			<?= @$_gb->content; ?>
		</div>
	</div>
</div>
