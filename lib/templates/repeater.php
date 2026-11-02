<?php
/*
	Redaxo-Addon Gridblock²
	Vendor-Kopie: HTML-Repeater-Chrome-Fragments (Original: _fm_helper/fragments/
				  fm_helper/repeater.php), siehe Gridblock2Builder_VendorRepeater.php-Kommentar.
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)

	Erwartet im Scope (siehe Gridblock2Builder_VendorRepeater::render()): $name, $rows, $emptyRowHtml, $options
*/

/** @var string $name */
/** @var array<int, array<string, mixed>> $rows */
/** @var string $emptyRowHtml */
/** @var array<string, mixed> $options */

$renderToolbar = function (bool $collapsed = false) use ($options) {
	?>
	<div class="fmrep-item-toolbar">
		<?php if ($options['sortable']): ?>
		<span class="fmrep-item-draghandle" role="button" tabindex="0" title="Verschieben"><span class="fmrep-item-draghandle-dots"></span></span>
		<?php endif; ?>
		<?php if (!empty($options['collapsible'])): ?>
		<span class="fmrep-item-title" role="button" tabindex="0" aria-expanded="<?php echo $collapsed ? 'false' : 'true'; ?>" title="Ein-/Ausklappen"><i class="rex-icon fa-chevron-down fmrep-item-title-chevron" aria-hidden="true"></i><span class="fmrep-item-title-text"></span></span>
		<?php endif; ?>
		<span class="fmrep-item-add" role="button" tabindex="0" title="Zeile hinzufügen"><i class="rex-icon fa-plus" aria-hidden="true"></i></span>
		<?php if ($options['duplicate']): ?>
		<span class="fmrep-item-duplicate" role="button" tabindex="0" title="Duplizieren"><i class="rex-icon fa-copy" aria-hidden="true"></i></span>
		<?php endif; ?>
		<?php if ($options['offline']): ?>
		<span class="fmrep-item-offline-toggle" role="button" tabindex="0" title="Online/Offline schalten"><i class="rex-icon fa-eye" aria-hidden="true"></i></span>
		<?php endif; ?>
		<?php if ($options['movebuttons']): ?>
		<span class="fmrep-item-moveup" role="button" tabindex="0" title="Nach oben verschieben"><i class="rex-icon fa-arrow-up" aria-hidden="true"></i></span>
		<span class="fmrep-item-movedown" role="button" tabindex="0" title="Nach unten verschieben"><i class="rex-icon fa-arrow-down" aria-hidden="true"></i></span>
		<?php endif; ?>
		<span class="fmrep-item-remove" role="button" tabindex="0" title="Entfernen" data-confirm="Zeile wirklich entfernen?"><i class="rex-icon rex-icon-delete" aria-hidden="true"></i></span>
	</div>
	<?php
};
?>
<div class="fmrep" data-repeater-name="<?php echo htmlspecialchars($name); ?>" data-repeater-duplicate="<?php echo $options['duplicate'] ? '1' : '0'; ?>" data-repeater-offline="<?php echo $options['offline'] ? '1' : '0'; ?>" data-repeater-sortable="<?php echo $options['sortable'] ? '1' : '0'; ?>"<?php if ((int) $options['maxRows'] > 0): ?> data-repeater-max="<?php echo (int) $options['maxRows']; ?>"<?php endif; ?><?php if (!empty($options['collapsible'])): ?> data-repeater-collapsible="1" data-repeater-title="<?php echo htmlspecialchars((string) $options['titleField']); ?>" data-repeater-title-fallback="<?php echo htmlspecialchars((string) $options['titleFallback']); ?>"<?php endif; ?>>
	<input type="hidden" name="<?php echo htmlspecialchars($name); ?>" class="fmrep-json" value="<?php echo htmlspecialchars((string) ($initialJson ?? ''), ENT_QUOTES); ?>">

	<div class="fmrep-list">
		<?php foreach ($rows as $row): $uid = (string) ($row['uid'] ?? Gridblock2Builder_VendorRepeater::makeUid()); $collapsed = !empty($options['collapsible']) && !empty($options['collapsed']) && empty($row['isNew']); ?>
		<div class="fmrep-item<?php echo !empty($row['disabled']) ? ' fmrep-item-offline' : ''; echo $collapsed ? ' fmrep-item-collapsed' : ''; ?>" data-uid="<?php echo htmlspecialchars($uid); ?>">
			<?php $renderToolbar($collapsed); ?>
			<div class="fmrep-item-body"><?php echo $row['html'] ?? ''; ?></div>
		</div>
		<?php endforeach; ?>
	</div>

	<div class="fmrep-empty"><span class="fmrep-empty-add" role="button" tabindex="0"><i class="rex-icon fa-plus" aria-hidden="true"></i> Element hinzufügen</span></div>

	<template class="fmrep-item-template"><div class="fmrep-item" data-uid="<?php echo Gridblock2Builder_VendorRepeater::UID_TOKEN; ?>">
		<?php $renderToolbar(); ?>
		<div class="fmrep-item-body"><?php echo $emptyRowHtml; ?></div>
	</div></template>
</div>
