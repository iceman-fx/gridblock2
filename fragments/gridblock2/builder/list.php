<?php
/*
	Redaxo-Addon Gridblock²
	Fragment: eine Elementliste (Wurzel ODER Kind-Liste einer Sektion/eines Spalten-Slots)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)

	Erwartet im Scope: $this->nodes (array), $this->moduleValues (array), $this->rexVars (array),
	$this->depth (int), $this->maxDepth (int) - rex_fragment stellt gesetzte Vars als Objekt-Property
	bereit (__get()), NICHT als extrahierte lokale Variable.
*/

/** @var rex_fragment $this */

$nodes = (array) $this->nodes;
$depth = (int) $this->depth;

$addBtnSizeClass = ($depth > 0) ? ' btn-xs' : '';
$cardsHtml = Gridblock2Builder_Tree::renderList($nodes, (array) $this->moduleValues, (array) $this->rexVars, $depth);
?>
<div class="gb-builder-list" data-depth="<?php echo $depth; ?>">
	<?php if (empty($nodes)): ?><div class="gb-builder-empty" data-builder-empty><?php echo rex_i18n::rawMsg('a2034_builder_empty'); ?></div><?php endif; ?>
	<?php echo $cardsHtml; ?>
</div>

<div class="gb-builder-adder" data-depth="<?php echo $depth; ?>">
	<button type="button" class="btn btn-default<?php echo $addBtnSizeClass; ?> gb-builder-add-toggle" aria-haspopup="true" aria-expanded="false"><i class="rex-icon fa-plus" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_builder_add'); ?></button>
</div>
