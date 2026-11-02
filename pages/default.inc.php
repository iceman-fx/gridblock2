<?php
/*
	Redaxo-Addon Gridblock²
	Verwaltung: default
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** RexStan: Vars vom Check ausschließen */
/** @var rex_addon $this */
/** @var array $config */
/** @var string $func */
/** @var string $page */
/** @var rex_csrf_token $gbCsrf */
/** @var string $gbCsrfUrl */
/** @var string $subpage */
/** @var string $mypage */


//Variablen deklarieren
$mode = rex_request('mode');
$id = intval(rex_request('id'));
$form_error = 0;

$_SESSION['as_sbeg_gridtemplatelist'] = (!isset($_SESSION['as_sbeg_gridtemplatelist'])) ? "" : $_SESSION['as_sbeg_gridtemplatelist'];


//Formular dieser Seite verarbeiten
if ($func == "save" && (isset($_POST['submit']) || isset($_POST['submit-apply'])) ):
	$fields = array("f_title", "f_columns");
	foreach ($fields as $field):
		$tmp = rex_post($field);
		$form_error = (empty($tmp)) ? 1 : $form_error;
	endforeach;


	if ($form_error):
		//Pflichtfelder fehlen
		echo rex_view::warning($this->i18n('a2034_entry_emptyfields'));
	else:
		//Eintrag speichern
		$prio 	= rex_post('f_prio', 'int');
		
			//maxPrio prüfen
			$dbp = rex_sql::factory();
			$dbp->setQuery("SELECT id FROM ".rex::getTable('2034_gridtemplates'));
			$prio = ($prio <= 0 || $prio > $dbp->getRows()) ? ($dbp->getRows()+1) : $prio;		


		$db = rex_sql::factory();
		$db->setTable(rex::getTable('2034_gridtemplates'));

		$db->setValue("title", 			rex_post('f_title'));
		$db->setValue("prio", 			$prio);
		$db->setValue("description", 	rex_post('f_description'));
		$db->setValue("columns", 		rex_post('f_columns', 'int'));
		if (rex::getUser()->isAdmin()):
			$db->setValue("template", 	rex_post('f_template'));
		elseif ($id <= 0):
			$db->setValue("template", 	'');
		endif;
		$db->setValue("preview", 		rex_post('f_preview'));
		$db->setValue("status", 		rex_post('f_status'));
		$db->setValue("cssclass", 		rex_post('f_cssclass'));

		if ($id > 0):
			$db->addGlobalUpdateFields();						//Standard Datumsfelder hinzufügen
			$db->setWhere("id = '".$id."'");
			$dbreturn = $db->update();
			$lastID = $id;

			$form_error = (isset($_POST['submit-apply'])) ? 1 : $form_error;
		else:
			$db->addGlobalCreateFields();						//Standard Datumsfelder hinzufügen
			$dbreturn = $db->insert();
			$lastID = $db->getLastId();
		endif;

		if ($dbreturn):
			//gespeichert
			echo rex_view::info($this->i18n('a2034_entry_saved'));

			//Prioritäten korrigieren
			$dbp = rex_sql::factory();
			$dbp->setQuery("SELECT id, prio FROM ".rex::getTable('2034_gridtemplates')." WHERE id <> '".$lastID."' ORDER BY prio ASC");
				$i = 1;
				foreach ($dbp as $row):
					$i = ($i == rex_post('f_prio', 'int')) ? ($i+1) : $i;
					
					$db = rex_sql::factory();
					$db->setQuery("UPDATE ".rex::getTable('2034_gridtemplates')." SET prio = '".$i."' WHERE id = '".$row->getValue('id')."'");
					$i++;
				endforeach;			
			

		else:
			//Fehler beim Speichern
			echo rex_view::warning($this->i18n('a2034_error'));
		endif;
	endif;

elseif ($func == "delete" && $id > 0):
	//Eintrag löschen - mit möglicher Prüfung auf Zuweisung
	$db = rex_sql::factory();
	$db->setQuery("SELECT id FROM ".rex::getTable('article_slice')." WHERE value17 like '%selectedTemplate\":\"".$id."\"%'");

	if ($db->getRows() <= 0):
		//löschen
		$dbp = rex_sql::factory();
		$dbp->setQuery("SELECT id FROM ".rex::getTable('2034_gridtemplates'));
		$maxPrio = $dbp->getRows();

		$dbp = rex_sql::factory();
		$dbp->setQuery("SELECT prio FROM ".rex::getTable('2034_gridtemplates')." WHERE id = '".$id."'");
		$lastPrio = ($dbp->getRows() > 0) ? $dbp->getValue('prio') : 0;

		$db = rex_sql::factory();
		$db->setTable(rex::getTable('2034_gridtemplates'));
		$db->setWhere("id = '".$id."'");

		if ($db->delete()):
			echo rex_view::info($this->i18n('a2034_entry_deleted'));

			//Prioritäten korrigieren
			$dbp = rex_sql::factory();
			$dbp->setQuery("SELECT id FROM ".rex::getTable('2034_gridtemplates')." ORDER BY prio ASC");
				$i = 1;
				foreach ($dbp as $row):
					$db = rex_sql::factory();
					$db->setQuery("UPDATE ".rex::getTable('2034_gridtemplates')." SET prio = '".$i."' WHERE id = '".$row->getValue('id')."'");
					$i++;
				endforeach;
			
		else:
			echo rex_view::warning($this->i18n('a2034_error_deleted'));
		endif;
	else:
		//nicht löschen aufgrund gültiger Zuweisung
		echo rex_view::warning($this->i18n('a2034_entry_used'));
	endif;

elseif ($func == "status" && $id > 0):
	//Status setzen
	$db = rex_sql::factory();
	$db->setQuery("SELECT status FROM ".rex::getTable('2034_gridtemplates')." WHERE id = '".$id."' LIMIT 0,1");
	$dbe = $db->getArray();	//mehrdimensionales Array kommt raus

	$newstatus = ($dbe[0]['status'] != "checked") ? "checked" : "";

	$db = rex_sql::factory();
	$db->setTable(rex::getTable('2034_gridtemplates'));
	$db->setWhere("id = '".$id."'");

	$db->setValue("status", $newstatus);
	$db->update();

elseif ($func == "duplicate" && $id > 0):
	//Eintrag duplizieren
	$db = rex_sql::factory();
	$db->setQuery("SELECT * FROM ".rex::getTable('2034_gridtemplates')." WHERE id = '".$id."'");

	if ($db->getRows() > 0):
		$dbp = rex_sql::factory();
		$dbp->setQuery("SELECT id FROM ".rex::getTable('2034_gridtemplates'));
		$maxPrio = $dbp->getRows();

		$dbe = $db->getArray();	//mehrdimensionales Array kommt raus
		$db = rex_sql::factory();
		$db->setTable(rex::getTable('2034_gridtemplates'));
		$db->setValue("prio", $maxPrio+1);

		$skipKeys = ['id', 'prio', 'status', 'createdate', 'createuser', 'updatedate', 'updateuser'];
		foreach ($dbe[0] as $key=>$val):
			if (in_array($key, $skipKeys, true)) { continue; }
			if ($key == 'title') { $val = rex_gridblock2_helper::duplicateName($val); }

			$db->setValue($key, $val);
		endforeach;
		$db->addGlobalCreateFields();
		$db->addGlobalUpdateFields();

		$dbreturn = $db->insert();

		$lastID = $db->getLastId();
	endif;

elseif ($func == "insert_default_templates"):
	//Beispieltemplates installieren
	$db = rex_sql::factory();
	$db->setQuery("SELECT id FROM ".rex::getTable('2034_gridtemplates'));

	if ($db->getRows() <= 0):
		rex_sql_util::importDump($this->getPath('install/install.sql'));
	endif;

elseif ($func == "import_templatearchive"):
	//Templatearchiv importieren
	if (@$this->getConfig('config')['gb_builder_templates_import_export'] !== 'checked'):
		echo rex_view::error($this->i18n('a2034_config_no_permission'));
	elseif ($_FILES["importfile"]["error"] > 0):
		echo rex_view::warning($this->i18n('a2034_error_templates_fileerror'));
	else:
		$return = rex_gridblock2_importer::import($_FILES["importfile"]);

		if ($return == "success"):
			echo rex_view::info($this->i18n('a2034_templates_imported'));
		else:
			echo rex_view::warning($return);
		endif;
	endif;

elseif ($func == "export_templates"):
	if (@$this->getConfig('config')['gb_builder_templates_import_export'] !== 'checked'):
		echo rex_view::error($this->i18n('a2034_config_no_permission'));
		exit;
	endif;

	$return = rex_gridblock2_importer::export();

	if ($return != "success"):
		echo rex_view::warning($return);
	endif;
	exit;

elseif ($func == "wizard_dismiss"):
	$this->setConfig('gb_builder_wizard_dismissed', time());
	exit;

elseif ($func == "wizard_save_settings"):
	$wizardConfig = $this->getConfig('config');

	$wizardPalette = (string) rex_post('gb_builder_palette', 'string', '');
	$wizardPaletteErrors = Gridblock2Palette::parse($wizardPalette)['errors'];
	if ($wizardPaletteErrors !== []) {
		echo '<div id="gb-wizard-ajax-result">';
		echo '<div id="gb-wizard-save-status" data-ok="0" style="display:none"></div>';
		echo '<div class="alert alert-danger">'.$this->i18n('a2034_palette_err_head').'<br>'.implode('<br>', $wizardPaletteErrors).'</div>';
		echo '</div>';
		exit;
	}

	$old = rex_addon::get('gridblock');
	$gb1ConfigCopied = false;
	if (rex_post('gb1_copy', 'string', '') === '1' && $old && $old->isInstalled()) {
		$oldConfig = $old->getConfig('config');
		if (is_array($oldConfig)) {
			$wizardConfig = array_merge($wizardConfig, $oldConfig);
			$gb1ConfigCopied = true;
		}
	}

	$wizardMods = rex_post('modules');
	$wizardConfig['modulesmode'] = rex_post('modulesmode', 'string', 'allow');
	$wizardConfig['modules'] = '#'.(is_array($wizardMods) ? implode('#', $wizardMods) : '').'#';

	$wizardConfig['gb_builder_gap_mode'] = (rex_post('gb_builder_gap_mode', 'string', 'native') === 'cs') ? 'cs' : 'native';
	if ($wizardConfig['gb_builder_gap_mode'] === 'cs') {
		$wizardConfig['gb_builder_gap_cs_field'] = rex_post('gb_builder_gap_cs_field');
	} else {
		$wizardConfig['gb_builder_gap_cs_field'] = '';
		$wizardConfig['gb_builder_gap_native_fallback'] = rex_post('gb_builder_gap_native_fallback');
	}
	$wizardConfig['gb_builder_native_grid_css_frontend'] = rex_post('gb_builder_native_grid_css_frontend');
	$wizardConfig['gb_builder_native_grid_css_backend'] = rex_post('gb_builder_native_grid_css_backend');
	$wizardConfig['gb_elements_css_frontend'] = rex_post('gb_elements_css_frontend');

	$wizardConfig['gb_builder_preview_mode'] = rex_post('gb_builder_preview_mode', 'string', 'iframe');
	$wizardConfig['gb_builder_element_preview_iframe'] = rex_post('gb_builder_element_preview_iframe');
	$wizardConfig['gb_builder_content_stylesheets'] = rex_post('gb_builder_content_stylesheets');
	$wizardConfig['gb_builder_content_scripts'] = rex_post('gb_builder_content_scripts');
	$wizardConfig['gb_builder_palette'] = $wizardPalette;

	$ok = $this->setConfig('config', $wizardConfig);

	//generierte native Grid-CSS-Datei (siehe boot.php/Onlinehilfe) nach jedem Speichern frisch schreiben
	Gridblock2Builder_Tree::writeNativeGridCssFile();
	Gridblock2Builder_Tree::writeElementsCssFile();

	echo '<div id="gb-wizard-ajax-result">';
	echo '<div id="gb-wizard-save-status" data-ok="'.($ok ? '1' : '0').'" style="display:none"></div>';

	if (!$ok) {
		echo '<div class="alert alert-danger">'.$this->i18n('a2034_error').'</div>';
		echo '</div>';
		exit;
	}

	$this->setConfig('gb_builder_wizard_dismissed', time());

	if (rex_post('gb1_deactivate', 'string', '') === '1' && $old && $old->isAvailable()) {
		$manager = rex_addon_manager::factory($old);
		if ($manager->deactivate()) {
			echo '<div class="alert alert-success">'.$this->i18n('a2034_wizard_gb1_deactivated').'<div class="alert alert-warning" style="margin:10px 0 0">'.$this->i18n('a2034_wizard_gb1_delete_warning').'</div></div>';
		} else {
			echo '<div class="alert alert-danger">'.$this->i18n('a2034_wizard_gb1_deactivate_error').' '.rex_escape($manager->getMessage()).'</div>';
		}
	}

	if (rex_post('gb1_copy', 'string', '') === '1' && $old && $old->isInstalled()) {
		if ($gb1ConfigCopied) {
			echo '<div class="alert alert-success">'.$this->i18n('a2034_wizard_config_copied').'</div>';

			$oldContentsettingsDir = rex_path::pluginData('gridblock', 'contentsettings');
			if (is_dir($oldContentsettingsDir) && rex_dir::copy($oldContentsettingsDir, $this->getDataPath('contentsettings'))) {
				echo '<div class="alert alert-success">'.$this->i18n('a2034_wizard_contentsettings_copied').'</div>';
			}

			try {
				$gbTemplateColumns = 'id, prio, cat, title, description, columns, template, preview, status, createdate, createuser, updatedate, updateuser';
				rex_sql::factory()->setQuery('INSERT IGNORE INTO '.rex::getTable('2034_gridtemplates').' ('.$gbTemplateColumns.') SELECT '.$gbTemplateColumns.' FROM '.rex::getTable('1620_gridtemplates'));
				echo '<div class="alert alert-success">'.$this->i18n('a2034_wizard_templates_copied').'</div>';
			} catch (rex_sql_exception $e) {
				echo '<div class="alert alert-danger">'.$this->i18n('a2034_wizard_templates_copy_error').' '.rex_escape($e->getMessage()).'</div>';
			}
		} else {
			echo '<div class="alert alert-warning">'.$this->i18n('a2034_wizard_config_copy_none').'</div>';
		}
	}

	if (rex_post('gb1_replace_module', 'string', '') === '1') {
		$gbNewInput = (string) rex_file::get($this->getPath('install/input.php'));
		$gbNewOutput = (string) rex_file::get($this->getPath('install/output.php'));
		$db = rex_sql::factory();
		$db->setQuery('SELECT id, name FROM '.rex::getTable('module').' WHERE input LIKE "%/* GRID_MODULE_IDENTIFIER | DONT REMOVE */%"');
		if ($gbNewInput === '' || $gbNewOutput === '') {
			echo '<div class="alert alert-danger">'.$this->i18n('a2034_wizard_module_replace_failed').'</div>';
		} elseif ($db->getRows() > 0) {
			$gbReplacedNames = [];
			foreach ($db as $gbModRow) {
				$db2 = rex_sql::factory();
				$db2->setTable(rex::getTable('module'));
				$db2->setValue('input', $gbNewInput);
				$db2->setValue('output', $gbNewOutput);
				$db2->addGlobalUpdateFields();
				$db2->setWhere(['id' => $gbModRow->getValue('id')]);
				$db2->update();
				$gbReplacedNames[] = $gbModRow->getValue('name').' ['.$gbModRow->getValue('id').']';
			}
			echo '<div class="alert alert-success">'.$this->i18n('a2034_wizard_module_replaced', rex_escape(implode(', ', $gbReplacedNames))).'</div>';
		} else {
			echo '<div class="alert alert-info">'.$this->i18n('a2034_wizard_module_none_found').'</div>';
		}
	} elseif (rex_post('gb1_migrate_module', 'string', '') === '1') {
		$db = rex_sql::factory();
		$db->setQuery('SELECT id, input, output FROM '.rex::getTable('module').' WHERE input LIKE "%/* GRID_MODULE_IDENTIFIER | DONT REMOVE */%"');
		if ($db->hasNext()) {
			$db2 = rex_sql::factory();
			$db2->setTable(rex::getTable('module'));
			$db2->setValue('input', rex_gridblock2::replaceLegacyIdentifiers((string) $db->getValue('input')));
			$db2->setValue('output', rex_gridblock2::replaceLegacyIdentifiers((string) $db->getValue('output')));
			$db2->addGlobalUpdateFields();
			$db2->setWhere(['id' => $db->getValue('id')]);
			$db2->update();
			echo '<div class="alert alert-success">'.$this->i18n('a2034_wizard_module_migrated').'</div>';
		} else {
			echo '<div class="alert alert-info">'.$this->i18n('a2034_wizard_module_none_found').'</div>';
		}
	}

	if ($old && $old->isInstalled()) {
		if (rex_post('gb1_edit_systemelements', 'string', '') === '1') {
			//Ziel ist AUSSCHLIESSLICH die update-sichere data/-Kopie - nie die addon-eigene Default-Kopie.
			$gbSystemElementTargets = [
				'gb1_systemelement_section' => ['system/section', 'output.php'],
				'gb1_systemelement_columnsrow' => ['system/column', 'columnsrow.php'],
				'gb1_systemelement_column' => ['system/column', 'column.php'],
			];

			$gbSystemElementsSaved = 0;
			foreach ($gbSystemElementTargets as $gbPostKey => $gbTarget) {
				$gbCode = rex_post($gbPostKey, 'string', '');
				if ($gbCode === '') {
					continue;
				}
				if (Gridblock2Builder_Tree::saveSystemElementOverride($gbTarget[0], $gbTarget[1], $gbCode)) {
					$gbSystemElementsSaved++;
				}
			}

			if ($gbSystemElementsSaved > 0) {
				echo '<div class="alert alert-success">'.$this->i18n('a2034_wizard_systemelements_saved').'</div>';
			} else {
				echo '<div class="alert alert-info">'.$this->i18n('a2034_wizard_systemelements_skipped').'</div>';
			}
		} else {
			echo '<div class="alert alert-info">'.$this->i18n('a2034_wizard_systemelements_skipped').'</div>';
		}
	}

	if (!$old || !$old->isInstalled()) {
		if (rex_post('install_default_templates', 'string', '') === '1') {
			$db = rex_sql::factory();
			$db->setQuery('SELECT id FROM '.rex::getTable('2034_gridtemplates'));

			if ($db->getRows() <= 0) {
				try {
					rex_sql_util::importDump($this->getPath('install/install.sql'));
					echo '<div class="alert alert-success">'.$this->i18n('a2034_wizard_default_templates_installed').'</div>';
				} catch (rex_sql_exception $e) {
					echo '<div class="alert alert-danger">'.$this->i18n('a2034_wizard_default_templates_error').' '.rex_escape($e->getMessage()).'</div>';
				}
			} else {
				echo '<div class="alert alert-info">'.$this->i18n('a2034_wizard_default_templates_exists').'</div>';
			}
		} else {
			echo '<div class="alert alert-info">'.$this->i18n('a2034_wizard_default_templates_skipped').'</div>';
		}
	}

	echo '</div>';
	exit;

endif;


//Formular oder Liste ausgeben
if ($func == "update" || $func == "insert" || $form_error == 1):
	//Formular ausgeben
	if (($mode == "update" || $func == "update") && $id > 0):
		$db = rex_sql::factory();
		$db->setQuery("SELECT * FROM ".rex::getTable('2034_gridtemplates')." WHERE id = '".$id."' LIMIT 0,1");
		$dbe = $db->getArray();	//mehrdimensionales Array kommt raus
	endif;

	//Std.vorgaben der Felder setzen
	if (!isset($dbe) || (is_array($dbe) && count($dbe) <= 0)):
		$dbe[0]['title'] = $dbe[0]['description'] = $dbe[0]["columns"] = $dbe[0]['template'] = $dbe[0]['preview'] = $dbe[0]['status'] = $dbe[0]['cssclass'] = '';
	endif;
	//$dbe[0] = array_map('htmlspecialchars', $dbe[0]);

	//Insert-Vorgaben
	if ($mode == "insert" || $id <= 0):
		$dbp = rex_sql::factory();
		$dbp->setQuery("SELECT id FROM ".rex::getTable('2034_gridtemplates'));
		$dbe[0]['prio'] = $dbp->getRows() + 1;
	endif;

	if ($form_error):
		//Formular bei Fehleingaben wieder befüllen
		$dbe[0]['id'] = $id;

		$dbe[0]["title"] = 			rex_post('f_title');
		$dbe[0]["description"] = 	rex_post('f_description');
		$dbe[0]["columns"] = 		rex_post('f_columns', 'int');
		$dbe[0]["template"] = 		rex_post('f_template');
		$dbe[0]["preview"] = 		rex_post('f_preview');
		$dbe[0]["status"] = 		rex_post('f_status');
		$dbe[0]["cssclass"] = 		rex_post('f_cssclass');

		$func = $mode;
	endif;


	//Beispiel-JSON holen
	$example = rex_file::get(rex_addon::get('gridblock2')->getPath('data/example.json'));


	//Ausgabe: Formular (Update / Insert)
?>

	<script id="json-example" type="text/template"><?php echo $example; ?></script>


	<form action="index.php?page=<?php echo $page; ?>" method="post" enctype="multipart/form-data">
		<!-- <input type="hidden" name="subpage" value="<?php echo $subpage; ?>" /> -->
		<input type="hidden" name="func" value="save" />
		<?php echo $gbCsrf->getHiddenField(); ?>
		<input type="hidden" name="id" value="<?php echo $dbe[0]['id']; ?>" />
		<input type="hidden" name="mode" value="<?php echo $func; ?>" />

		<section class="rex-page-section">
			<div class="panel panel-edit">

				<header class="panel-heading">
					<div class="panel-title"><?php echo $this->i18n('a2034_head_basics'); ?></div>
				</header>

				<div class="panel-body">

					<dl class="rex-form-group form-group">
						<dt><label for=""><?php echo $this->i18n('a2034_std_status'); ?></label></dt>
						<dd>
							<div class="checkbox toggle">
								<label for="f_status"> <input type="checkbox" name="f_status" id="f_status" value="checked" <?php echo $dbe[0]['status']; ?> class="" /> <?php echo $this->i18n('a2034_yes'); ?> </label>
							</div>
						</dd>
					</dl>


					<dl class="rex-form-group form-group"><dt></dt></dl>

					<legend><?php echo $this->i18n('a2034_subheader_bas1'); ?></legend>


					<dl class="rex-form-group form-group">
						<dt><label for=""><?php echo $this->i18n('a2034_bas_title'); ?> *</label></dt>
						<dd>
							<input type="text" size="25" name="f_title" id="f_title" value="<?php echo rex_escape($dbe[0]['title']); ?>" maxlength="100" class="form-control" />
						</dd>
					</dl>


					<dl class="rex-form-group form-group">
						<dt><label for=""><?php echo $this->i18n('a2034_bas_description'); ?></label></dt>
						<dd>
							<input type="text" size="25" name="f_description" id="f_description" value="<?php echo rex_escape($dbe[0]['description']); ?>" maxlength="200" class="form-control" />
						</dd>
					</dl>


					<dl class="rex-form-group form-group">
						<dt><label for=""><?php echo $this->i18n('a2034_bas_priority'); ?></label></dt>
						<dd>
							<input type="number" size="25" name="f_prio" id="f_prio" value="<?php echo intval($dbe[0]['prio']); ?>" maxlength="4" min="1" class="form-control" />
						</dd>
					</dl>


					<dl class="rex-form-group form-group"><dt></dt></dl>


					<legend><?php echo $this->i18n('a2034_subheader_bas2'); ?></legend>


					<dl class="rex-form-group form-group">
						<dt><label for=""><?php echo $this->i18n('a2034_bas_columns'); ?> *</label></dt>
						<dd>
							<input type="number" min="1" max="20" name="f_columns" id="f_columns" value="<?php echo (intval($dbe[0]['columns']) > 0) ? intval($dbe[0]['columns']) : ''; ?>" class="form-control" />
						</dd>
					</dl>


					<dl class="rex-form-group form-group">
						<dt><label for=""><?php echo $this->i18n('a2034_bas_cssclass'); ?></label></dt>
						<dd>
							<input type="text" size="25" name="f_cssclass" id="f_cssclass" value="<?php echo rex_escape($dbe[0]['cssclass']); ?>" maxlength="255" class="form-control" />
							<span class="infoblock"><?php echo $this->i18n('a2034_bas_cssclass_info'); ?></span>
						</dd>
					</dl>


					<dl class="rex-form-group form-group">
						<dt><label for=""><?php echo $this->i18n('a2034_bas_preview'); ?></label></dt>
						<dd>
							<textarea name="f_preview" rows="15" class="form-control rex-code" id="f_preview"><?php echo rex_escape($dbe[0]['preview']); ?></textarea>
							<span class="infoblock"><a href="javascript:$('#f_preview').val($('#json-example').html());"><?php echo $this->i18n('a2034_bas_preview_example'); ?></a></span>
						</dd>
					</dl>


					<?php if (!empty($dbe[0]['template']) && rex::getUser()->isAdmin()): ?>
					<dl class="rex-form-group form-group">
						<dt></dt>
						<dd>
							<details>
								<summary style="display: inline-block; cursor: pointer; color: #2A6EBB; text-decoration: underline; list-style: none;"><?php echo $this->i18n('a2034_bas_template'); ?></summary>
								<textarea name="f_template" rows="15" class="form-control rex-code" id="f_template"><?php echo rex_escape($dbe[0]['template']); ?></textarea>
								<span class="infoblock"><?php echo $this->i18n('a2034_bas_template_placeholder'); ?></span>
							</details>
						</dd>
					</dl>
					<?php endif; ?>

				</div>


				<footer class="panel-footer">
					<div class="rex-form-panel-footer">
						<div class="btn-toolbar">
							<input class="btn btn-save rex-form-aligned" type="submit" name="submit" title="<?php echo $this->i18n('a2034_save'); ?>" value="<?php echo $this->i18n('a2034_save'); ?>" />
							<?php if ($func == "update"): ?>
								<input class="btn btn-save" type="submit" name="submit-apply" title="<?php echo $this->i18n('a2034_apply'); ?>" value="<?php echo $this->i18n('a2034_apply'); ?>" />
							<?php endif; ?>
							<input class="btn btn-abort" type="submit" name="submit-abort" title="<?php echo $this->i18n('a2034_abort'); ?>" value="<?php echo $this->i18n('a2034_abort'); ?>" />
						</div>
					</div>
				</footer>

			</div>
		</section>

	</form>


<?php
else:
	//Übersichtsliste laden + ausgeben
	// --> wird per AJAX nachgeladen !!!

	$addpath = "index.php?page=" . $page;

	if (empty($this->getConfig('gb_builder_wizard_dismissed')) && rex::getUser()->isAdmin()):
		require rex_path::addon('gridblock2', 'pages/wizard.inc.php');
	endif;
?>


	<section class="rex-page-section">
		<div class="panel panel-default" id="gb-default-list" data-page="<?php echo rex_escape($page); ?>">

			<header class="panel-heading">
				<div class="panel-title"><?php echo $this->i18n('a2034_overview') . ' ' . $this->i18n('a2034_default'); ?></div>
			</header>

			<!-- Suchbox -->
			<table class="table table-striped addon_search" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td class="td1" valign="middle">
							<div class="btn-group btn-group-xs">
								<a href="<?php echo $addpath; ?>&func=insert" title="<?php echo $this->i18n('a2034_new'); ?>" class="btn btn-primary addRightMrgin"><i class="rex-icon rex-icon-add-template"></i> <?php echo $this->i18n('a2034_new_template'); ?></a>
							</div>								

							<?php if ((@$this->getConfig('config')['gb_builder_templates_import_export'] === 'checked')): ?>
								<div class="btn-group btn-group-xs">
									<a data-toggle="modal" data-target="#gb2ImportModal" class="btn btn-default"><i class="fa-solid fa-file-import"></i> <?php echo $this->i18n('a2034_bas_list_btn_importtemplate'); ?></a>

									<?php
									$db = rex_sql::factory();
									$db->setQuery("SELECT id FROM " . rex::getTable('2034_gridtemplates'));

									if ($db->getRows() > 0):
									?>
										<a href="index.php?page=<?php echo $page; ?>&amp;func=export_templates&amp;<?php echo $gbCsrfUrl; ?>" class="btn btn-default"><i class="fa-solid fa-file-export"></i> <?php echo $this->i18n('a2034_bas_list_btn_exporttemplate'); ?></a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</td>

						<td class="td2"><img src="/assets/addons/<?php echo $mypage; ?>/indicator.gif" width="16" height="16" border="0" id="ajax_loading" style="display:none;" /></td>
						<td class="td3">

							<div class="input-group sbeg">
								<input class="form-control" type="text" name="s_sbeg" id="s_sbeg" maxlength="50" value="<?php echo rex_escape($_SESSION['as_sbeg_gridtemplatelist']); ?>" placeholder="<?php echo $this->i18n('a2034_search_keyword'); ?>">
								<span class="input-group-btn">
									<a class="btn btn-popup form-control-btn" title="<?php echo $this->i18n('a2034_search_reset'); ?>" id="s_resetsbeg"><i class="rex-icon fa-close"></i></a>
								</span>
							</div>
							<input name="submit" type="button" value="<?php echo $this->i18n('a2034_search_submit'); ?>" class="button" id="s_button" style="display:none" />

						</td>
					</tr>
				</tbody>
			</table>


			<!-- Liste -->
			<table class="table table-striped table-hover">
				<thead>
					<tr>
						<th class="rex-table-icon"><a href="<?php echo $addpath; ?>&func=insert" accesskey="a" title="<?php echo $this->i18n('a2034_new'); ?> [a]"><i class="rex-icon rex-icon-add-template"></i></a></th>
						<th class="rex-table-id">ID</th>
						<th><?php echo $this->i18n('a2034_bas_list_name'); ?></th>
						<th><?php echo $this->i18n('a2034_bas_list_columns'); ?></th>
						<th><?php echo $this->i18n('a2034_bas_list_preview'); ?></th>
                        <th><?php echo $this->i18n('a2034_bas_list_inuse'); ?></th>
						<th><?php echo $this->i18n('a2034_bas_list_priority'); ?> <a class="db-order db-order-asc" id="db-order" data-order="asc"><i class="rex-icon fa-sort"></i></a></th>
						<th class="rex-table-action" colspan="3"><?php echo $this->i18n('a2034_statusfunc'); ?></th>
					</tr>
				</thead>

				<tbody id="ajax_jlist"></tbody>
			</table>


		</div>
	</section>

<?php
endif;
?>


<?php if (@$this->getConfig('config')['gb_builder_templates_import_export'] === 'checked'): ?>
<!-- Modalfenster Import Template-Archive -->
<div class="modal fade" id="gb2ImportModal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="<?php echo $this->i18n('a2034_close'); ?>"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo rex_i18n::msg('a2034_bas_list_btn_importtemplate'); ?></h4>
			</div>

			<form action="index.php?page=<?php echo $page; ?>&amp;func=import_templatearchive" method="post" enctype="multipart/form-data">
				<?php echo $gbCsrf->getHiddenField(); ?>
				<div class="modal-body">
					<?php echo rex_i18n::msg('a2034_bas_list_modal_text'); ?>

					<dl class="rex-form-group form-group">
						<dt></dt>
					</dl>
					<input type="file" name="importfile" class="form-control modalupload" />
					<p class="help-block small"><?php echo rex_i18n::msg('a2034_import_limits'); ?></p>
					<dl class="rex-form-group form-group">
						<dt></dt>
					</dl>

					<p class="faq" data-toggle="collapse" data-target="#gb2ImportInfo"><span class="caret"></span> <strong><?php echo rex_i18n::msg('a2034_import_info_head'); ?></strong></p>
					<div id="gb2ImportInfo" class="collapse">
						<?php echo rex_i18n::msg('a2034_import_info_zip'); ?><br>
						<?php echo rex_i18n::msg('a2034_import_info_intro'); ?>
						<br><br>

						<ul>
							<li><?php echo rex_i18n::msg('a2034_import_info_folder'); ?></li>
							<li><?php echo rex_i18n::msg('a2034_import_info_files'); ?></li>
							<ul>
								<li><?php echo rex_i18n::msg('a2034_import_info_definition'); ?></li>
								<li><?php echo rex_i18n::msg('a2034_import_info_template'); ?><br>&nbsp;</li>

								<li><?php echo rex_i18n::msg('a2034_import_info_optional'); ?> <br>
									<?php echo rex_i18n::msg('a2034_import_info_cs'); ?></li>
							</ul>
						</ul>

						<br>
						<a href="<?php echo rex_url::addonAssets($mypage, 'gridblock_import_example.zip'); ?>" target="_blank"><?php echo rex_i18n::msg('a2034_import_info_example'); ?></a>

						<br><br>
						<?php echo rex_i18n::rawMsg('a2034_import_info_note'); ?>
					</div>

				</div>
				<div class="modal-footer"><button type="submit" class="btn btn-save"><?php echo rex_i18n::msg('a2034_bas_list_modal_import'); ?></button></div>
			</form>
		</div>
	</div>
</div>
<?php endif; ?>
