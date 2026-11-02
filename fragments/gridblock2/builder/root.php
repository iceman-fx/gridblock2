<?php
/*
	Redaxo-Addon Gridblock²
	Fragment: Wurzel-Wrapper des Builders - ersetzt den bisherigen Template-Picker+Tabs komplett
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)

	Anders als beim Builder-Vorbild wird hier NICHT rein clientseitig per AJAX aus einem leeren Grundgerüst
	aufgebaut (data-initial + buildFromTree()) - stattdessen rendert PHP die komplette Karten-Struktur
	serverseitig in einem Rutsch (wie die bisherige Gridblock-Oberfläche auch), performanter bei vielen
	Elementen. AJAX kommt nur beim tatsächlichen NEU-Einfügen eines Elements zum Einsatz.

	Erwartet im Scope: $nodes, $moduleValues, $rexVars, $depth(=0), $maxDepth, $instanceUid
*/

/** @var rex_fragment $this */

$nodes = (array) $this->nodes;
$moduleValues = (array) $this->moduleValues;
$rexVars = (array) $this->rexVars;
$maxDepth = (int) $this->maxDepth;
$instanceUid = (string) $this->instanceUid;

$missingSystemElements = Gridblock2Builder_Tree::checkSystemElementsAvailable();
if ($missingSystemElements !== []):
	?>
	<div class="alert alert-danger">
		<strong>Gridblock kann nicht gestartet werden.</strong>
		Folgende zwingend benötigte System-Elemente fehlen sowohl im Projekt-Datenordner als auch in der
		Addon-eigenen Installation:
		<ul>
			<?php foreach ($missingSystemElements as $missing): ?>
			<li><code><?php echo rex_escape($missing); ?></code></li>
			<?php endforeach; ?>
		</ul>
		Bitte die Gridblock-Installation prüfen (Addon ggf. neu installieren) oder die fehlenden Dateien
		wiederherstellen.
	</div>
	<?php
	return;
endif;

$draghandleEnabled = (rex_gridblock2::getConfig('gb_builder_draghandle') === 'checked');
$nestedAddButtonEnabled = (rex_gridblock2::getConfig('gb_builder_nested_addbutton') === 'checked');
$columnsQuickswitchEnabled = (rex_gridblock2::getConfig('gb_builder_columns_quickswitch') === 'checked');
$ratioPresets = Gridblock2Builder_Tree::getRatioPresets();
$templateOptions = Gridblock2Builder_Tree::getLegacyTemplateOptions();
$columnsUsable = ($ratioPresets !== []);
$firstRatioKey = '';
foreach ($ratioPresets as $presetKey => $preset) {
	if ((int) $preset['columns'] >= 2) {
		$firstRatioKey = $presetKey;
		break;
	}
}
if ($firstRatioKey === '' && $columnsUsable) {
	$firstRatioKey = (string) array_key_first($ratioPresets);
}
$addmenuMaxitems = 15;
$modalEditEnabled = (rex_gridblock2::getConfig('gb_builder_modaledit') === 'checked');
$modalEditMinwidth = rex_gridblock2::getConfig('gb_builder_modaledit_minwidth');
$modalEditMinwidth = ($modalEditMinwidth === '' || $modalEditMinwidth === null) ? 560 : (int) $modalEditMinwidth;
$modalStyle = (rex_gridblock2::getConfig('gb_builder_modalstyle') === 'sidebar') ? 'sidebar' : 'modal';
$livePreview = (rex_gridblock2::getConfig('gb_builder_live_preview') === 'checked');
$modalNarrow = (rex_gridblock2::getConfig('gb_builder_modal_narrow') === 'checked');
$fullscreenApplyConfig = rex_gridblock2::getConfig();
$viewportButtons = !(is_array($fullscreenApplyConfig) && array_key_exists('gb_builder_viewport_buttons', $fullscreenApplyConfig) && $fullscreenApplyConfig['gb_builder_viewport_buttons'] !== 'checked');
$fullscreenApply = !(is_array($fullscreenApplyConfig) && array_key_exists('gb_builder_fullscreen_apply', $fullscreenApplyConfig) && $fullscreenApplyConfig['gb_builder_fullscreen_apply'] !== 'checked');
$containerBackgroundCss = Gridblock2Builder_Preview::collectPreviewBodyCss();

//Modulrechte der Rolle (REDAXO-Rechteverwaltung) gelten auch fuer das Hinzufuegen-Menue - wie bei der Modulauswahl in Gridblock 1.x.
$modules = [];
$modulePerm = rex::getUser() ? rex::getUser()->getComplexPerm('modules') : null;
foreach (($_SESSION['gridAllowedModules'] ?? []) as $modId => $modInfo) {
	if ($modulePerm === null || !$modulePerm->hasPerm((int) $modId)) {
		continue;
	}
	if (!Gridblock2Builder_Tree::isModuleAllowedByTemplate((int) $modId, $rexVars)) {
		continue;
	}
	if (!Gridblock2Builder_Availability::isAllowed(Gridblock2Builder_Availability::moduleKey((int) $modId), $rexVars)) {
		continue;
	}
	$modules[] = ['id' => (int) $modId, 'name' => (string) ($modInfo['name'] ?? ('[ID: '.$modId.']'))];
}

//Custom-Elemente: projekteigene "Mini-Elemente" als 3.
$isDemoContext = Gridblock2Builder_Tree::isDemoVars($rexVars);

$customElementTypes = [];
foreach (Gridblock2Builder_Tree::discoverAddableCustomElementTypes($isDemoContext) as $key => $meta) {
	if (!Gridblock2Builder_Tree::canUseCustomElement($key, $isDemoContext)) {
		continue;
	}
	if (!Gridblock2Builder_Availability::isAllowed(Gridblock2Builder_Availability::customKey($key), $rexVars)) {
		continue;
	}
	$customElementTypes[] = ['key' => $key, 'label' => $meta['label'], 'icon' => $meta['icon'], 'group' => $meta['group'], 'description' => $meta['description']];
}

//Label/Icon der System-Elemente kommen aus deren element.yml statt REDAXO-i18n - Gruppe bleibt fest "Layout".
$sectionMeta = Gridblock2Builder_Tree::getSectionElementMeta();
$columnsMeta = Gridblock2Builder_Tree::getColumnsElementMeta();
?>
<div id="<?php echo $instanceUid; ?>" class="gb-builder<?php echo $nestedAddButtonEnabled ? ' gb-builder-show-nested-add' : ''; ?>" data-max-depth="<?php echo (int) $maxDepth; ?>" data-addmenu-maxitems="<?php echo (int) $addmenuMaxitems; ?>" data-draghandle-enabled="<?php echo $draghandleEnabled ? '1' : '0'; ?>" data-modal-edit-enabled="<?php echo $modalEditEnabled ? '1' : '0'; ?>" data-modal-edit-minwidth="<?php echo $modalEditMinwidth; ?>" data-modal-style="<?php echo $modalStyle; ?>" data-live-preview="<?php echo $livePreview ? '1' : '0'; ?>" data-modal-narrow="<?php echo $modalNarrow ? '1' : '0'; ?>" data-preview-block-links="<?php echo Gridblock2Builder_Preview::blockLinksEnabled() ? '1' : '0'; ?>" data-fullscreen-apply="<?php echo $fullscreenApply ? '1' : '0'; ?>" data-ratio-presets='<?php echo htmlspecialchars(json_encode($ratioPresets), ENT_QUOTES); ?>' data-template-options='<?php echo htmlspecialchars(json_encode($templateOptions), ENT_QUOTES); ?>' data-slice-id="<?php echo (int) ($rexVars['sliceID'] ?? 0); ?>" data-article-id="<?php echo (int) ($rexVars['artID'] ?? 0); ?>" data-clang-id="<?php echo (int) ($rexVars['clangID'] ?? 0); ?>" data-ctype-id="<?php echo (int) ($rexVars['ctypeID'] ?? 0); ?>" data-columns-enabled="<?php echo $columnsUsable ? '1' : '0'; ?>" data-columns-default-ratio="<?php echo rex_escape($firstRatioKey); ?>" data-section-enabled="<?php echo ($sectionMeta['enabled'] && Gridblock2Builder_Tree::canUseSection($isDemoContext) && Gridblock2Builder_Availability::isAllowed(Gridblock2Builder_Availability::sectionKey(), $rexVars)) ? '1' : '0'; ?>" data-modules='<?php echo htmlspecialchars(json_encode($modules), ENT_QUOTES); ?>' data-elements='<?php echo htmlspecialchars(json_encode($customElementTypes), ENT_QUOTES); ?>' data-label-section="<?php echo rex_escape($sectionMeta['label']); ?>" data-icon-section="<?php echo rex_escape($sectionMeta['icon']); ?>" data-desc-section="<?php echo rex_escape($sectionMeta['description']); ?>" data-label-columns="<?php echo rex_escape($columnsMeta['label']); ?>" data-icon-columns="<?php echo rex_escape($columnsMeta['icon']); ?>" data-desc-columns="<?php echo rex_escape($columnsMeta['description']); ?>" data-label-columnsdisabledhint="<?php echo rex_escape(rex_i18n::msg('a2034_builder_columns_disabled_hint')); ?>" data-label-searchplaceholder="<?php echo rex_i18n::msg('a2034_builder_search_placeholder'); ?>" data-label-groupstandard="<?php echo rex_i18n::msg('a2034_builder_group_standard'); ?>" data-label-groupspecial="<?php echo rex_i18n::msg('a2034_builder_group_special'); ?>" data-label-pasteelement="<?php echo rex_i18n::msg('a2034_builder_pasteelement'); ?>" data-label-iscopied="<?php echo rex_i18n::msg('a2034_builder_iscopied'); ?>" data-label-groupcore="<?php echo rex_i18n::msg('a2034_builder_group_core'); ?>" data-label-empty="<?php echo htmlspecialchars(rex_i18n::rawMsg('a2034_builder_empty'), ENT_QUOTES); ?>" data-label-settings="<?php echo rex_i18n::msg('a2034_builder_settings'); ?>" data-label-columnsettings="<?php echo rex_i18n::msg('a2034_builder_columnsettings'); ?>" data-label-apply="<?php echo rex_i18n::msg('a2034_apply'); ?>" data-label-cancel="<?php echo rex_i18n::msg('a2034_builder_cancel_element'); ?>" data-label-more="<?php echo rex_i18n::msg('a2034_builder_more'); ?>" data-label-setonline="<?php echo rex_i18n::msg('a2034_builder_setonline'); ?>" data-label-setoffline="<?php echo rex_i18n::msg('a2034_builder_setoffline'); ?>" data-label-fullscreen="<?php echo rex_i18n::msg('a2034_builder_fullscreen'); ?>" data-label-saveblock="<?php echo rex_i18n::msg('save_block'); ?>" data-label-fullscreenexit="<?php echo rex_i18n::msg('a2034_builder_fullscreen_exit'); ?>" data-label-fullscreenlocked="<?php echo rex_i18n::msg('a2034_builder_fullscreen_locked'); ?>" data-label-editlocked="<?php echo rex_i18n::msg('a2034_builder_edit_locked'); ?>" data-label-fullscreenapplylocked="<?php echo rex_i18n::msg('a2034_builder_fullscreen_apply_locked'); ?>" data-label-fullscreenapplytitle="<?php echo rex_i18n::msg('a2034_builder_fullscreen_apply_title'); ?>" data-label-addsearch="<?php echo rex_i18n::msg('a2034_builder_add_search'); ?>" data-label-addclose="<?php echo rex_i18n::msg('a2034_builder_add_close'); ?>" data-label-addnoresults="<?php echo rex_i18n::msg('a2034_builder_add_noresults'); ?>" data-label-addfailed="<?php echo rex_i18n::msg('a2034_builder_add_failed'); ?>" data-label-addtarget="<?php echo rex_i18n::msg('a2034_builder_add_target'); ?>" data-label-addtargetroot="<?php echo rex_i18n::msg('a2034_builder_add_target_root'); ?>" data-label-addtargetslot="<?php echo rex_i18n::msg('a2034_builder_add_target_slot'); ?>" data-label-addtargetafter="<?php echo rex_i18n::msg('a2034_builder_add_target_after'); ?>" data-label-addtargetend="<?php echo rex_i18n::msg('a2034_builder_add_target_end'); ?>" data-sortable-url="<?php echo rex_escape(rex_gridblock2_helper::getTimebuster(rex_addon::get('gridblock2')->getAssetsUrl('sortable.min.js'))); ?>" data-label-modalwidth="<?php echo rex_i18n::msg('a2034_builder_modal_width'); ?>">

	<div class="gb-builder-viewporttoolbar">
		<div class="gb-builder-viewporttoolbar-left">
			<div class="checkbox toggle gb-builder-compact-toggle">
				<label for="<?php echo $instanceUid; ?>_compact"><input type="checkbox" id="<?php echo $instanceUid; ?>_compact" class="gb-builder-compact-checkbox" checked="checked" /> Layout-Hilfen</label>
			</div>
		</div>

		<div class="gb-builder-viewporttoolbar-right">
			<?php if ($viewportButtons): ?>
			<div class="btn-group btn-group-xs gb-builder-viewport-group" role="group">
				<button type="button" class="btn btn-default gb-builder-viewport-btn active" data-viewport="desktop" title="Desktop"><i class="rex-icon fa-desktop" aria-hidden="true"></i></button>
				<button type="button" class="btn btn-default gb-builder-viewport-btn" data-viewport="tablet" title="Tablet"><i class="rex-icon fa-tablet-screen-button" aria-hidden="true"></i></button>
				<button type="button" class="btn btn-default gb-builder-viewport-btn" data-viewport="mobile" title="Smartphone"><i class="rex-icon fa-mobile-screen-button" aria-hidden="true"></i></button>
			</div>
			<?php endif; ?>
			<div class="btn-group btn-group-xs gb-builder-fullscreen-group" role="group">
				<button type="button" class="btn btn-default gb-builder-fullscreen-toggle" title="<?php echo rex_i18n::msg('a2034_builder_fullscreen'); ?>"><i class="rex-icon fa-maximize" aria-hidden="true"></i> <span class="gb-builder-fullscreen-label"><?php echo rex_i18n::msg('a2034_builder_fullscreen'); ?></span></button>
			</div>
		</div>
	</div>

	<?php if ($containerBackgroundCss !== ''): ?>
	<style>:where(#<?php echo rex_escape($instanceUid); ?>) > .gb-builder-body-wrapper > .gb-builder-body { <?php echo $containerBackgroundCss; ?> }</style>
	<?php endif; ?>

	<div class="gb-builder-body-wrapper">
	<div class="gb-builder-body">
		<?php
		$rootListFragment = new rex_fragment();
		$rootListFragment->setVar('nodes', $nodes, false);
		$rootListFragment->setVar('moduleValues', $moduleValues, false);
		$rootListFragment->setVar('rexVars', $rexVars, false);
		$rootListFragment->setVar('depth', 0, false);
		$rootListFragment->setVar('maxDepth', $maxDepth, false);
		echo $rootListFragment->parse('gridblock2/builder/list.php');
		?>
	</div>
	</div>

	<textarea name="REX_INPUT_VALUE[16]" data-builder-json style="display:none;"></textarea>
</div>
