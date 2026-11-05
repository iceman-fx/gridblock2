<?php
/*
	Redaxo-Addon Gridblock²
	Fragment: eine einzelne Karte (echtes Modul, Sektion oder Spalten-Element)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)

	Erwartet im Scope: $type ("module"|"section"|"columns"), $uid, $label, $status (int), $depth (int),
	$formHtml (Bearbeiten-/Einstellungsformular, im Body versteckt), $childrenHtml (bei section/columns:
	die verschachtelte(n) Liste(n) - IMMER sichtbar, nicht im Bearbeiten-Toggle versteckt), optional
	$layout (nur bei columns)
*/

/** @var rex_fragment $this */

$type = (string) $this->type;
$uid = (string) $this->uid;
$label = (string) $this->label;
$status = (int) $this->status;
$depth = (int) $this->depth;
$formHtml = (string) $this->formHtml;
$childrenHtml = (string) $this->childrenHtml;
$layout = (array) $this->layout;
$gapCssValue = (string) $this->gapCssValue;
$moduleId = (int) $this->moduleId;
$elementType = (string) ($this->elementType ?? '');
$editable = (bool) ($this->editable ?? true);
$unavailableText = (string) ($this->unavailableText ?? '');
$quickswitchEnabled = (bool) $this->quickswitchEnabled;
$quickswitchMenu = (string) $this->quickswitchMenu;

$settingsFormHtml = (string) ($this->settingsFormHtml ?? '');
$settingsKeepHtml = (string) ($this->settingsKeepHtml ?? '');

$locked = (bool) ($this->locked ?? false);
$templateLocked = (bool) ($this->templateLocked ?? false);
$noEdit = $locked || $templateLocked;
$isContainer = ($type === 'section' || $type === 'columns');
$hasSettings = !$noEdit && ($isContainer ? ($formHtml !== '') : ($settingsFormHtml !== ''));
$offlineClass = (!$status) ? ' gb-builder-block-offline' : '';
$containerClass = ($isContainer) ? ' gb-builder-block-container' : '';

$draghandleEnabled = (rex_gridblock2::getConfig('gb_builder_draghandle') === 'checked');
$offlineToggleEnabled = (rex_gridblock2::getConfig('gb_builder_offlinetoggle') === 'checked');
$copyPasteEnabled = (rex_gridblock2::getConfig('gb_builder_copypaste') === 'checked');
$hideLabel = (rex_gridblock2::getConfig('gb_builder_hidelabel') === 'checked');
$compactToolbox = (rex_gridblock2::getConfig('gb_builder_compacttoolbox') === 'checked');
$toolbarInnerClass = ($compactToolbox) ? ' gb-builder-toolbox-compact' : '';
if ($noEdit) {
	$copyPasteEnabled = false;
	$offlineToggleEnabled = false;
}
$handleClass = ($draghandleEnabled) ? ' gb-builder-block-hashandle' : ' gb-builder-block-nohandle';

$labelTruncated = (mb_strlen($label) > 30) ? mb_substr($label, 0, 30).'…' : $label;
?>
<div class="gb-builder-block<?php echo $handleClass.$offlineClass.$containerClass; ?>" tabindex="-1" data-type="<?php echo rex_escape($type); ?>" data-uid="<?php echo rex_escape($uid); ?>" data-depth="<?php echo (int) $depth; ?>" data-status="<?php echo (int) $status; ?>"<?php if ($type === 'module'): ?> data-module-id="<?php echo (int) ($moduleId ?? 0); ?>"<?php endif; ?><?php if ($type === 'element'): ?> data-element-type="<?php echo rex_escape($elementType); ?>"<?php endif; ?><?php if ($unavailableText !== ''): ?> data-unavailable="1" data-unavailable-text="<?php echo rex_escape($unavailableText); ?>"<?php endif; ?><?php if ($type === 'columns'): ?> data-layout='<?php echo htmlspecialchars(json_encode($layout ?? []), ENT_QUOTES); ?>'<?php endif; ?>>
	<?php if ($draghandleEnabled): ?>
	<div class="gb-builder-block-draghandle" aria-hidden="true" title="<?php echo rex_i18n::msg('a2034_mod_move_modul_drag'); ?>"><span class="gb-builder-block-draghandle-dots"></span></div>
	<?php endif; ?>
	<div class="gb-builder-block-toolbar">
		<div class="gb-builder-block-toolbar-inner<?php echo $toolbarInnerClass; ?>">
			<?php if ($draghandleEnabled): ?>
			<span class="gb-builder-block-drag" aria-hidden="true" title="<?php echo rex_i18n::msg('a2034_mod_move_modul_drag'); ?>"><span class="gb-builder-block-drag-dots"></span></span>
			<?php endif; ?>
			<?php
			$quickswitchWidths = array_values((array) ($layout['columns'] ?? []));
			$quickswitchApplicable = ($quickswitchEnabled && ($layout['mode'] ?? 'ratio') === 'ratio');
			?>
			<?php if ($quickswitchApplicable):
			?>
			<span class="dropdown gb-builder-columns-quickswitch<?php echo ($quickswitchMenu === '' || count($quickswitchWidths) < 2) ? ' gb-builder-columns-quickswitch-empty' : ''; ?>" title="<?php echo rex_i18n::msg('a2034_builder_columns_quickswitch'); ?>">
				<span class="gb-builder-columns-quickswitch-toggle" role="button" tabindex="0" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<svg width="16" height="16" viewBox="0 0 640 640" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
						<g transform="matrix(1.15033,0,0,1.15033,-48.1694,-48.1069)"><path d="M96,227.723L96,160C96,124.7 124.7,96 160,96L480,96C515.3,96 544,124.7 544,160L544,227.699L496.368,180.067L496.368,142.162L335.632,142.162L335.632,224.557L304.368,224.557L304.368,142.162L143.632,142.162L143.632,180.09L96,227.723ZM544,412.395L544,480C544,515.3 515.3,544 480,544L160,544C124.7,544 96,515.3 96,480L96,412.277L143.632,459.91L143.632,496.368L304.368,496.368L304.368,415.443L335.632,415.443L335.632,496.368L496.368,496.368L496.368,460.027L544,412.395Z" fill="currentColor"/></g>
						<g transform="matrix(1.02784,0,0,1,-8.90578,0.1)"><path d="M502.6,438.6L598.6,342.6C611.1,330.1 611.1,309.8 598.6,297.3L502.6,201.3C490.1,188.8 469.8,188.8 457.3,201.3C444.8,213.8 444.8,234.1 457.3,246.6L498.7,288L141.2,288L182.6,246.6C195.1,234.1 195.1,213.8 182.6,201.3C170.1,188.8 149.8,188.8 137.3,201.3L41.3,297.3C35.3,303.3 31.9,311.4 31.9,319.9C31.9,328.4 35.3,336.5 41.3,342.5L137.3,438.5C149.8,451 170.1,451 182.6,438.5C195.1,426 195.1,405.7 182.6,393.2L141.2,351.8L498.7,351.8L457.3,393.2C444.8,405.7 444.8,426 457.3,438.5C469.8,451 490.1,451 502.6,438.5L502.6,438.6Z" fill="currentColor" fill-rule="nonzero"/></g>
					</svg>
				</span>
				<ul class="dropdown-menu gb-builder-columns-quickswitch-menu" role="menu"><?php echo $quickswitchMenu; ?></ul>
			</span>
			<?php endif; ?>
			<?php if (!$hideLabel): ?>
			<span class="gb-builder-block-label" title="<?php echo rex_escape($label); ?>"><?php echo rex_escape($labelTruncated); ?></span>
			<?php endif; ?>
			<span class="gb-builder-adder gb-builder-inline-adder gb-builder-block-add">
				<span class="gb-builder-add-toggle" role="button" tabindex="0" title="<?php echo rex_i18n::msg('a2034_builder_add'); ?>"><i class="rex-icon fa-plus" aria-hidden="true"></i></span>
			</span>
			<?php if (!$isContainer && $editable): ?>
			<span role="button" tabindex="0" class="gb-builder-block-edit" title="<?php echo rex_i18n::msg('a2034_mod_edit_modul'); ?>"><i class="rex-icon fa-pencil" aria-hidden="true"></i></span>
			<?php elseif (!$isContainer): ?>
			<span class="gb-builder-block-edit-disabled" title="<?php echo rex_i18n::msg('a2034_mod_edit_disabled'); ?>" aria-disabled="true"><i class="rex-icon fa-pencil" aria-hidden="true"></i></span>
			<?php endif; ?>
			<?php if ($hasSettings): ?>
			<span role="button" tabindex="0" class="gb-builder-block-settings" title="<?php echo rex_i18n::msg('a2034_builder_settings'); ?>"><i class="rex-icon fa-cog" aria-hidden="true"></i></span>
			<?php endif; ?>
			<?php if (!$compactToolbox): ?>
			<?php if ($copyPasteEnabled): ?>
			<span role="button" tabindex="0" class="gb-builder-block-copy" title="<?php echo rex_i18n::msg('a2034_mod_copy_modul'); ?>"><i class="rex-icon fa-copy" aria-hidden="true"></i></span>
			<?php endif; ?>
			<span role="button" tabindex="0" class="gb-builder-block-moveup" title="<?php echo rex_i18n::msg('a2034_mod_move_modul_up'); ?>"><i class="rex-icon fa-arrow-up" aria-hidden="true"></i></span>
			<span role="button" tabindex="0" class="gb-builder-block-movedown" title="<?php echo rex_i18n::msg('a2034_mod_move_modul_down'); ?>"><i class="rex-icon fa-arrow-down" aria-hidden="true"></i></span>
			<?php if ($offlineToggleEnabled): ?>
			<span role="button" tabindex="0" class="gb-builder-block-toggleoffline<?php echo (!$status) ? ' is-offline' : ''; ?>" title="<?php echo rex_i18n::msg('a2034_mod_status_modul'); ?>"><i class="rex-icon <?php echo (!$status) ? 'fa-eye-slash' : 'fa-eye'; ?>" aria-hidden="true"></i></span>
			<?php endif; ?>
			<?php endif; ?>
			<?php if (!$locked): ?>
			<span role="button" tabindex="0" class="gb-builder-block-remove" title="<?php echo rex_i18n::msg('a2034_mod_delete_modul'); ?>" data-confirm="<?php echo rex_i18n::msg('a2034_mod_confirm_delete'); ?>"><i class="rex-icon fa-trash-can" aria-hidden="true"></i></span>
			<?php endif; ?>
			<span class="dropdown gb-builder-block-kebab">
				<span class="gb-builder-block-kebab-toggle" role="button" tabindex="0" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="<?php echo rex_i18n::msg('a2034_builder_more'); ?>"><i class="rex-icon fa-ellipsis-v" aria-hidden="true"></i></span>
				<ul class="dropdown-menu gb-builder-block-kebab-menu">
					<li class="gb-builder-block-kebab-label" title="<?php echo rex_escape($label); ?>"><?php echo rex_escape($labelTruncated); ?></li>
					<li>
						<span class="gb-builder-adder gb-builder-inline-adder gb-builder-block-add">
							<span class="gb-builder-add-toggle" role="button" tabindex="0"><i class="rex-icon fa-plus" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_builder_add'); ?></span>
						</span>
					</li>
					<?php if (!$isContainer && $editable): ?>
					<li><span role="button" tabindex="0" class="gb-builder-block-edit"><i class="rex-icon fa-pencil" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_mod_edit_modul'); ?></span></li>
					<?php elseif (!$isContainer): ?>
					<li><span class="gb-builder-block-edit-disabled" aria-disabled="true" title="<?php echo rex_i18n::msg('a2034_mod_edit_disabled'); ?>"><i class="rex-icon fa-pencil" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_mod_edit_modul'); ?></span></li>
					<?php endif; ?>
					<?php if ($hasSettings): ?>
					<li><span role="button" tabindex="0" class="gb-builder-block-settings"><i class="rex-icon fa-cog" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_builder_settings'); ?></span></li>
					<?php endif; ?>
					<?php if ($copyPasteEnabled): ?>
					<li><span role="button" tabindex="0" class="gb-builder-block-copy"><i class="rex-icon fa-copy" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_mod_copy_modul'); ?></span></li>
					<?php endif; ?>
					<?php if (Gridblock2Presets::canSave()): ?>
					<li><span role="button" tabindex="0" class="gb-builder-block-favorite"><i class="rex-icon fa-star" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_fav_save'); ?></span></li>
					<?php endif; ?>
					<li><span role="button" tabindex="0" class="gb-builder-block-moveup"><i class="rex-icon fa-arrow-up" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_mod_move_modul_up'); ?></span></li>
					<li><span role="button" tabindex="0" class="gb-builder-block-movedown"><i class="rex-icon fa-arrow-down" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_mod_move_modul_down'); ?></span></li>
					<?php if ($offlineToggleEnabled): ?>
					<li><span role="button" tabindex="0" class="gb-builder-block-toggleoffline<?php echo (!$status) ? ' is-offline' : ''; ?>"><i class="rex-icon <?php echo (!$status) ? 'fa-eye-slash' : 'fa-eye'; ?>" aria-hidden="true"></i> <span class="gb-builder-block-toggleoffline-label"><?php echo rex_i18n::msg($status ? 'a2034_builder_setoffline' : 'a2034_builder_setonline'); ?></span></span></li>
					<?php endif; ?>
					<?php if (!$locked): ?>
					<li><span role="button" tabindex="0" class="gb-builder-block-remove" data-confirm="<?php echo rex_i18n::msg('a2034_mod_confirm_delete'); ?>"><i class="rex-icon fa-trash-can" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_mod_delete_modul'); ?></span></li>
					<?php endif; ?>
				</ul>
			</span>
		</div>
	</div>

	<?php if ($type === 'module' || $type === 'element'): ?>
	<div class="gb-builder-block-preview" data-builder-preview data-loading-label="<?php echo rex_i18n::msg('a2034_builder_preview_loading'); ?>"></div>
	<div class="gb-builder-block-body gb-builder-block-form" style="display:none;">
		<div class="gb-builder-block-modulename"><span class="gb-builder-block-modulename-text"><?php echo rex_escape($label); ?></span><?php if ($settingsFormHtml !== ''): ?><button type="button" class="btn btn-primary btn-xs gb-builder-block-modulename-settings" title="<?php echo rex_i18n::msg('a2034_builder_settings'); ?>" aria-label="<?php echo rex_i18n::msg('a2034_builder_settings'); ?>"><i class="rex-icon fa-cog" aria-hidden="true"></i></button><?php endif; ?></div>
		<?php echo $formHtml; ?>
	</div>
	<div class="gb-builder-block-editfooter" style="display:none;">
		<button type="button" class="btn btn-default gb-builder-block-cancelbtn"><?php echo rex_i18n::msg('a2034_builder_cancel_element'); ?></button>
		<button type="button" class="btn btn-primary gb-builder-block-savebtn"><i class="rex-icon fa-check" aria-hidden="true"></i> <?php echo rex_i18n::msg('a2034_builder_save_element'); ?></button>
	</div>
	<?php if ($settingsFormHtml !== ''): ?>
	<div class="gb-builder-block-settingsform" style="display:none;"><?php echo $settingsFormHtml; ?></div>
	<?php endif; ?>
	<?php if ($settingsKeepHtml !== ''): ?>
	<div class="gb-builder-block-settingskeep" style="display:none;"><?php echo $settingsKeepHtml; ?></div>
	<?php endif; ?>
	<?php else: ?>
	<div class="gb-builder-block-settingsform" style="display:none;"><?php echo $formHtml; ?></div>
	<?php if ($type === 'section'): ?>
	<div class="gb-builder-block-container-body">
		<?php echo $childrenHtml; ?>
	</div>
	<?php elseif ($type === 'columns'): ?>
	<?php
?>
	<div class="gb-builder-columns-slots" style="gap:<?php echo rex_escape($gapCssValue ?: '0px'); ?>;--gb-preview-display:flex;">
		<?php echo $childrenHtml; ?>
	</div>
	<?php endif; ?>
	<?php endif; ?>

	<span class="gb-builder-adder gb-builder-bottomadder">
		<span class="gb-builder-add-toggle" role="button" tabindex="0" title="<?php echo rex_i18n::msg('a2034_builder_add'); ?>"><i class="rex-icon fa-plus" aria-hidden="true"></i></span>
	</span>
</div>
