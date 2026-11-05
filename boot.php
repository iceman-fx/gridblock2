<?php
/*
	Redaxo-Addon Gridblock²
	Boot (weitere Konfigurationen & Einbindung)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** RexStan: Vars vom Check ausschließen */
/** @var rex_addon $this */


//Variablen deklarieren
$mypage = $this->getProperty('package');


//Userrechte prüfen
$isAdmin = ( is_object(rex::getUser()) AND (rex::getUser()->hasPerm($mypage.'[admin]') OR rex::getUser()->isAdmin()) ) ? true : false;


if (rex::isBackend()):
	rex_perm::register('gridblock2[favorites]', rex_i18n::msg('a2034_perm_favorites'), rex_perm::OPTIONS);
endif;


//Addon Einstellungen
$config = rex_addon::get($mypage)->getConfig('config');			//Addon-Konfig einladen


//Funktionen einladen/definieren
//Global für Backend+Frontend
global $a2034_mypage;
$a2034_mypage = $mypage;

global $a2034_darkmode;
$a2034_darkmode = (rex_version::compare(rex::getVersion(), '5.13.0-dev', '>=')) ? true : false;


require_once(rex_path::addon($mypage, "lib/Gridblock2Builder_ContentSettings.php"));
require_once(rex_path::addon($mypage, "lib/Gridblock2Builder_MultiSite.php"));
require_once(rex_path::addon($mypage, "lib/Gridblock2Builder_Availability.php"));

if (!class_exists('rex_gridblock_helper')):
	class_alias('rex_gridblock2_helper', 'rex_gridblock_helper');
endif;

if (!class_exists('rex_gridblock')):
	class_alias('rex_gridblock2', 'rex_gridblock');
endif;
if (!class_exists('rex_gridblock_var_replacer')):
	class_alias('rex_gridblock2_var_replacer', 'rex_gridblock_var_replacer');
endif;
if (!class_exists('rex_sql_gridblock')):
	class_alias('rex_sql_gridblock2', 'rex_sql_gridblock');
endif;
if (!class_exists('GridblockContentSettings')):
	class_alias('Gridblock2Builder_ContentSettings', 'GridblockContentSettings');
endif;


//Rechte fuer Custom-/System-Elemente mit "permission: true" in der element.yml (Rollenformular, direkt unter "Module")
if (rex::isBackend()):
	require_once(rex_path::addon($mypage, "lib/gridblock2_elements_perm.php"));
	rex_complex_perm::register(Gridblock2Builder_Tree::ELEMENT_PERM_KEY, rex_gridblock2_elements_perm::class);
	if (rex_be_controller::getCurrentPage() === 'users/roles'):
		rex_extension::register('PACKAGES_INCLUDED', static function () {
			rex_gridblock2_elements_perm::moveBelowModules();
		});
	endif;
endif;


//Backend
if (rex::isBackend()):
	if (rex_be_controller::getCurrentPage() === 'content/edit'):
		rex_sql::setFactoryClass('rex_sql_gridblock2');

		$isGridblockModuleSave = false;
		if (isset($_POST['REX_INPUT_VALUE'][15]) || isset($_POST['REX_INPUT_VALUE'][16])):
			$gbModuleId = ('add' === rex_request('function', 'string', '')) ? rex_post('module_id', 'int', 0) : 0;
			if ($gbModuleId <= 0):
				$gbSliceId = rex_request('slice_id', 'int', 0);
				if ($gbSliceId > 0):
					$gbSliceSql = rex_sql::factory();
					$gbSliceSql->setQuery('SELECT module_id FROM '.rex::getTable('article_slice').' WHERE id = ?', [$gbSliceId]);
					$gbModuleId = ($gbSliceSql->getRows() > 0) ? (int) $gbSliceSql->getValue('module_id') : 0;
				endif;
			endif;

			if ($gbModuleId > 0):
				$gbModCheck = rex_sql::factory();
				$gbModCheck->setQuery('SELECT 1 FROM '.rex::getTablePrefix().'module WHERE id = ? AND input LIKE ?', [$gbModuleId, '%GRID_MODULE_IDENTIFIER%']);
				$isGridblockModuleSave = $gbModCheck->getRows() > 0;
			endif;
		endif;

		$values = isset($_POST['REX_INPUT_VALUE']) ? $_POST['REX_INPUT_VALUE'] : null;

		if (is_array($values) && $isGridblockModuleSave):
			foreach ($values as $colID => $slices):
			
				if (is_array($slices)):
					//erste Ebene (columnID) durchlaufen	
					foreach ($slices as $uID => $data):
						//zweite Ebene (uniqeID = Slices) durchlaufen
						if (isset($data['VALUE'])):
						
							foreach ($data['VALUE'] as $i => $value):
								//Eingabedaten durchlaufen und _MBLOCK korrigieren	
								if (strrpos($i, '_MBLOCK') !== false):
									$id = str_replace('_MBLOCK', '', $i);									
									
									$values[$colID][$uID]['VALUE'][$id] = (isset($values[$colID][$uID]['VALUE'][$id]) && !is_array($value)) ? $values[$colID][$uID]['VALUE'][$id] + $value : $value;		//$id + $val nur durchführen, wenn kein array
									unset($values[$colID][$uID]['VALUE'][$i]);
								endif;
							endforeach;
																					
						endif;
					endforeach;
				endif;
				
			endforeach;
			
			//korrigierte Vars zurückgeben
			$_POST['REX_INPUT_VALUE'] = $values;
			$_REQUEST['REX_INPUT_VALUE'] = $values;

		endif;


		if ($isGridblockModuleSave):
			$treeValues = isset($_POST['REX_INPUT_VALUE'][15]) ? $_POST['REX_INPUT_VALUE'][15] : null;
			if (is_array($treeValues)):
				foreach ($treeValues as $uID => $data):
					if (isset($data['VALUE']) && is_array($data['VALUE'])):
						foreach ($data['VALUE'] as $i => $value):
							if (strrpos((string) $i, '_MBLOCK') !== false):
								$id = str_replace('_MBLOCK', '', (string) $i);
								$treeValues[$uID]['VALUE'][$id] = (isset($treeValues[$uID]['VALUE'][$id]) && !is_array($value)) ? $treeValues[$uID]['VALUE'][$id] + $value : $value;
								unset($treeValues[$uID]['VALUE'][$i]);
							endif;
						endforeach;
					endif;
				endforeach;

				$_POST['REX_INPUT_VALUE'][15] = $treeValues;
				$_REQUEST['REX_INPUT_VALUE'][15] = $treeValues;
			endif;


			$gbSliceIdTree = isset($gbSliceId) ? (int) $gbSliceId : 0;
			rex_extension::register('PACKAGES_INCLUDED', static function () use ($gbSliceIdTree) {
			$treeStructureRaw = isset($_POST['REX_INPUT_VALUE'][16]) ? $_POST['REX_INPUT_VALUE'][16] : null;
			if (is_string($treeStructureRaw) && $treeStructureRaw !== ''):
				$treeStructure = json_decode($treeStructureRaw, true);
				if (is_array($treeStructure)):
					$gbPreviousModules = [];
					$gbPreviousUids = [];
					if ($gbSliceIdTree > 0):
						$gbPrevSql = rex_sql::factory();
						$gbPrevSql->setQuery('SELECT value16 FROM '.rex::getTable('article_slice').' WHERE id = ?', [$gbSliceIdTree]);
						if ($gbPrevSql->getRows() > 0):
							$gbPrevTreeRaw = (string) $gbPrevSql->getValue('value16');
							$gbPrevTree = ($gbPrevTreeRaw !== '') ? json_decode($gbPrevTreeRaw, true) : null;
							if (is_array($gbPrevTree)):
								Gridblock2Builder_Tree::collectModuleUidMap($gbPrevTree, $gbPreviousModules);
								Gridblock2Builder_Tree::collectAllUids($gbPrevTree, $gbPreviousUids);
							endif;
						endif;
					endif;

					Gridblock2Builder_Tree::validateTreeSchema($treeStructure, $gbPreviousUids);
					Gridblock2Builder_Tree::validateTreeSecurity($treeStructure, $gbPreviousModules);

					$gbPreviousKeys = [];
					if ($gbSliceIdTree > 0):
						$gbPrevRow = rex_sql::factory();
						$gbPrevRow->setQuery('SELECT * FROM '.rex::getTable('article_slice').' WHERE id = ?', [$gbSliceIdTree]);
						if ($gbPrevRow->getRows() > 0):
							$gbPrevValues = [];
							for ($gbValueNo = 1; $gbValueNo <= 20; ++$gbValueNo):
								$gbPrevValues[$gbValueNo] = $gbPrevRow->getValue('value'.$gbValueNo);
							endfor;
							Gridblock2Builder_Tree::collectAvailabilityKeyMap(Gridblock2Builder_Tree::loadStructure($gbPrevValues), $gbPreviousKeys);
						endif;
					endif;
					Gridblock2Builder_Tree::validateTreeAvailability($treeStructure, $gbPreviousKeys, rex_request('article_id', 'int', 0), rex_request('clang', 'int', 0) ?: rex_clang::getCurrentId(), max(1, rex_request('ctype', 'int', 1)));

					$settingsPost = (isset($_POST['REX_INPUT_VALUE'][14]) && is_array($_POST['REX_INPUT_VALUE'][14])) ? $_POST['REX_INPUT_VALUE'][14] : [];
					Gridblock2Builder_Tree::injectSettingsFromPost($treeStructure, $settingsPost);

					$_POST['REX_INPUT_VALUE'][16] = json_encode($treeStructure);
					$_REQUEST['REX_INPUT_VALUE'][16] = $_POST['REX_INPUT_VALUE'][16];
				endif;
			endif;
			});
		endif;

	endif;
	
	if (rex::getUser()):
		//AJAX anbinden
		$ajaxPages = array('load-defaultlist');
			if (rex_be_controller::getCurrentPagePart(1) == $mypage && in_array(rex_request('subpage', 'string'), $ajaxPages)):
				rex_extension::register('OUTPUT_FILTER', [rex_gridblock2_helper::class, 'bindAjax']);
			endif;
	
	endif;
endif;


if (rex_backend_login::hasSession() && rex::getUser() && rex::getUser()->isAdmin() && (@$config['gb_builder_synchronizer_enabled'] === 'checked')):
	Gridblock2Synchronizer::sync();
endif;


//Eigenes Hauptmenü-Icon - unconditional geladen, da die Hauptnavigation auf jeder Backend-Seite gerendert wird, nicht nur auf den eigenen Addon-Seiten.
if (rex::isBackend()):
	rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('css/gridblock-brand.min.css')));
endif;
if (rex_be_controller::getCurrentPagePart(1) === $mypage):
	rex_extension::register('PAGE_TITLE', static function (rex_extension_point $ep) {
		return '<i class="gridblock-icon-logo"></i> ' . $ep->getSubject();
	}, rex_extension::EARLY);
endif;


//GB2-Kontext: Slice-Bearbeitung (content/edit) und die eigenen Addon-Seiten.
$gbContext = rex::isBackend() && rex::getUser() && (rex_be_controller::getCurrentPage() === 'content/edit' || rex_be_controller::getCurrentPagePart(1) === $mypage);

if ($gbContext):
rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('style.min.css')));
if ($a2034_darkmode) { rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('style-darkmode.min.css'))); }

if (rex_be_controller::getCurrentPage() === $mypage.'/default'):
	rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('gridblock_default.min.js')));
endif;
if (rex_be_controller::getCurrentPage() === $mypage.'/config'):
	rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('gridblock_config.min.js')));
endif;


//GB2 Builder-Engine
rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('css/gb-builder.min.css')));
rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('js/gridblock_builder.min.js')));
if (rex_be_controller::getCurrentPage() === 'content/edit'):
	rex_extension::register('SLICE_BE_PREVIEW', [rex_gridblock2_helper::class, 'injectSliceFullscreenButton']);
endif;

if (!Gridblock2Builder_Tree::isFmHelperToolActive('simplecolorpicker')):
	rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/simpleColorPicker/simple-colorpicker.min.css')));
	rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/simpleColorPicker/simple-colorpicker.min.js')));
endif;
if (!Gridblock2Builder_Tree::isFmHelperToolActive('multideviceinput')):
	rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/multiDeviceInput/multidevice-input.min.css')));
	rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/multiDeviceInput/multidevice-input.min.js')));
endif;
if (!Gridblock2Builder_Tree::isFmHelperToolActive('multideviceselect')):
	rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/multiDeviceSelect/multidevice-select.min.css')));
	rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/multiDeviceSelect/multidevice-select.min.js')));
endif;
if (!Gridblock2Builder_Tree::isFmHelperToolActive('datetimepicker')):
	rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/datetimePicker/accessible-datepicker.min.css')));
	rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/datetimePicker/accessible-datepicker.min.js')));
endif;

if (!Gridblock2Builder_Tree::isFmHelperToolActive('htmlrepeater')):
	rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/repeater/repeater.min.css')));
	rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/repeater/sortable.min.js')));
	rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/repeater/repeater.min.js')));
endif;
if (!Gridblock2Builder_Tree::isFmHelperToolActive('multilink')):
	rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/multiLink/multilink-input.min.css')));
	rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('vendor/fm_helper/multiLink/multilink-input.min.js')));
endif;


rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('contentsettings/bootstrap-slider.min.css')));
rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('contentsettings/style.min.css')));
if ($a2034_darkmode) { rex_view::addCssFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('contentsettings/style-darkmode.min.css'))); }
rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('contentsettings/popper.min.js')));
rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('contentsettings/tippy-bundle.umd.min.js')));
rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('contentsettings/bootstrap-slider.min.js')));
rex_view::addJsFile(rex_gridblock2_helper::getTimebuster($this->getAssetsUrl('contentsettings/script.min.js')));
endif;

if (rex::isFrontend() && (@$config['gb_builder_native_grid_css_frontend'] === 'checked')):
	rex_extension::register('OUTPUT_FILTER', static function (rex_extension_point $ep) {
		$subject = $ep->getSubject();
		if (stripos($subject, '</head>') === false):
			return $subject;
		endif;

		if (Gridblock2Builder_Tree::nativeGridCssIsOutdated()):
			Gridblock2Builder_Tree::writeNativeGridCssFile();
		endif;

		$href = rex_gridblock2_helper::getTimebuster(Gridblock2Builder_Tree::getNativeGridCssUrl());
		$link = '<link rel="stylesheet" href="'.rex_escape($href).'">';
		return str_ireplace('</head>', $link.'</head>', $subject);
	});
endif;

if (rex::isFrontend() && (@$config['gb_elements_css_frontend'] === 'checked')):
	rex_extension::register('OUTPUT_FILTER', static function (rex_extension_point $ep) {
		$subject = $ep->getSubject();
		if (stripos($subject, '</head>') === false):
			return $subject;
		endif;

		$link = '<link rel="stylesheet" href="'.rex_escape(Gridblock2Builder_Tree::getElementsCssUrl()).'">';
		return str_ireplace('</head>', $link.'</head>', $subject);
	});
endif;
?>