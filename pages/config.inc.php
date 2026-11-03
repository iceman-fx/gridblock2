<?php
/*
	Redaxo-Addon Gridblock²
	Verwaltung: Einstellungen (config)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** RexStan: Vars vom Check ausschließen */
/** @var rex_addon $this */
/** @var array $config */
/** @var string $func */
/** @var string $page */
/** @var rex_csrf_token $gbCsrf */
/** @var string $subpage */


if (!rex::getUser()->hasPerm($this->getProperty('package').'[config]')):
	echo rex_view::error($this->i18n('a2034_config_no_permission'));
	return;
endif;


//Variablen deklarieren
$form_error = 0;
$gbPaletteErrors = [];
$gbPaletteDraft = null;
$gbGapErrors = [];
$gbGapDraft = null;


//Projektfarben vorab pruefen: bei Fehlern wird nichts gespeichert, die Eingaben bleiben im Formular
if ($func == "save" && isset($_POST['submit'])):
	$gbPaletteDraft = ['base' => (string) rex_post('gb_builder_palette', 'string', ''), 'domains' => (array) rex_post('gb_domain', 'array', [])];
	$gbPaletteErrors = Gridblock2Palette::parse($gbPaletteDraft['base'])['errors'];
	foreach ($gbPaletteDraft['domains'] as $gbPalDomainId => $gbPalDomain):
		if (!is_array($gbPalDomain) || !empty($gbPalDomain['remove'])):
			continue;
		endif;
		foreach (Gridblock2Palette::parse((string) ($gbPalDomain['gb_builder_palette'] ?? ''))['errors'] as $gbPalError):
			$gbPaletteErrors[] = $this->i18n('a2034_palette_err_domain', (string) (int) $gbPalDomainId).' '.$gbPalError;
		endforeach;
	endforeach;
	if ($gbPaletteErrors !== []):
		echo rex_view::warning($this->i18n('a2034_palette_err_head').'<br>'.implode('<br>', $gbPaletteErrors));
	else:
		$gbPaletteDraft = null;
	endif;

	//Werte fuer den nativen Spalten-Abstand pruefen (JSON-Format): bei Fehlern wird ebenfalls nichts gespeichert
	$gbGapDraft = (string) rex_post('gb_builder_gap_native_fallback', 'string', '');
	$gbGapErrors = Gridblock2Builder_Tree::gapNativeErrors($gbGapDraft);
	if ($gbGapErrors !== []):
		echo rex_view::warning($this->i18n('a2034_gap_err_head').'<br>'.implode('<br>', $gbGapErrors));
	else:
		$gbGapDraft = null;
	endif;
endif;


//Formular dieser Seite verarbeiten
if ($func == "save" && isset($_POST['submit']) && $gbPaletteErrors === [] && $gbGapErrors === []):

	//Modulauswahl aufbereiten
	$mods = rex_post('modules');
	$mods = (is_array($mods)) ? implode("#", rex_post('modules')) : '';

	//Konfig speichern
	$res = $this->setConfig('config', [
		'modulesmode'				=> rex_post('modulesmode'),
		'modules'					=> '#'.$mods.'#',
		'hidepreviewcoltitles'		=> rex_post('hidepreviewcoltitles'),
		'hideinfotexts'				=> rex_post('hideinfotexts'),
		'showcontentsettingsbe'		=> rex_post('showcontentsettingsbe'),
		
		//Gridblock 2.0		
		'gb_builder_slicelist_preview'			=> rex_post('gb_builder_slicelist_preview'),
		'gb_builder_slicelist_fullscreen'		=> rex_post('gb_builder_slicelist_fullscreen'),
		'gb_builder_fullscreen_apply'			=> rex_post('gb_builder_fullscreen_apply'),
		'gb_builder_viewport_buttons'			=> rex_post('gb_builder_viewport_buttons'),
		'gb_builder_preview_block_links'		=> rex_post('gb_builder_preview_block_links'),
		'gb_builder_live_preview'				=> rex_post('gb_builder_live_preview'),
		'gb_builder_modalstyle'					=> (rex_post('gb_builder_modalstyle', 'string', 'modal') === 'sidebar') ? 'sidebar' : 'modal',
		'gb_builder_modal_narrow'				=> rex_post('gb_builder_modal_narrow'),
		'gb_builder_slicelist_maxheight'		=> in_array(rex_post('gb_builder_slicelist_maxheight', 'string', ''), ['small', 'medium', 'large'], true) ? rex_post('gb_builder_slicelist_maxheight', 'string', '') : '',
		'gb_builder_preview_mode'				=> rex_post('gb_builder_preview_mode', 'string', 'iframe'),
		'gb_builder_element_preview_iframe'		=> rex_post('gb_builder_element_preview_iframe'),
		'gb_builder_preview_visibility_fixes'	=> rex_post('gb_builder_preview_visibility_fixes'),
		'gb_builder_palette'					=> (string) rex_post('gb_builder_palette', 'string', ''),
		'gb_builder_preview_media_to_container'	=> rex_post('gb_builder_preview_media_to_container'),
		'gb_builder_compacttoolbox'				=> rex_post('gb_builder_compacttoolbox'),
		'gb_builder_draghandle'					=> rex_post('gb_builder_draghandle'),
		'gb_builder_columns_quickswitch'		=> rex_post('gb_builder_columns_quickswitch'),
		'gb_builder_nested_addbutton'			=> rex_post('gb_builder_nested_addbutton'),
		'gb_builder_between_addbutton'			=> rex_post('gb_builder_between_addbutton'),
		'gb_builder_offlinetoggle'				=> rex_post('gb_builder_offlinetoggle'),
		'gb_builder_copypaste'					=> rex_post('gb_builder_copypaste'),
		'gb_builder_hidelabel'					=> rex_post('gb_builder_hidelabel'),
		'gb_builder_maxdepth'					=> rex_post('gb_builder_maxdepth', 'int', 3),
		'gb_builder_modaledit'					=> rex_post('gb_builder_modaledit'),
		'gb_builder_modaledit_minwidth'			=> rex_post('gb_builder_modaledit_minwidth', 'int', 560),
		'gb_builder_content_stylesheets'		=> rex_post('gb_builder_content_stylesheets'),
		'gb_builder_content_scripts'			=> rex_post('gb_builder_content_scripts'),
		'gb_builder_preview_wrapper_class'	=> trim((string) rex_post('gb_builder_preview_wrapper_class')),
		'gb_builder_module_css_overrides'		=> rex_post('gb_builder_module_css_overrides'),
		'gb_builder_preview_mode_modules'		=> '#'.(is_array(rex_post('gb_builder_preview_mode_modules')) ? implode('#', array_map('intval', rex_post('gb_builder_preview_mode_modules'))) : '').'#',
		'gb_builder_gap_mode'					=> (rex_post('gb_builder_gap_mode', 'string', 'native') === 'cs') ? 'cs' : 'native',
		'gb_builder_gap_cs_field'				=> rex_post('gb_builder_gap_cs_field'),
		'gb_builder_gap_native_fallback'		=> rex_post('gb_builder_gap_native_fallback'),
		'gb_builder_native_grid_css_frontend'	=> rex_post('gb_builder_native_grid_css_frontend'),
		'gb_builder_native_grid_css_backend'	=> rex_post('gb_builder_native_grid_css_backend'),
		'gb_elements_css_frontend'				=> rex_post('gb_elements_css_frontend'),
		'gb_builder_templates_import_export'	=> rex_post('gb_builder_templates_import_export'),
		'gb_builder_synchronizer_enabled'		=> rex_post('gb_builder_synchronizer_enabled'),
		'gb_builder_multisite'					=> rex_post('gb_builder_multisite'),
		'gb_builder_multisite_elements'			=> rex_post('gb_builder_multisite_elements'),
		'gb_builder_domains'					=> (rex_post('gb_domain_present', 'int', 0) === 1) ? Gridblock2Builder_MultiSite::sanitizePost((array) rex_post('gb_domain', 'array', [])) : (string) (@$config['gb_builder_domains'] ?? ''),
	]);

	Gridblock2Builder_Tree::writeNativeGridCssFile();
	Gridblock2Builder_Tree::writeElementsCssFile();

	//Rückmeldung
	echo ($res) ? rex_view::info($this->i18n('a2034_settings_saved')) : rex_view::warning($this->i18n('a2034_error'));

	//reload Konfig
	$config = $this->getConfig('config');
	$config = (is_array($config)) ? $config : [];
	if (!array_key_exists('gb_builder_slicelist_fullscreen', $config)) { $config['gb_builder_slicelist_fullscreen'] = 'checked'; }
	if (!array_key_exists('gb_builder_fullscreen_apply', $config)) { $config['gb_builder_fullscreen_apply'] = 'checked'; }
endif;

if (isset($config) && is_array($config) && !array_key_exists('gb_builder_viewport_buttons', $config)) { $config['gb_builder_viewport_buttons'] = 'checked'; }
if (isset($config) && is_array($config) && !array_key_exists('gb_builder_preview_block_links', $config)) { $config['gb_builder_preview_block_links'] = 'checked'; }


//Formular ausgeben
?>


<form action="index.php?page=<?php echo $page; ?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="subpage" value="<?php echo $subpage; ?>" />
<input type="hidden" name="func" value="save" />
<?php echo $gbCsrf->getHiddenField(); ?>

<section class="rex-page-section">

	<div class="row">
		<div class="col-lg-7">
		
			<div class="panel panel-default">
				<header class="panel-heading"><div class="panel-title"><?php echo $this->i18n('a2034_config_modules_head'); ?></div></header>
				
				<div class="panel-body">

					<dl class="rex-form-group form-group">
						<dt><label for=""><?php echo $this->i18n('a2034_config_modulesmode'); ?></label></dt>
						<dd>
							<div class="radio toggle switch">
								<label for="mode1">
									<input name="modulesmode" type="radio" id="mode1" value="allow" <?php echo (@$config['modulesmode'] != 'ignore') ? 'checked' : ''; ?> /> <?php echo $this->i18n('a2034_config_modulesmode_allow'); ?>
								</label>
								
								<label for="mode2">
									<input name="modulesmode" type="radio" id="mode2" value="ignore" <?php echo (@$config['modulesmode'] == 'ignore') ? 'checked' : ''; ?> /> <?php echo $this->i18n('a2034_config_modulesmode_deny'); ?>
								</label>
							</div>
						</dd>
					</dl>        
				
					<dl class="rex-form-group form-group">
						<dt><label for=""><?php echo $this->i18n('a2034_config_modules'); ?></label></dt>
						<dd>
							<select name="modules[]" id="modules" size="10" multiple class="form-control">
							<?php
							$db = rex_sql::factory();
							$db->setQuery('SELECT id, name FROM '.rex::getTable('module').' WHERE input NOT LIKE "%/* GRID_MODULE_IDENTIFIER | DONT REMOVE */%" ORDER BY name, id');
							
							foreach ($db as $dbi):
								$sel = (preg_match("/#".$dbi->getValue('id')."#/i", @$config['modules'])) ? 'selected="selected"' : '';
								echo '<option value="'.$dbi->getValue('id').'" '.$sel.'>'.rex_escape($dbi->getValue('name')).'</option>';
							endforeach;
							?>
							</select>
							<span class="infoblock"><?php echo rex_i18n::rawmsg('a2034_text1'); ?></span>
						</dd>
					</dl>
			
				</div>						

				<footer class="panel-footer">
					<div class="btn-toolbar">
						<input class="btn btn-save" type="submit" name="submit" title="<?php echo $this->i18n('a2034_save'); ?>" value="<?php echo $this->i18n('a2034_save'); ?>" />
					</div>
				</footer>
				
			</div>
			
	
		</div>
		<div class="col-lg-5">
		
			<div class="panel panel-default">
				<header class="panel-heading"><div class="panel-title"><?php echo $this->i18n('a2034_config_functions_head'); ?></div></header>
				
				<div class="panel-body">
		
					<div class="checkbox toggle">
						<label for="hideinfotexts">
							<input type="checkbox" name="hideinfotexts" id="hideinfotexts" value="checked" <?php echo @$config['hideinfotexts']; ?> /> <?php echo $this->i18n('a2034_config_hideinfotexts_info'); ?>
						</label>
					</div>
					
					<div class="checkbox toggle">
						<label for="gb_builder_templates_import_export">
							<input type="checkbox" name="gb_builder_templates_import_export" id="gb_builder_templates_import_export" value="checked" <?php echo @$config['gb_builder_templates_import_export']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_templates_import_export_info'); ?>
						</label>
					</div>

					<div class="checkbox toggle">
						<label for="gb_builder_synchronizer_enabled">
							<input type="checkbox" name="gb_builder_synchronizer_enabled" id="gb_builder_synchronizer_enabled" value="checked" <?php echo @$config['gb_builder_synchronizer_enabled']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_synchronizer_enabled_info'); ?>
						</label>
						<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_synchronizer_enabled_hint'); ?></span>
					</div>
					
					<?php if (Gridblock2Builder_MultiSite::isYrewriteAvailable()): ?>
					<br>
					
					<h4 class="gb-config-subhead"><?php echo $this->i18n('a2034_config_ms_head'); ?></h4>
					<span class="infoblock"><?php echo $this->i18n('a2034_config_ms_info'); ?></span>
					
					<div class="checkbox toggle">
						<label for="gb_builder_multisite">
							<input type="checkbox" name="gb_builder_multisite" id="gb_builder_multisite" value="checked" <?php echo @$config['gb_builder_multisite']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_multisite_info'); ?>
						</label>
					</div>

					<div class="checkbox toggle">
						<label for="gb_builder_multisite_elements">
							<input type="checkbox" name="gb_builder_multisite_elements" id="gb_builder_multisite_elements" value="checked" <?php echo @$config['gb_builder_multisite_elements']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_multisite_elements_info'); ?>
						</label>
					</div>
					<?php endif; ?>
					
				</div>

				<footer class="panel-footer">
					<div class="btn-toolbar">
						<input class="btn btn-save" type="submit" name="submit" title="<?php echo $this->i18n('a2034_save'); ?>" value="<?php echo $this->i18n('a2034_save'); ?>" />
					</div>
				</footer>

			</div>
	
		</div>
	</div>
			
            
	<dl class="rex-form-group form-group"><dt></dt></dl>


	<div class="panel panel-success">	
		<header class="panel-heading"><div class="panel-title"><?php echo $this->i18n('a2034_config_gb_builder_head'); ?></div></header>
		
		<div class="panel-body">

			<div class="row">
				<div class="col-lg-7">
				
					<div class="panel panel-default">
						<header class="panel-heading"><div class="panel-title"><?php echo $this->i18n('a2034_config_gb_builder_basic_head'); ?></div></header>
						
						<div class="panel-body">

							<dl class="rex-form-group form-group">
								<dt><label for="gb_builder_maxdepth"><?php echo $this->i18n('a2034_config_gb_builder_maxdepth'); ?></label></dt>
								<dd>
									<input type="number" size="4" min="1" max="10" name="gb_builder_maxdepth" id="gb_builder_maxdepth" value="<?php echo intval(@$config['gb_builder_maxdepth'] ?: 3); ?>" class="form-control" />
									<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_maxdepth_info'); ?></span>
								</dd>
							</dl>
							

							<dl class="rex-form-group form-group">
								<dt><label for="gb_builder_preview_mode"><?php echo $this->i18n('a2034_config_gb_builder_preview_mode'); ?></label></dt>
								<dd>
									<?php $previewMode = (@$config['gb_builder_preview_mode'] === 'shadowdom') ? 'shadowdom' : 'iframe'; ?>
									<select name="gb_builder_preview_mode" id="gb_builder_preview_mode" class="form-control">
										<option value="iframe" <?php echo ($previewMode === 'iframe') ? 'selected="selected"' : ''; ?>><?php echo $this->i18n('a2034_config_gb_builder_preview_mode_iframe'); ?></option>
										<option value="shadowdom" <?php echo ($previewMode === 'shadowdom') ? 'selected="selected"' : ''; ?>><?php echo $this->i18n('a2034_config_gb_builder_preview_mode_shadowdom'); ?></option>
									</select>
								</dd>
							</dl>


							<dl class="rex-form-group form-group" id="gb_builder_element_preview_iframe_group">
								<dt></dt>
								<dd>
									<div class="checkbox toggle">
										<label for="gb_builder_element_preview_iframe">
											<input type="checkbox" name="gb_builder_element_preview_iframe" id="gb_builder_element_preview_iframe" value="checked" <?php echo @$config['gb_builder_element_preview_iframe']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_element_preview_iframe_info'); ?>
										</label>
									</div>
								</dd>
							</dl>
							<script>
							(function () {
								var select = document.getElementById('gb_builder_preview_mode');
								var group = document.getElementById('gb_builder_element_preview_iframe_group');
								if (!select || !group) { return; }
								var toggle = function () { group.style.display = (select.value === 'iframe') ? '' : 'none'; };
								select.addEventListener('change', toggle);
								toggle();
							})();
							</script>


							<dl class="rex-form-group form-group" id="gb_builder_preview_mode_modules_group">
								<dt><label for="gb_builder_preview_mode_modules"><?php echo $this->i18n('a2034_config_gb_builder_preview_mode_modules'); ?></label></dt>
								<dd>
									<select name="gb_builder_preview_mode_modules[]" id="gb_builder_preview_mode_modules" size="8" multiple class="form-control">
									<?php
									foreach ($db as $dbi):
										$sel = (preg_match("/#".$dbi->getValue('id')."#/i", (string) @$config['gb_builder_preview_mode_modules'])) ? 'selected="selected"' : '';
										echo '<option value="'.$dbi->getValue('id').'" '.$sel.'>'.rex_escape($dbi->getValue('name')).'</option>';
									endforeach;
									?>
									</select>
									<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_preview_mode_modules_info'); ?></span>
								</dd>
							</dl>
							<script>
							(function () {
								var select = document.getElementById('gb_builder_preview_mode');
								var group = document.getElementById('gb_builder_preview_mode_modules_group');
								if (!select || !group) { return; }
								var toggle = function () { group.style.display = (select.value === 'iframe') ? '' : 'none'; };
								select.addEventListener('change', toggle);
								toggle();
							})();
							</script>


							<dl class="rex-form-group form-group"><dt></dt></dl>
							

							<dl class="rex-form-group form-group">
								<dt><label for="gb_builder_preview_media_to_container"><?php echo $this->i18n('a2034_config_gb_builder_preview_media_to_container'); ?></label></dt>
								<dd>
									<div class="checkbox toggle">
										<label for="gb_builder_preview_media_to_container">
											<input type="checkbox" name="gb_builder_preview_media_to_container" id="gb_builder_preview_media_to_container" value="checked" <?php echo @$config['gb_builder_preview_media_to_container']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_preview_media_to_container_info'); ?>
										</label>
									</div>
								</dd>
							</dl>


							<dl class="rex-form-group form-group">
								<dt><label for="gb_builder_modaledit"><?php echo $this->i18n('a2034_config_gb_builder_modaledit'); ?></label></dt>
								<dd>
									<div class="checkbox toggle">
										<label for="gb_builder_modaledit">
											<input type="checkbox" name="gb_builder_modaledit" id="gb_builder_modaledit" value="checked" <?php echo @$config['gb_builder_modaledit']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_modaledit_info'); ?>
										</label>
									</div>
								</dd>
							</dl>
							

							<dl class="rex-form-group form-group" id="gb_builder_modaledit_minwidth_group">
								<dt><label for="gb_builder_modaledit_minwidth"><?php echo $this->i18n('a2034_config_gb_builder_modaledit_minwidth'); ?></label></dt>
								<dd>
									<input type="number" size="4" min="200" max="2000" step="10" name="gb_builder_modaledit_minwidth" id="gb_builder_modaledit_minwidth" value="<?php echo intval(@$config['gb_builder_modaledit_minwidth'] ?: 560); ?>" class="form-control" />
									<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_modaledit_minwidth_info'); ?></span>
								</dd>
							</dl>
							<script>
							(function () {
								var checkbox = document.getElementById('gb_builder_modaledit');
								var group = document.getElementById('gb_builder_modaledit_minwidth_group');
								if (!checkbox || !group) { return; }
								var toggle = function () { group.style.display = checkbox.checked ? '' : 'none'; };
								checkbox.addEventListener('change', toggle);
								toggle();
							})();
							</script>

							<dl class="rex-form-group form-group">
								<dt><label for="gb_builder_modalstyle_modal"><?php echo $this->i18n('a2034_config_gb_builder_modalstyle'); ?></label></dt>
								<dd>
									<div class="radio toggle switch">
										<label for="gb_builder_modalstyle_modal">
											<input name="gb_builder_modalstyle" type="radio" id="gb_builder_modalstyle_modal" value="modal" <?php echo (@$config['gb_builder_modalstyle'] !== 'sidebar') ? 'checked' : ''; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_modalstyle_modal'); ?>
										</label>
										<label for="gb_builder_modalstyle_sidebar">
											<input name="gb_builder_modalstyle" type="radio" id="gb_builder_modalstyle_sidebar" value="sidebar" <?php echo (@$config['gb_builder_modalstyle'] === 'sidebar') ? 'checked' : ''; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_modalstyle_sidebar'); ?>
										</label>
									</div>
								</dd>
							</dl>

							<dl class="rex-form-group form-group">
								<dt><label for="gb_builder_modal_narrow"><?php echo $this->i18n('a2034_config_gb_builder_modal_narrow'); ?></label></dt>
								<dd>
									<div class="checkbox toggle">
										<label for="gb_builder_modal_narrow">
											<input type="checkbox" name="gb_builder_modal_narrow" id="gb_builder_modal_narrow" value="checked" <?php echo @$config['gb_builder_modal_narrow']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_modal_narrow_info'); ?>
										</label>
									</div>
								</dd>
							</dl>

							<dl class="rex-form-group form-group">
								<dt><label for="gb_builder_viewport_buttons"><?php echo $this->i18n('a2034_config_gb_builder_viewport_buttons'); ?></label></dt>
								<dd>
									<div class="checkbox toggle">
										<label for="gb_builder_viewport_buttons">
											<input type="checkbox" name="gb_builder_viewport_buttons" id="gb_builder_viewport_buttons" value="checked" <?php echo @$config['gb_builder_viewport_buttons']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_viewport_buttons_info'); ?>
										</label>
									</div>
								</dd>
							</dl>

							<dl class="rex-form-group form-group">
								<dt><label for="gb_builder_preview_block_links"><?php echo $this->i18n('a2034_config_gb_builder_preview_block_links'); ?></label></dt>
								<dd>
									<div class="checkbox toggle">
										<label for="gb_builder_preview_block_links">
											<input type="checkbox" name="gb_builder_preview_block_links" id="gb_builder_preview_block_links" value="checked" <?php echo @$config['gb_builder_preview_block_links']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_preview_block_links_info'); ?>
										</label>
									</div>
								</dd>
							</dl>

							<dl class="rex-form-group form-group">
								<dt></dt>
								<dd>
									<div class="checkbox toggle">
										<label for="gb_builder_nested_addbutton">
											<input type="checkbox" name="gb_builder_nested_addbutton" id="gb_builder_nested_addbutton" value="checked" <?php echo @$config['gb_builder_nested_addbutton']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_nested_addbutton_info'); ?>
										</label>
									</div>
								</dd>
							</dl>

							<dl class="rex-form-group form-group">
								<dt></dt>
								<dd>
									<div class="checkbox toggle">
										<label for="gb_builder_between_addbutton">
											<input type="checkbox" name="gb_builder_between_addbutton" id="gb_builder_between_addbutton" value="checked" <?php echo @$config['gb_builder_between_addbutton']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_between_addbutton_info'); ?>
										</label>
									</div>
								</dd>
							</dl>
							
						</div>
					</div>
			
				</div>
				
				<div class="col-lg-5">
				
					<div class="panel panel-default">
						<header class="panel-heading"><div class="panel-title"><?php echo $this->i18n('a2034_config_gb_builder_toolbar_head'); ?></div></header>
						
						<div class="panel-body">				

							<div class="checkbox toggle">
								<label for="gb_builder_compacttoolbox">
									<input type="checkbox" name="gb_builder_compacttoolbox" id="gb_builder_compacttoolbox" value="checked" <?php echo @$config['gb_builder_compacttoolbox']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_compacttoolbox_info'); ?>
								</label>
							</div>
												
							<div class="checkbox toggle">
								<label for="gb_builder_draghandle">
									<input type="checkbox" name="gb_builder_draghandle" id="gb_builder_draghandle" value="checked" <?php echo @$config['gb_builder_draghandle']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_draghandle_info'); ?>
								</label>
							</div>

							<div class="checkbox toggle">
								<label for="gb_builder_offlinetoggle">
									<input type="checkbox" name="gb_builder_offlinetoggle" id="gb_builder_offlinetoggle" value="checked" <?php echo @$config['gb_builder_offlinetoggle']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_offlinetoggle_info'); ?>
								</label>
							</div>

							<div class="checkbox toggle">
								<label for="gb_builder_copypaste">
									<input type="checkbox" name="gb_builder_copypaste" id="gb_builder_copypaste" value="checked" <?php echo @$config['gb_builder_copypaste']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_copypaste_info'); ?>
								</label>
							</div>

							<div class="checkbox toggle">
								<label for="gb_builder_columns_quickswitch">
									<input type="checkbox" name="gb_builder_columns_quickswitch" id="gb_builder_columns_quickswitch" value="checked" <?php echo @$config['gb_builder_columns_quickswitch']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_columns_quickswitch_info'); ?>
								</label>
							</div>

							<div class="checkbox toggle">
								<label for="gb_builder_hidelabel">
									<input type="checkbox" name="gb_builder_hidelabel" id="gb_builder_hidelabel" value="checked" <?php echo @$config['gb_builder_hidelabel']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_hidelabel_info'); ?>
								</label>
							</div>

					
						</div>
					</div>


					<div class="panel panel-default">
						<header class="panel-heading"><div class="panel-title"><?php echo $this->i18n('a2034_config_gb_builder_fullscreen_head'); ?></div></header>
						
						<div class="panel-body">

							<div class="checkbox toggle">
								<label for="gb_builder_fullscreen_apply">
									<input type="checkbox" name="gb_builder_fullscreen_apply" id="gb_builder_fullscreen_apply" value="checked" <?php echo @$config['gb_builder_fullscreen_apply']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_fullscreen_apply_info'); ?>
								</label>
							</div>
							
							<div class="checkbox toggle">
								<label for="gb_builder_live_preview">
									<input type="checkbox" name="gb_builder_live_preview" id="gb_builder_live_preview" value="checked" <?php echo @$config['gb_builder_live_preview']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_live_preview_info'); ?>
								</label>
							</div>
											
						</div>
					</div>


					<div class="panel panel-default">
						<header class="panel-heading"><div class="panel-title"><?php echo $this->i18n('a2034_config_gb_builder_slicelist_head'); ?></div></header>
						
						<div class="panel-body">

							<div class="checkbox toggle">
								<label for="gb_builder_slicelist_fullscreen">
									<input type="checkbox" name="gb_builder_slicelist_fullscreen" id="gb_builder_slicelist_fullscreen" value="checked" <?php echo @$config['gb_builder_slicelist_fullscreen']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_slicelist_fullscreen_info'); ?>
								</label>
							</div>
				
							<div class="checkbox toggle">
								<label for="gb_builder_slicelist_preview">
									<input type="checkbox" name="gb_builder_slicelist_preview" id="gb_builder_slicelist_preview" value="checked" <?php echo @$config['gb_builder_slicelist_preview']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_slicelist_preview_info'); ?>
								</label>
							</div>
						
							
							<dl class="rex-form-group form-group"><dt></dt></dl>
							
								
							<label for="gb_builder_slicelist_maxheight"><?php echo $this->i18n('a2034_config_gb_builder_slicelist_maxheight'); ?></label>
							<select name="gb_builder_slicelist_maxheight" id="gb_builder_slicelist_maxheight" class="form-control">
								<?php foreach (['' => 'auto', 'small' => 'small', 'medium' => 'medium', 'large' => 'large'] as $maxHeightValue => $maxHeightKey): ?>
								<option value="<?php echo $maxHeightValue; ?>"<?php echo ((string) (@$config['gb_builder_slicelist_maxheight'] ?? '') === $maxHeightValue) ? ' selected' : ''; ?>><?php echo $this->i18n('a2034_config_gb_builder_slicelist_maxheight_'.$maxHeightKey); ?></option>
								<?php endforeach; ?>
							</select>
							
					
						</div>
					</div>

					
				</div>
			</div>			
			
		</div>		

		<footer class="panel-footer">
			<div class="btn-toolbar">
				<input class="btn btn-save" type="submit" name="submit" title="<?php echo $this->i18n('a2034_save'); ?>" value="<?php echo $this->i18n('a2034_save'); ?>" />
			</div>
		</footer>
		
	</div>


	<div class="panel panel-success">	
		<header class="panel-heading"><div class="panel-title"><?php echo $this->i18n('a2034_config_gb_builderstyle_head'); ?></div></header>
		
		<div class="panel-body">			

			<?php
			$gbMsPreview = Gridblock2Builder_MultiSite::isEnabled();
			$gbMsAvail = Gridblock2Builder_Availability::isEnabled();
			$gbMsActive = $gbMsPreview || $gbMsAvail;
			$gbMsDomains = $gbMsActive ? Gridblock2Builder_MultiSite::getDomains() : [];
			$gbMsStored = Gridblock2Builder_MultiSite::getSettings();
			$gbMsOrphans = $gbMsActive ? array_diff_key($gbMsStored, $gbMsDomains) : [];
			$gbMsTabs = ($gbMsDomains !== [] || $gbMsOrphans !== []);
			$gbMsCatalog = $gbMsAvail ? Gridblock2Builder_Availability::getCatalog() : ['modules' => [], 'elements' => []];
			$gbMsSelect = function (string $name, string $id, array $selected) use ($gbMsCatalog): string {
				$html = '<select multiple name="'.rex_escape($name).'" id="'.rex_escape($id).'" class="selectpicker form-control" data-live-search="true" data-actions-box="true" data-size="10" data-width="100%" data-selected-text-format="count > 3">';
				foreach (['modules' => 'a2034_config_ms_avail_group_modules', 'elements' => 'a2034_config_ms_avail_group_elements'] as $group => $labelKey) {
					$html .= '<optgroup label="'.rex_escape(rex_i18n::msg($labelKey)).'">';
					foreach ($gbMsCatalog[$group] as $key => $label) {
						$html .= '<option value="'.rex_escape($key).'"'.(in_array($key, $selected, true) ? ' selected="selected"' : '').'>'.rex_escape($label).'</option>';
					}
					$html .= '</optgroup>';
				}
				return $html.'</select>';
			};
			$gbMsFields = array_merge(array_diff(Gridblock2Builder_MultiSite::FIELDS, ['gb_builder_preview_wrapper_class']), ['gb_builder_preview_wrapper_class']);
			?>
			<?php if ($gbMsTabs): ?>
			<input type="hidden" name="gb_domain_present" value="1" />
			<div class="gb-ms-box">
			<h4 class="gb-config-subhead"><i class="rex-icon fa-earth-americas" aria-hidden="true"></i> <?php echo $this->i18n('a2034_config_ms_box_head'); ?></h4>
			<ul class="nav nav-tabs" role="tablist">
				<li class="active"><a href="#gb-ms-default" data-toggle="tab"><?php echo $this->i18n('a2034_config_ms_tab_default'); ?></a></li>
				<li><a href="#gb-ms-individual" data-toggle="tab"><?php echo $this->i18n('a2034_config_ms_tab_individual'); ?></a></li>
			</ul>
			<div class="tab-content gb-ms-tabcontent">
			<div class="tab-pane active" id="gb-ms-default">
			<?php endif; ?>

			<div class="gb-ms-fieldgrid">
			<dl class="rex-form-group form-group">
				<dt><label for="gb_builder_content_stylesheets"><?php echo $this->i18n('a2034_config_gb_builder_content_stylesheets'); ?></label></dt>
				<dd>
					<textarea name="gb_builder_content_stylesheets" id="gb_builder_content_stylesheets" rows="4" class="form-control"><?php echo rex_escape(@$config['gb_builder_content_stylesheets']); ?></textarea>
					<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_content_stylesheets_info'); ?></span>
				</dd>
			</dl>


			<dl class="rex-form-group form-group">
				<dt><label for="gb_builder_content_scripts"><?php echo $this->i18n('a2034_config_gb_builder_content_scripts'); ?></label></dt>
				<dd>
					<textarea name="gb_builder_content_scripts" id="gb_builder_content_scripts" rows="4" class="form-control"><?php echo rex_escape(@$config['gb_builder_content_scripts']); ?></textarea>
					<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_content_scripts_info'); ?></span>
				</dd>
			</dl>




			<dl class="rex-form-group form-group">
				<dt><label for="gb_builder_module_css_overrides"><?php echo $this->i18n('a2034_config_gb_builder_module_css_overrides'); ?></label></dt>
				<dd>
					<textarea name="gb_builder_module_css_overrides" id="gb_builder_module_css_overrides" rows="4" class="form-control"><?php echo rex_escape(@$config['gb_builder_module_css_overrides']); ?></textarea>
					<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_module_css_overrides_info'); ?></span>
				</dd>
			</dl>


			<dl class="rex-form-group form-group">
				<dt><label for="gb_builder_preview_visibility_fixes"><?php echo $this->i18n('a2034_config_gb_builder_preview_visibility_fixes'); ?></label></dt>
				<dd>
					<textarea name="gb_builder_preview_visibility_fixes" id="gb_builder_preview_visibility_fixes" rows="4" class="form-control"><?php echo rex_escape(@$config['gb_builder_preview_visibility_fixes']); ?></textarea>
					<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_preview_visibility_fixes_info'); ?></span>
				</dd>
			</dl>

			<dl class="rex-form-group form-group gb-ms-fullrow">
				<dt><label for="gb_builder_palette"><?php echo $this->i18n('a2034_config_gb_builder_palette'); ?></label></dt>
				<dd>
					<textarea name="gb_builder_palette" id="gb_builder_palette" rows="7" class="form-control" spellcheck="false" placeholder="<?php echo rex_escape(Gridblock2Palette::EXAMPLE); ?>"><?php echo rex_escape($gbPaletteDraft['base'] ?? @$config['gb_builder_palette']); ?></textarea>
					<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_palette_info'); ?></span>
				</dd>
			</dl>

			<dl class="rex-form-group form-group gb-ms-fullrow">
				<dt><label for="gb_builder_preview_wrapper_class"><?php echo $this->i18n('a2034_config_gb_builder_preview_wrapper_class'); ?></label></dt>
				<dd>
					<input type="text" name="gb_builder_preview_wrapper_class" id="gb_builder_preview_wrapper_class" value="<?php echo rex_escape(@$config['gb_builder_preview_wrapper_class']); ?>" class="form-control" placeholder="z. B. content" />
					<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_preview_wrapper_class_info'); ?></span>
				</dd>
			</dl>
			</div>

			<?php if ($gbMsTabs): ?>
			</div>
			<div class="tab-pane" id="gb-ms-individual">
				<dl class="rex-form-group form-group gb-ms-domainselect">
					<dt><label for="gb-ms-domain-select"><?php echo $this->i18n('a2034_config_ms_domain'); ?></label></dt>
					<dd>
						<select id="gb-ms-domain-select" class="selectpicker form-control" data-live-search="true" data-size="10">
							<?php foreach ($gbMsDomains as $gbMsId => $gbMsName): ?>
							<option value="<?php echo (int) $gbMsId; ?>"><?php echo rex_escape($gbMsName); ?></option>
							<?php endforeach; ?>
							<?php foreach ($gbMsOrphans as $gbMsId => $gbMsOrphan): ?>
							<option value="<?php echo (int) $gbMsId; ?>">&#9888; ID <?php echo (int) $gbMsId; ?> (<?php echo $this->i18n('a2034_config_ms_orphan_short'); ?>)</option>
							<?php endforeach; ?>
						</select>
					</dd>
				</dl>
				<?php foreach (($gbMsDomains + array_fill_keys(array_keys($gbMsOrphans), null)) as $gbMsId => $gbMsName): $gbMsOwn = (array) ($gbMsStored[(string) $gbMsId] ?? []); $gbMsMode = (($gbMsOwn['mode'] ?? 'merge') === 'replace') ? 'replace' : 'merge'; if ($gbPaletteDraft !== null && isset($gbPaletteDraft['domains'][$gbMsId]['gb_builder_palette'])) { $gbMsOwn['gb_builder_palette'] = (string) $gbPaletteDraft['domains'][$gbMsId]['gb_builder_palette']; } ?>
				<div class="gb-ms-domain" id="gb-ms-d<?php echo (int) $gbMsId; ?>" data-domain="<?php echo (int) $gbMsId; ?>" hidden>
					<?php if ($gbMsName === null): ?>
					<div class="alert alert-warning">
						<?php echo $this->i18n('a2034_config_ms_orphan'); ?>
						<div class="checkbox toggle">
							<label><input type="checkbox" name="gb_domain[<?php echo (int) $gbMsId; ?>][remove]" value="1" /> <?php echo $this->i18n('a2034_config_ms_orphan_remove'); ?></label>
						</div>
					</div>
					<?php endif; ?>
					<?php if ($gbMsPreview): ?>
					<dl class="rex-form-group form-group gb-ms-modedl">
						<dt><label for=""><?php echo $this->i18n('a2034_config_ms_mode'); ?></label><br /><button type="button" class="btn btn-default btn-sm gb-ms-copy" data-pane="gb-ms-d<?php echo (int) $gbMsId; ?>"><?php echo $this->i18n('a2034_config_ms_copy'); ?></button></dt>
						<dd>
							<div class="radio toggle switch">
								<label><input type="radio" name="gb_domain[<?php echo (int) $gbMsId; ?>][mode]" value="merge"<?php echo ($gbMsMode === 'merge') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_ms_mode_merge'); ?></label>
								<label><input type="radio" name="gb_domain[<?php echo (int) $gbMsId; ?>][mode]" value="replace"<?php echo ($gbMsMode === 'replace') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_ms_mode_replace'); ?></label>
							</div>
							<span class="infoblock"><?php echo $this->i18n('a2034_config_ms_mode_info'); ?></span>
						</dd>
					</dl>
					<div class="gb-ms-fieldgrid">
					<?php foreach ($gbMsFields as $gbMsField): ?>
					<dl class="rex-form-group form-group<?php echo in_array($gbMsField, ['gb_builder_preview_wrapper_class', 'gb_builder_palette'], true) ? ' gb-ms-fullrow' : ''; ?>">
						<dt><label for="gb-ms-<?php echo (int) $gbMsId; ?>-<?php echo $gbMsField; ?>"><?php echo $this->i18n('a2034_config_'.$gbMsField); ?></label></dt>
						<dd>
							<?php if ($gbMsField === 'gb_builder_preview_wrapper_class'): ?>
							<input type="text" name="gb_domain[<?php echo (int) $gbMsId; ?>][<?php echo $gbMsField; ?>]" id="gb-ms-<?php echo (int) $gbMsId; ?>-<?php echo $gbMsField; ?>" data-field="<?php echo $gbMsField; ?>" class="form-control" placeholder="z. B. content" value="<?php echo rex_escape((string) ($gbMsOwn[$gbMsField] ?? '')); ?>" />
							<?php else: ?>
							<textarea name="gb_domain[<?php echo (int) $gbMsId; ?>][<?php echo $gbMsField; ?>]" id="gb-ms-<?php echo (int) $gbMsId; ?>-<?php echo $gbMsField; ?>" data-field="<?php echo $gbMsField; ?>" rows="<?php echo ($gbMsField === 'gb_builder_palette') ? 7 : 4; ?>" class="form-control"<?php echo ($gbMsField === 'gb_builder_palette') ? ' spellcheck="false" placeholder="'.rex_escape(Gridblock2Palette::EXAMPLE).'"' : ''; ?>><?php echo rex_escape((string) ($gbMsOwn[$gbMsField] ?? '')); ?></textarea>
							<?php endif; ?>
							<span class="infoblock"><?php echo $this->i18n('a2034_config_'.$gbMsField.'_info'); ?></span>
						</dd>
					</dl>
					<?php endforeach; ?>
					</div>
					<?php endif; ?>
					<?php if ($gbMsAvail):
						$gbMsRule = Gridblock2Builder_Availability::getDomainRule((int) $gbMsId) ?? ['mode' => 'merge', 'all' => false, 'items' => [], 'deny' => []];
						$gbMsEff = ($gbMsName !== null) ? Gridblock2Builder_Availability::getEffectiveItems((int) $gbMsId) : ['modules' => [], 'elements' => []];
					?>
					<br>
					<h4 class="gb-config-subhead"><i class="rex-icon fa-th-large" aria-hidden="true"></i> <?php echo $this->i18n('a2034_config_ms_avail_head'); ?></h4>
					<input type="hidden" name="gb_domain[<?php echo (int) $gbMsId; ?>][avail_present]" value="1" />
					<?php
						$gbMsOverview = function (string $suffix) use ($gbMsId, $gbMsName, $gbMsEff): string {
							if ($gbMsName === null) {
								return '';
							}
							$id = 'gb-avail-overview-'.(int) $gbMsId.'-'.$suffix;
							$html = '<div class="gb-avail-overview" id="'.$id.'" hidden><strong>'.rex_escape(rex_i18n::msg('a2034_config_ms_avail_overview')).'</strong><div class="gb-avail-overview-cols">';
							foreach (['modules' => 'a2034_config_ms_avail_group_modules', 'elements' => 'a2034_config_ms_avail_group_elements'] as $group => $labelKey) {
								$html .= '<div><div class="gb-avail-overview-group">'.rex_escape(rex_i18n::msg($labelKey)).' ('.count($gbMsEff[$group]).')</div><ul class="gb-avail-overview-list">';
								foreach ($gbMsEff[$group] as $label) {
									$html .= '<li>'.rex_escape($label).'</li>';
								}
								$html .= '</ul></div>';
							}
							return $html.'</div></div>';
						};
						$gbMsOverviewBtn = function (string $suffix) use ($gbMsId, $gbMsName): string {
							if ($gbMsName === null) {
								return '';
							}
							$id = 'gb-avail-overview-'.(int) $gbMsId.'-'.$suffix;
							return '<button type="button" class="btn btn-default gb-avail-overview-toggle" data-target="'.$id.'" aria-expanded="false" aria-controls="'.$id.'" title="'.rex_escape(rex_i18n::msg('a2034_config_ms_avail_overview_toggle')).'"><i class="rex-icon fa-list" aria-hidden="true"></i></button>';
						};
					?>
					<dl class="rex-form-group form-group">
						<dt><label for=""><?php echo $this->i18n('a2034_config_ms_avail_mode'); ?></label></dt>
						<dd>
							<div class="radio toggle switch">
								<label><input type="radio" class="gb-avail-mode" name="gb_domain[<?php echo (int) $gbMsId; ?>][avail_mode]" value="merge"<?php echo ($gbMsRule['mode'] === 'merge') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_ms_avail_mode_merge'); ?></label>
								<label><input type="radio" class="gb-avail-mode" name="gb_domain[<?php echo (int) $gbMsId; ?>][avail_mode]" value="replace"<?php echo ($gbMsRule['mode'] === 'replace') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_ms_avail_mode_replace'); ?></label>
							</div>
						</dd>
					</dl>
					<dl class="rex-form-group form-group gb-avail-merge">
						<dt><label for="gb-avail-deny-<?php echo (int) $gbMsId; ?>"><?php echo $this->i18n('a2034_config_ms_avail_deny'); ?></label></dt>
						<dd>
							<div class="gb-avail-selectrow">
								<div class="gb-avail-selectcol"><?php echo $gbMsSelect('gb_domain['.(int) $gbMsId.'][avail_deny][]', 'gb-avail-deny-'.(int) $gbMsId, $gbMsRule['deny']); ?></div>
								<?php echo $gbMsOverviewBtn('merge'); ?>
							</div>
							<?php echo $gbMsOverview('merge'); ?>
						</dd>
					</dl>
					<dl class="rex-form-group form-group gb-avail-replace">
						<dt><label for="gb-avail-all-<?php echo (int) $gbMsId; ?>"><?php echo $this->i18n('a2034_config_ms_avail_items'); ?></label></dt>
						<dd>
							<div class="checkbox toggle">
								<label for="gb-avail-all-<?php echo (int) $gbMsId; ?>"><input type="checkbox" id="gb-avail-all-<?php echo (int) $gbMsId; ?>" name="gb_domain[<?php echo (int) $gbMsId; ?>][avail_all]" value="1"<?php echo $gbMsRule['all'] ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_ms_avail_all'); ?></label>
							</div>
							<div class="gb-avail-selectrow">
								<div class="gb-avail-selectcol gb-avail-select-wrap" data-toggle-by="gb-avail-all-<?php echo (int) $gbMsId; ?>">
									<?php echo $gbMsSelect('gb_domain['.(int) $gbMsId.'][avail_items][]', 'gb-avail-items-'.(int) $gbMsId, $gbMsRule['items']); ?>
								</div>
								<?php echo $gbMsOverviewBtn('replace'); ?>
							</div>
							<?php echo $gbMsOverview('replace'); ?>
						</dd>
					</dl>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
			</div>
			</div>
			<script>
			(function () {
				var select = document.getElementById('gb-ms-domain-select');
				var panes = document.querySelectorAll('.gb-ms-domain');
				var storageKey = 'gb2_ms_domain';
				function showDomain(id) {
					panes.forEach(function (pane) { pane.hidden = (pane.dataset.domain !== String(id)); });
					try { window.sessionStorage.setItem(storageKey, String(id)); } catch (e) {}
				}
				var initial = null;
				try { initial = window.sessionStorage.getItem(storageKey); } catch (e) {}
				if (!initial || !select.querySelector('option[value="' + initial + '"]')) { initial = select.options.length ? select.options[0].value : null; }
				if (initial !== null) { select.value = initial; showDomain(initial); }
				select.addEventListener('change', function () { showDomain(select.value); });

				document.querySelectorAll('.gb-ms-copy').forEach(function (btn) {
					btn.addEventListener('click', function () {
						if (!window.confirm(<?php echo json_encode($this->i18n('a2034_config_ms_copy_confirm')); ?>)) { return; }
						var pane = document.getElementById(btn.dataset.pane);
						pane.querySelectorAll('textarea[data-field], input[data-field]').forEach(function (ta) {
							var source = document.querySelector('[name="' + ta.dataset.field + '"]');
							if (source) { ta.value = source.value; }
						});
					});
				});
			})();
			</script>
			<script>
			(function () {
				function syncWrap(wrap) {
					var toggle = document.getElementById(wrap.dataset.toggleBy);
					var off = !!(toggle && toggle.checked);
					var row = wrap.closest('.gb-avail-selectrow');
					row.hidden = off;
					if (off) {
						var box = row.parentElement.querySelector('.gb-avail-overview');
						if (box) { box.hidden = true; }
						row.querySelectorAll('.gb-avail-overview-toggle').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
					}
				}
				function syncMode(domain) {
					var checked = domain.querySelector('.gb-avail-mode:checked');
					var replace = checked && checked.value === 'replace';
					domain.querySelectorAll('.gb-avail-merge').forEach(function (el) { el.style.display = replace ? 'none' : ''; });
					domain.querySelectorAll('.gb-avail-replace').forEach(function (el) { el.style.display = replace ? '' : 'none'; });
				}
				document.querySelectorAll('.gb-avail-select-wrap').forEach(function (wrap) {
					var toggle = document.getElementById(wrap.dataset.toggleBy);
					syncWrap(wrap);
					if (toggle) { toggle.addEventListener('change', function () { syncWrap(wrap); }); }
				});
				document.querySelectorAll('.gb-ms-domain').forEach(function (domain) {
					syncMode(domain);
					domain.querySelectorAll('.gb-avail-mode').forEach(function (radio) { radio.addEventListener('change', function () { syncMode(domain); }); });
				});
				document.querySelectorAll('.gb-avail-overview-toggle').forEach(function (btn) {
					btn.addEventListener('click', function () {
						var box = document.getElementById(btn.dataset.target);
						if (!box) { return; }
						box.hidden = !box.hidden;
						document.querySelectorAll('.gb-avail-overview-toggle[data-target="' + btn.dataset.target + '"]').forEach(function (b) { b.setAttribute('aria-expanded', box.hidden ? 'false' : 'true'); });
					});
				});
			})();
			</script>
			<h4 class="gb-config-subhead"><i class="rex-icon fa-sliders" aria-hidden="true"></i> <?php echo $this->i18n('a2034_config_ms_general_head'); ?></h4>
			<?php endif; ?>


			<dl class="rex-form-group form-group"><dt></dt></dl>
			

			<?php $gbGapModeCurrent = Gridblock2Builder_Tree::getGapMode(); ?>
			<dl class="rex-form-group form-group">
				<dt><label for=""><?php echo $this->i18n('a2034_wizard_step_gap_mode_label'); ?></label></dt>
				<dd>
					<div class="radio toggle switch">
						<label><input type="radio" name="gb_builder_gap_mode" value="native"<?php echo ($gbGapModeCurrent === 'native') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_wizard_step_gap_native'); ?></label>
						<label><input type="radio" name="gb_builder_gap_mode" value="cs"<?php echo ($gbGapModeCurrent === 'cs') ? ' checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_wizard_step_gap_cs'); ?></label>
					</div>
				</dd>
			</dl>


			<div class="gb-config-gap-option" data-gap-mode="cs">
			<dl class="rex-form-group form-group">
				<dt><label for="gb_builder_gap_cs_field"><?php echo $this->i18n('a2034_config_gb_builder_gap_cs_field'); ?></label></dt>
				<dd>
					<?php
					$gbGapCsFieldCurrent = (string) @$config['gb_builder_gap_cs_field'];
					$gbCsFieldChoices = Gridblock2Builder_Tree::getCsFieldChoices();
					$gbCsFieldScopeLabels = ['template' => $this->i18n('a2034_config_gb_builder_gap_cs_field_scope_template'), 'columns' => $this->i18n('a2034_config_gb_builder_gap_cs_field_scope_columns'), 'section' => $this->i18n('a2034_config_gb_builder_gap_cs_field_scope_section')];
					?>
					<select name="gb_builder_gap_cs_field" id="gb_builder_gap_cs_field" class="selectpicker form-control" data-live-search="true" data-size="10">
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
					<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_gap_cs_field_info'); ?></span>
				</dd>
			</dl>
			</div>


			<div class="gb-config-gap-option" data-gap-mode="native">
			<dl class="rex-form-group form-group">
				<dt><label for="gb_builder_gap_native_fallback"><?php echo $this->i18n('a2034_config_gb_builder_gap_native_fallback'); ?></label></dt>
				<dd>
					<textarea name="gb_builder_gap_native_fallback" id="gb_builder_gap_native_fallback" rows="7" class="form-control" spellcheck="false" placeholder="<?php echo rex_escape(Gridblock2Builder_Tree::GAP_NATIVE_EXAMPLE); ?>"><?php echo rex_escape($gbGapDraft !== null ? $gbGapDraft : @$config['gb_builder_gap_native_fallback']); ?></textarea>
					<span class="infoblock"><?php echo $this->i18n('a2034_config_gb_builder_gap_native_fallback_info'); ?></span>
				</dd>
			</dl>


			</div>

			<dl class="rex-form-group form-group">
				<dt></dt>
				<dd>
					<div class="checkbox toggle">
						<label for="gb_builder_native_grid_css_backend">
							<input type="checkbox" name="gb_builder_native_grid_css_backend" id="gb_builder_native_grid_css_backend" value="checked" <?php echo (@$config['gb_builder_native_grid_css_backend'] ?? 'checked') === 'checked' ? 'checked="checked"' : ''; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_native_grid_css_backend_info'); ?>
						</label>
					</div>
					<div class="checkbox toggle">
						<label for="gb_builder_native_grid_css_frontend">
							<input type="checkbox" name="gb_builder_native_grid_css_frontend" id="gb_builder_native_grid_css_frontend" value="checked" <?php echo @$config['gb_builder_native_grid_css_frontend']; ?> /> <?php echo $this->i18n('a2034_config_gb_builder_native_grid_css_frontend_info'); ?>
						</label>
					</div>
				</dd>
			</dl>

			<dl class="rex-form-group form-group">
				<dt><label for="gb_elements_css_frontend"><?php echo $this->i18n('a2034_config_gb_elements_css_frontend'); ?></label></dt>
				<dd>
					<div class="checkbox toggle">
						<label for="gb_elements_css_frontend">
							<input type="checkbox" name="gb_elements_css_frontend" id="gb_elements_css_frontend" value="checked" <?php echo @$config['gb_elements_css_frontend']; ?> /> <?php echo $this->i18n('a2034_config_gb_elements_css_frontend_info'); ?>
						</label>
					</div>
				</dd>
			</dl>

			<script>
			(function () {
				var radios = document.querySelectorAll('input[name="gb_builder_gap_mode"]');
				var groups = document.querySelectorAll('.gb-config-gap-option');
				var sync = function () {
					var checked = document.querySelector('input[name="gb_builder_gap_mode"]:checked');
					var mode = checked ? checked.value : 'native';
					groups.forEach(function (g) { g.style.display = (g.getAttribute('data-gap-mode') === mode) ? '' : 'none'; });
				};
				radios.forEach(function (r) { r.addEventListener('change', sync); });
				sync();
			})();
			</script>
        
		
		</div>		
        
		<footer class="panel-footer">
			<div class="btn-toolbar">
				<input class="btn btn-save" type="submit" name="submit" title="<?php echo $this->i18n('a2034_save'); ?>" value="<?php echo $this->i18n('a2034_save'); ?>" />
			</div>
		</footer>
	
	</div>
	
</section>	
</form>