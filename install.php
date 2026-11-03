<?php
/*
	Redaxo-Addon Gridblock²
	Installation
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** RexStan: Vars vom Check ausschließen */
/** @var rex_addon $this */


//Variablen deklarieren
$mypage = $this->getProperty('package');
$error = "";


//Vorgaben vornehmen
$config = $this->getConfig('config');
	$config = (!is_array($config)) ? [] : $config;
	
$configDefaults = [
		'modulesmode'				=> 'ignore',
		'modules'					=> '',
		'hidepreviewcoltitles'		=> '',
		'hideinfotexts'				=> '',
		'showcontentsettingsbe'		=> '',
		
		//Gridblock²
		'gb_builder_slicelist_preview'			=> '',
		'gb_builder_slicelist_fullscreen'		=> 'checked',
		'gb_builder_fullscreen_apply'			=> 'checked',
		'gb_builder_live_preview'				=> 'checked',
		'gb_builder_modalstyle'					=> 'sidebar',
		'gb_builder_modal_narrow'				=> 'checked',
		'gb_builder_slicelist_maxheight'		=> '',
		'gb_builder_preview_mode'				=> 'shadowdom',
		'gb_builder_element_preview_iframe'		=> '',
		'gb_builder_native_grid_css_frontend'	=> '',
		'gb_builder_preview_wrapper_class'	=> '',
		'gb_elements_css_frontend'				=> '',
		'gb_builder_preview_visibility_fixes'	=> "[data-aos] { opacity: 1 !important; transform: none !important; }",
		'gb_builder_palette'					=> '',
		'gb_builder_preview_mode_modules'		=> '',
		'gb_builder_preview_media_to_container'	=> 'checked',
		'gb_builder_preview_block_links'		=> 'checked',
		'gb_builder_compacttoolbox'				=> 'checked',
		'gb_builder_viewport_buttons'			=> 'checked',
		'gb_builder_draghandle'					=> 'checked',
		'gb_builder_columns_quickswitch'		=> 'checked',
		'gb_builder_nested_addbutton'			=> '',
		'gb_builder_between_addbutton'			=> '',
		'gb_builder_offlinetoggle'				=> 'checked',
		'gb_builder_copypaste'					=> 'checked',
		'gb_builder_hidelabel'					=> '',		
		'gb_builder_maxdepth'					=> '3',
		'gb_builder_modaledit'					=> 'checked',
		'gb_builder_modaledit_minwidth'			=> '560',
		'gb_builder_content_stylesheets'		=> '/assets/style.css',
		'gb_builder_content_scripts'			=> '',
		'gb_builder_module_css_overrides'		=> '',
		'gb_builder_gap_mode'					=> 'native',
		'gb_builder_gap_cs_field'				=> '',
		'gb_builder_gap_native_fallback'		=> "{\n  \"sm\": {\"label\": \"Klein\", \"value\": \"5px\"},\n  \"md\": {\"label\": \"Mittel\", \"value\": \"25px\"},\n  \"lg\": {\"label\": \"Groß\", \"value\": \"50px\"},\n  \"xl\": {\"label\": \"Sehr groß\", \"value\": \"100px\"}\n}",
		'gb_builder_native_grid_css_backend'	=> 'checked',
		'gb_builder_templates_import_export'	=> '',
		'gb_builder_synchronizer_enabled'		=> '',
		'gb_builder_multisite'					=> '',
		'gb_builder_domains'					=> '',
		'gb_builder_multisite_elements'			=> '',
];

$this->setConfig('config', array_merge($configDefaults, $config));

//generierte native Grid-CSS-Datei direkt bei Installation einmal anlegen, statt erst auf das erste Speichern der Einstellungen zu warten
Gridblock2Builder_Tree::writeNativeGridCssFile();
Gridblock2Builder_Tree::writeElementsCssFile();



//Datenbank-Einträge vornehmen
rex_sql_table::get(rex::getTable('2034_gridtemplates'))
	->ensureColumn(new rex_sql_column('id', 'int(100)', false, null, 'auto_increment'))
	->ensureColumn(new rex_sql_column('prio', 'int(11)'))
	->ensureColumn(new rex_sql_column('cat', 'int(100)'))
	->ensureColumn(new rex_sql_column('title', 'varchar(255)'))
	->ensureColumn(new rex_sql_column('description', 'text'))
	->ensureColumn(new rex_sql_column('columns', 'int(2)'))
	->ensureColumn(new rex_sql_column('template', 'text'))
	->ensureColumn(new rex_sql_column('preview', 'text'))
	->ensureColumn(new rex_sql_column('status', 'varchar(10)'))
	->ensureColumn(new rex_sql_column('cssclass', 'varchar(255)'))
	->ensureGlobalColumns()
	->setPrimaryKey('id')
	->ensure();



//Module anlegen
$db = rex_sql::factory();
$db->setQuery('SELECT id FROM '.rex::getTable('module').' WHERE input LIKE "%/* GRID_MODULE_IDENTIFIER | DONT REMOVE */%"');

if (!$db->hasNext()):
	$db2 = rex_sql::factory();
	$db2->setTable(rex::getTable('module'));
	$db2->setValue('input', rex_file::get($this->getPath('install/input.php')));
	$db2->setValue('output', rex_file::get($this->getPath('install/output.php')));
	$db2->addGlobalCreateFields();
	$db2->setValue('name', '01 - Gridblock (Pagebuilder & Blöcke gruppieren)');
	$db2->insert();
endif;



//Data-Ordner kopieren
if (empty($this->getConfig('gb_builder_install'))) {
    $dataSrc = $this->getPath('data');

    if (!is_dir($dataSrc) || !rex_dir::copy($dataSrc, $this->getDataPath())) {
        throw new rex_functional_exception('Gridblock: Der Daten-Ordner "'.$dataSrc.'" fehlt oder konnte nicht kopiert werden. Bitte prüfen, ob der Ordner "data/" beim Hochladen/Deployment des Addons vollständig mit übertragen wurde.');
    }

    //Vorgaben aus install/settings.json nur bei der Erstinstallation uebernehmen (bei einer Neuinstallation ueber vorhandene Einstellungen nicht)
    $settingsFile = Gridblock2Builder_SettingsFile::load($this->getPath(Gridblock2Builder_SettingsFile::FILE), array_keys($configDefaults));
    if ($settingsFile['values'] !== []) {
        $this->setConfig('config', array_merge((array) $this->getConfig('config'), $settingsFile['values']));
        $this->setConfig('gb_builder_wizard_dismissed', time());

        Gridblock2Builder_Tree::writeNativeGridCssFile();
        Gridblock2Builder_Tree::writeElementsCssFile();
    }
    foreach ($settingsFile['notes'] as $settingsNote) {
        rex_logger::factory()->log('warning', 'Gridblock²: '.$settingsNote);
    }

    $this->setConfig('gb_builder_install', time());
}
?>