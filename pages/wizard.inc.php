<?php
/*
	Redaxo-Addon Gridblock²
	Verwaltung: Einrichtungs-Assistent
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var rex_addon $this */
/** @var string $page */
/** @var string $gbCsrfUrl */
/** @var string $mypage */

$gbLegacyAddon = rex_addon::get('gridblock');
$hasGb1 = $gbLegacyAddon && $gbLegacyAddon->isInstalled();
$wizardConfig = $this->getConfig('config');
$gbModulesMode = (string) @$wizardConfig['modulesmode'];
$gbModulesSelected = (string) @$wizardConfig['modules'];

if ($hasGb1 && trim($gbModulesSelected, '#') === '') {
	$gb1Config = $gbLegacyAddon->getConfig('config');
	if (is_array($gb1Config) && trim((string) @$gb1Config['modules'], '#') !== '') {
		$gbModulesMode = (string) @$gb1Config['modulesmode'];
		$gbModulesSelected = (string) $gb1Config['modules'];
	}
}

$gbGapModeCurrent = Gridblock2Builder_Tree::getGapMode();
$gbGapCsFieldCurrent = (string) @$wizardConfig['gb_builder_gap_cs_field'];
$gbPreviewModeCurrent = (@$wizardConfig['gb_builder_preview_mode'] === 'shadowdom') ? 'shadowdom' : 'iframe';
$gbCsFieldChoices = Gridblock2Builder_Tree::getCsFieldChoices();
$gbCsFieldScopeLabels = ['template' => $this->i18n('a2034_config_gb_builder_gap_cs_field_scope_template'), 'columns' => $this->i18n('a2034_config_gb_builder_gap_cs_field_scope_columns'), 'section' => $this->i18n('a2034_config_gb_builder_gap_cs_field_scope_section')];

$gbAltStatus = rex_gridblock2_helper::getMediaAltFieldStatus();
$gbAltMissing = [];
if ($gbAltStatus['metainfo'] && !$gbAltStatus['alt']) {
	$gbAltMissing[] = rex_gridblock2_helper::MEDIA_ALT_FIELD;
}
if ($gbAltStatus['metainfo'] && !$gbAltStatus['decorative']) {
	$gbAltMissing[] = rex_gridblock2_helper::MEDIA_DECORATIVE_FIELD;
}

$gbSystemElementSection = Gridblock2Builder_Tree::getSystemElementSourceCode('system/section', 'output.php');
$gbSystemElementColumnsrow = Gridblock2Builder_Tree::getSystemElementSourceCode('system/column', 'columnsrow.php');
$gbSystemElementColumn = Gridblock2Builder_Tree::getSystemElementSourceCode('system/column', 'column.php');
?>

<div class="gb-wizard-banner gb-wizard-hero">
	<div class="gb-wizard-hero__bg" aria-hidden="true">
		<span class="gb-wizard-hero__block gb-wizard-hero__block--a"></span>
		<span class="gb-wizard-hero__block gb-wizard-hero__block--b"></span>
		<span class="gb-wizard-hero__block gb-wizard-hero__block--c"></span>
	</div>
	<div class="gb-wizard-hero__content">
		<div class="gb-wizard-hero__logo" aria-hidden="true"></div>
		<div>
			<p class="gb-wizard-hero__kicker"><?php echo $this->i18n($hasGb1 ? 'a2034_wizard_modal_title_gb1' : 'a2034_wizard_modal_title'); ?></p>
			<h3 class="gb-wizard-hero__title"><?php echo $this->i18n($hasGb1 ? 'a2034_wizard_banner_title_gb1' : 'a2034_wizard_banner_title_fresh'); ?></h3>
			<p class="gb-wizard-hero__lead"><?php echo $this->i18n($hasGb1 ? 'a2034_wizard_banner_lead_gb1' : 'a2034_wizard_banner_lead_fresh'); ?></p>
		</div>
		<div class="gb-wizard-hero__actions">
			<button type="button" class="btn btn-primary" id="gb-wizard-open"><?php echo $this->i18n('a2034_wizard_banner_start_btn'); ?></button>
			<button type="button" class="btn btn-link btn-xs" id="gb-wizard-banner-dismiss"><?php echo $this->i18n('a2034_wizard_banner_dismiss_btn'); ?></button>
		</div>
	</div>
</div>

<div class="modal fade" id="gb-wizard-modal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="<?php echo $this->i18n('a2034_close'); ?>"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo $this->i18n($hasGb1 ? 'a2034_wizard_modal_title_gb1' : 'a2034_wizard_modal_title'); ?></h4>
			</div>
			<div class="modal-body" id="gb-wizard-modal-body">
				<form id="gb-wizard-form">

					<?php if ($hasGb1): ?>
					<div class="gb-wizard-step" data-step="intro">
						<h4><?php echo $this->i18n('a2034_wizard_step_intro_head'); ?></h4>
						<p><?php echo $this->i18n('a2034_wizard_step_intro_lead'); ?></p>
						<p><br><strong><?php echo $this->i18n('a2034_wizard_step_intro_note'); ?></strong></p>
					</div>

					<div class="gb-wizard-step" data-step="gb1-actions">
						<h4><?php echo $this->i18n('a2034_wizard_step_gb1_actions_head'); ?></h4>
						<p><?php echo $this->i18n('a2034_wizard_step_gb1_actions_intro'); ?></p>
						<p class="infoblock"><i class="fas fa-circle-info"></i> <?php echo $this->i18n('a2034_wizard_step_gb1_actions_modulehint'); ?></p>
						
						<dl class="rex-form-group form-group">
							<dt>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb1_copy" value="1" checked="checked" /> <?php echo $this->i18n('a2034_wizard_gb1_action_copy'); ?></label>
								</div>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb1_migrate_module" value="1" checked="checked" /> <?php echo $this->i18n('a2034_wizard_gb1_action_migrate'); ?></label>
								</div>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb1_replace_module" value="1" /> <?php echo $this->i18n('a2034_wizard_gb1_action_replace_module'); ?></label>
									<p class="text-warning small"><i class="fas fa-triangle-exclamation"></i> <?php echo $this->i18n('a2034_wizard_gb1_action_replace_module_warning'); ?></p>
								</div>
								
								<br>
								
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb1_deactivate" value="1" checked="checked" /> <?php echo $this->i18n('a2034_wizard_gb1_action_deactivate'); ?></label>
									<p class="text-warning small"><i class="fas fa-triangle-exclamation"></i> <?php echo rex_i18n::rawMsg('a2034_wizard_gb1_action_deactivate_warning'); ?></p>
								</div>
							</dt>
						</dl>
					</div>

					<div class="gb-wizard-step" data-step="system-elements">
						<h4><?php echo $this->i18n('a2034_wizard_step_systemelements_head'); ?></h4>
						<p><?php echo $this->i18n('a2034_wizard_step_systemelements_intro'); ?></p>

						<dl class="rex-form-group form-group">
							<dt>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb1_edit_systemelements" id="gb-wizard-edit-systemelements" value="1" /> <?php echo $this->i18n('a2034_wizard_action_edit_systemelements'); ?></label>
								</div>
							</dt>
						</dl>

						<div class="gb-wizard-systemelements-fields" style="display:none;">
							<dl class="rex-form-group form-group rex-form-group-vertical">
								<dt><label for="gb-wizard-se-section"><?php echo $this->i18n('a2034_wizard_systemelements_section'); ?></label></dt>
								<dd><textarea name="gb1_systemelement_section" id="gb-wizard-se-section" rows="12" class="form-control rex-code"><?php echo rex_escape($gbSystemElementSection); ?></textarea></dd>
							</dl>
							<dl class="rex-form-group form-group rex-form-group-vertical">
								<dt><label for="gb-wizard-se-columnsrow"><?php echo $this->i18n('a2034_wizard_systemelements_columnsrow'); ?></label></dt>
								<dd><textarea name="gb1_systemelement_columnsrow" id="gb-wizard-se-columnsrow" rows="12" class="form-control rex-code"><?php echo rex_escape($gbSystemElementColumnsrow); ?></textarea></dd>
							</dl>
							<dl class="rex-form-group form-group rex-form-group-vertical">
								<dt><label for="gb-wizard-se-column"><?php echo $this->i18n('a2034_wizard_systemelements_column'); ?></label></dt>
								<dd><textarea name="gb1_systemelement_column" id="gb-wizard-se-column" rows="12" class="form-control rex-code"><?php echo rex_escape($gbSystemElementColumn); ?></textarea></dd>
							</dl>
							<span class="infoblock"><?php echo $this->i18n('a2034_wizard_systemelements_info'); ?></span>
						</div>
					</div>
					<?php endif; ?>

					<?php if (!$hasGb1): ?>
					<div class="gb-wizard-step" data-step="fresh-templates">
						<h4><?php echo $this->i18n('a2034_wizard_step_templates_head'); ?></h4>
						<p><?php echo $this->i18n('a2034_wizard_step_templates_intro'); ?></p>

						<dl class="rex-form-group form-group">
							<dt>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="install_default_templates" value="1" checked="checked" /> <?php echo $this->i18n('a2034_wizard_action_install_default_templates'); ?></label>
								</div>
							</dt>
						</dl>
					</div>
					<?php endif; ?>

					<div class="gb-wizard-step" data-step="modules">
						<h4><?php echo $this->i18n('a2034_wizard_step_modules_head'); ?></h4>
						<p><?php echo $this->i18n('a2034_wizard_step_modules_intro'); ?></p>
						
						<dl class="rex-form-group form-group">
							<dt><label for=""><?php echo $this->i18n('a2034_config_modulesmode'); ?></label></dt>
							<dd>
								<div class="radio toggle switch">
									<label><input name="modulesmode" type="radio" value="allow"<?php echo ($gbModulesMode != 'ignore') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_modulesmode_allow'); ?></label>
									
									<label><input name="modulesmode" type="radio" value="ignore"<?php echo ($gbModulesMode == 'ignore') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_modulesmode_deny'); ?></label>
								</div>
							</dd>
						</dl>
						
						<dl class="rex-form-group form-group">
							<dt><label for=""><?php echo $this->i18n('a2034_config_modules'); ?></label></dt>
							<dd>
								<select name="modules[]" size="10" multiple class="form-control">
									<?php
									$gbModDb = rex_sql::factory();
									$gbModDb->setQuery('SELECT id, name FROM '.rex::getTable('module').' WHERE input NOT LIKE "%/* GRID_MODULE_IDENTIFIER | DONT REMOVE */%" ORDER BY name, id');
									foreach ($gbModDb as $gbModRow):
										$gbModSel = (preg_match('/#'.$gbModRow->getValue('id').'#/i', $gbModulesSelected)) ? ' selected="selected"' : '';
										echo '<option value="'.$gbModRow->getValue('id').'"'.$gbModSel.'>'.rex_escape($gbModRow->getValue('name')).'</option>';
									endforeach;
									?>
								</select>
							</dd>
						</dl>
					</div>

					<div class="gb-wizard-step" data-step="gap">
						<h4><?php echo $this->i18n('a2034_wizard_step_gap_head'); ?></h4>
						<p><?php echo $this->i18n('a2034_wizard_step_gap_question'); ?></p>
						
						<dl class="rex-form-group form-group">
							<dt><label for=""><?php echo $this->i18n('a2034_wizard_step_gap_mode_label'); ?></label></dt>
							<dd>
								<div class="radio toggle switch">
									<label><input type="radio" name="gb_builder_gap_mode" value="native"<?php echo ($gbGapModeCurrent === 'native') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_wizard_step_gap_native'); ?></label>
									
									<label><input type="radio" name="gb_builder_gap_mode" value="cs"<?php echo ($gbGapModeCurrent === 'cs') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_wizard_step_gap_cs'); ?></label>
								</div>
							</dd>
						</dl>

						<?php
						$gbGapNativeFallback = @$wizardConfig['gb_builder_gap_native_fallback'];
						if (empty($gbGapNativeFallback)) {
							$gbGapNativeFallback = Gridblock2Builder_Tree::GAP_NATIVE_EXAMPLE;
						}
						?>
						<div class="gb-wizard-gap-option" data-gap-mode="native">
						<dl class="rex-form-group form-group">
							<dt><label for=""><?php echo $this->i18n('a2034_wizard_step_gap_native_info'); ?></label></dt>						
							<dd>
						
								<textarea name="gb_builder_gap_native_fallback" rows="7" class="form-control" spellcheck="false"><?php echo rex_escape($gbGapNativeFallback); ?></textarea>
								<span class="infoblock"><?php echo $this->i18n('a2034_wizard_step_gap_native_format_info'); ?></span>
							</dd>
						</dl>
						</div>

						<div class="gb-wizard-gap-option" data-gap-mode="cs">
						<dl class="rex-form-group form-group">
							<dt><label for=""><?php echo $this->i18n('a2034_wizard_step_gap_cs'); ?></label></dt>						
							<dd>							
							
							<select name="gb_builder_gap_cs_field" id="gb_wizard_gap_cs_field" class="selectpicker form-control" data-live-search="true" data-size="10">
								<option value=""<?php echo ($gbGapCsFieldCurrent === '') ? ' selected="selected"' : ''; ?>><?php echo $this->i18n('a2034_config_gb_builder_gap_cs_field_none'); ?></option>
								<?php foreach ($gbCsFieldChoices as $gbScope => $gbFields): ?>
									<?php if (!$gbFields): ?><?php continue; ?><?php endif; ?>
									<optgroup label="<?php echo rex_escape($gbCsFieldScopeLabels[$gbScope] ?? $gbScope); ?>">
										<?php foreach ($gbFields as $gbFieldKey => $gbFieldLabel): ?>
											<?php $gbOptionValue = $gbScope.'|'.$gbFieldKey; ?>
											<option value="<?php echo rex_escape($gbOptionValue); ?>"<?php echo ($gbGapCsFieldCurrent === $gbOptionValue) ? ' selected="selected"' : ''; ?>><?php echo rex_escape($gbFieldLabel); ?> (<?php echo rex_escape($gbFieldKey); ?>)</option>
										<?php endforeach; ?>
									</optgroup>
								<?php endforeach; ?>
							</select>
							<span class="infoblock"><?php echo $this->i18n('a2034_wizard_step_gap_cs_info'); ?></span>
							
							</dd>
						</dl>
							
						</div>

						<dl class="rex-form-group form-group">
							<dt></dt>
							<dd>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb_builder_native_grid_css_backend" value="checked"<?php echo (@$wizardConfig['gb_builder_native_grid_css_backend'] !== '') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_native_grid_css_backend_info'); ?></label>
								</div>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb_builder_native_grid_css_frontend" value="checked"<?php echo (@$wizardConfig['gb_builder_native_grid_css_frontend'] === 'checked') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_native_grid_css_frontend_info'); ?></label>
								</div>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb_elements_css_frontend" value="checked"<?php echo (@$wizardConfig['gb_elements_css_frontend'] === 'checked') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_gb_elements_css_frontend_info'); ?></label>
								</div>
							</dd>
						</dl>

					</div>
					<?php if ($gbAltStatus['metainfo'] && ($gbAltMissing !== [] || $gbAltStatus['alt'])): ?>
					<div class="gb-wizard-step" data-step="alt-fields">
						<h4><?php echo $this->i18n('a2034_wizard_step_alt_head'); ?></h4>
						<p><?php echo $this->i18n('a2034_wizard_step_alt_intro'); ?></p>

						<?php if ($gbAltMissing !== []): ?>
						<dl class="rex-form-group form-group">
							<dt>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb_create_alt_fields" value="1" checked="checked" /> <?php echo $this->i18n('a2034_wizard_action_create_alt_fields', rex_escape(implode(', ', $gbAltMissing))); ?></label>
								</div>
							</dt>
						</dl>
						<?php endif; ?>

						<dl class="rex-form-group form-group">
							<dt>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb_copy_titles_to_alt" value="1" /> <?php echo $this->i18n('a2034_wizard_action_copy_titles'); ?></label>
								</div>
							</dt>
						</dl>
						<span class="infoblock"><?php echo $this->i18n('a2034_wizard_step_alt_copyinfo'); ?></span>
					</div>
					<?php endif; ?>

					<div class="gb-wizard-step" data-step="preview">
						<h4><?php echo $this->i18n('a2034_wizard_step_preview_head'); ?></h4>
						<p><?php echo $this->i18n('a2034_wizard_step_preview_intro'); ?></p>
						<dl class="rex-form-group form-group">
							<dt><label for=""><?php echo $this->i18n('a2034_wizard_step_preview_question'); ?></label></dt>
							<dd>
								<select name="gb_builder_preview_mode" class="form-control">
									<option value="iframe"<?php echo ($gbPreviewModeCurrent === 'iframe') ? ' selected="selected"' : ''; ?>><?php echo $this->i18n('a2034_config_gb_builder_preview_mode_iframe'); ?></option>
									<option value="shadowdom"<?php echo ($gbPreviewModeCurrent === 'shadowdom') ? ' selected="selected"' : ''; ?>><?php echo $this->i18n('a2034_config_gb_builder_preview_mode_shadowdom'); ?></option>
								</select>
							</dd>
						</dl>

						<dl class="rex-form-group form-group gb-wizard-preview-option" data-preview-mode="iframe">
							<dt></dt>
							<dd>
								<div class="checkbox toggle">
									<label><input type="checkbox" name="gb_builder_element_preview_iframe" value="checked"<?php echo (@$wizardConfig['gb_builder_element_preview_iframe'] === 'checked') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_element_preview_iframe_info'); ?></label>
								</div>
							</dd>
						</dl>

						<br>

						<h5><?php echo $this->i18n('a2034_wizard_step_css_head'); ?></h5>
						<dl class="rex-form-group form-group">
							<dt><label for="gb-wizard-stylesheets"><?php echo $this->i18n('a2034_config_gb_builder_content_stylesheets'); ?></label></dt>
							<dd>
								<textarea id="gb-wizard-stylesheets" name="gb_builder_content_stylesheets" rows="3" class="form-control"><?php echo rex_escape(@$wizardConfig['gb_builder_content_stylesheets']); ?></textarea>
								<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_content_stylesheets_info'); ?></span>
							</dd>
						</dl>
						
						<dl class="rex-form-group form-group gb-wizard-preview-option" data-preview-mode="iframe">
							<dt><label for="gb-wizard-content-scripts"><?php echo $this->i18n('a2034_config_gb_builder_content_scripts'); ?></label></dt>
							<dd>
								<textarea id="gb-wizard-content-scripts" name="gb_builder_content_scripts" rows="3" class="form-control"><?php echo rex_escape(@$wizardConfig['gb_builder_content_scripts']); ?></textarea>
								<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_content_scripts_info'); ?></span>
							</dd>
						</dl>

						<br>
						<h5><?php echo $this->i18n('a2034_wizard_step_palette_head'); ?></h5>
						<p><?php echo $this->i18n('a2034_wizard_step_palette_intro'); ?></p>
						<dl class="rex-form-group form-group">
							<dt><label for="gb-wizard-palette"><?php echo $this->i18n('a2034_config_gb_builder_palette'); ?></label></dt>
							<dd>
								<textarea id="gb-wizard-palette" name="gb_builder_palette" rows="7" class="form-control" spellcheck="false" placeholder="<?php echo rex_escape(Gridblock2Palette::EXAMPLE); ?>"><?php echo rex_escape(@$wizardConfig['gb_builder_palette']); ?></textarea>
								<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_palette_info'); ?></span>
							</dd>
						</dl>

						<p class="infoblock"><i class="fas fa-circle-info"></i> <?php echo $this->i18n('a2034_wizard_step_preview_hint'); ?></p>

						<div id="gb-wizard-settings-result"></div>
					</div>


					<!-- Success-Seite -->
					<div class="gb-wizard-step" data-step="thanks">
						<h4><?php echo $this->i18n('a2034_wizard_thanks_title'); ?></h4>
						
						<div class="row">
							<div class="col-md-6">
								<div id="gb-wizard-thanks-summary"></div>
								
								<?php if ($hasGb1): ?>
								<!--
								<br>
								<p class="infoblock"><i class="fas fa-triangle-exclamation"></i> <?php echo rex_i18n::rawMsg('a2034_wizard_thanks_systemelements'); ?></p>
								-->
								<?php else: ?>
								<br>
								<p class="infoblock"><i class="fas fa-triangle-exclamation"></i> <?php echo rex_i18n::rawMsg('a2034_wizard_thanks_frontend_css_hint'); ?></p>
								<?php endif; ?>
							</div>
							
							<div class="col-md-6">
								<p><?php echo $this->i18n('a2034_wizard_thanks_text'); ?></p>
								
								<?php if ($hasGb1): ?>
								<p><?php echo $this->i18n('a2034_wizard_thanks_text_newinstall'); ?></p>
								<?php endif; ?>
								
								<br>
								<p class="btn-toolbar">
									<a class="btn btn-default" href="index.php?page=<?php echo rex_escape($mypage); ?>/help" target="_blank"><?php echo $this->i18n('a2034_wizard_thanks_help_link'); ?></a>
									<a class="btn btn-default" href="index.php?page=<?php echo rex_escape($mypage); ?>/config"><?php echo $this->i18n('a2034_wizard_thanks_config_link'); ?></a>
								</p>
							</div>
						</div>
					</div>

				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default pull-left" id="gb-wizard-back"><?php echo $this->i18n('a2034_wizard_back_btn'); ?></button>
				<button type="button" class="btn btn-save" id="gb-wizard-next"><?php echo $this->i18n('a2034_wizard_next_btn'); ?></button>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	var wizardUrl = 'index.php?page=<?php echo rex_escape($page); ?>';
	var currentStepIndex = 0;
	var finished = false;

	function extractWizardFragment(html) {
		return jQuery('<div>').html(html).find('#gb-wizard-ajax-result').html();
	}

	function wizardSteps() { return jQuery('#gb-wizard-form > .gb-wizard-step'); }

	function showStep(index) {
		var steps = wizardSteps();
		currentStepIndex = Math.max(0, Math.min(index, steps.length - 1));
		steps.hide().eq(currentStepIndex).show();

		var isFirst = currentStepIndex === 0;
		var isThanks = steps.eq(currentStepIndex).data('step') === 'thanks';
		jQuery('#gb-wizard-back').toggle(!isFirst && !isThanks);
		jQuery('#gb-wizard-next').toggle(!isThanks);
		jQuery('#gb-wizard-next').text(isLastQuestionStep() ? <?php echo json_encode(rex_i18n::rawMsg('a2034_wizard_finish_btn')); ?> : <?php echo json_encode(rex_i18n::rawMsg('a2034_wizard_next_btn')); ?>);
	}

	function isLastQuestionStep() {
		var steps = wizardSteps();
		return currentStepIndex === steps.length - 2;
	}

	function syncGapOptionVisibility() {
		var mode = jQuery('input[name="gb_builder_gap_mode"]:checked').val();
		jQuery('.gb-wizard-gap-option').hide().filter('[data-gap-mode="' + mode + '"]').show();
	}
	function syncPreviewOptionVisibility() {
		var mode = jQuery('select[name="gb_builder_preview_mode"]').val();
		jQuery('.gb-wizard-preview-option').toggle(mode === 'iframe');
	}
	function syncSystemElementsVisibility() {
		jQuery('.gb-wizard-systemelements-fields').toggle(jQuery('#gb-wizard-edit-systemelements').is(':checked'));
	}

	jQuery('#gb-wizard-banner-dismiss').on('click', function () {
		jQuery.post(wizardUrl + '&func=wizard_dismiss&<?php echo $gbCsrfUrl; ?>');
		jQuery('.gb-wizard-banner').fadeOut();
		jQuery('#gb-wizard-modal').modal('hide');
	});

	jQuery('#gb-wizard-open').on('click', function () {
		finished = false;
		showStep(0);
		syncGapOptionVisibility();
		syncPreviewOptionVisibility();
		syncSystemElementsVisibility();
		jQuery('#gb-wizard-modal').modal('show');
	});

	jQuery('#gb-wizard-modal').on('shown.bs.modal', function () {
		jQuery('#gb_wizard_gap_cs_field').selectpicker('refresh');
	});

	jQuery('#gb-wizard-form').on('change', 'input[name="gb_builder_gap_mode"]', syncGapOptionVisibility);
	jQuery('#gb-wizard-form').on('change', 'select[name="gb_builder_preview_mode"]', syncPreviewOptionVisibility);
	jQuery('#gb-wizard-form').on('change', '#gb-wizard-edit-systemelements', syncSystemElementsVisibility);

	jQuery('#gb-wizard-back').on('click', function () { showStep(currentStepIndex - 1); });

	jQuery('#gb-wizard-form').on('submit', function (e) { e.preventDefault(); });

	jQuery('#gb-wizard-next').on('click', function () {
		if (!isLastQuestionStep()) { showStep(currentStepIndex + 1); return; }

		jQuery.post(wizardUrl + '&func=wizard_save_settings&<?php echo $gbCsrfUrl; ?>', jQuery('#gb-wizard-form').serialize(), function (html) {
			var $result = jQuery('<div>').html(extractWizardFragment(html));
			if ($result.find('#gb-wizard-save-status').data('ok') == 1) {
				jQuery('#gb-wizard-thanks-summary').html($result.html());
				finished = true;
				showStep(currentStepIndex + 1);
			} else {
				jQuery('#gb-wizard-settings-result').html($result.html());
			}
		}).fail(function () {
			jQuery('#gb-wizard-settings-result').html(<?php echo json_encode('<div class="alert alert-danger">'.$this->i18n('a2034_error').'</div>'); ?>);
		});
	});

	jQuery('#gb-wizard-modal').on('hidden.bs.modal', function () {
		if (finished) { window.location.reload(); }
	});
})();
</script>
