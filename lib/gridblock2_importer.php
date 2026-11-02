<?php
/*
	Redaxo-Addon Gridblock²
	Importer-Funktionen für templates
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class rex_gridblock2_importer
{
	static $aTemplateFiles = array(
		"template.json",
		"definition.json"
	);

	private const IMPORT_MAX_ENTRIES = 200;
	private const IMPORT_MAX_BYTES = 20971520;
	private const IMPORT_KNOWN_FILES = array('template.json', 'definition.json', 'contentsettings.json', 'contentsettings.global.json');

	//Prueft das Archiv gegen die Grenzen und liefert die zu entpackenden Eintraege (null = Grenze verletzt oder ungueltiger Pfad)
	private static function selectImportEntries(ZipArchive $zip): ?array
	{
		if ($zip->numFiles > self::IMPORT_MAX_ENTRIES) {
			return null;
		}

		$aEntries = array();
		$iBytes = 0;
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$aStat = $zip->statIndex($i);
			if ($aStat === false) {
				return null;
			}

			$iBytes += (int) $aStat['size'];
			if ($iBytes > self::IMPORT_MAX_BYTES) {
				return null;
			}

			$sName = (string) $aStat['name'];
			$sNormal = str_replace('\\', '/', $sName);
			if (preg_match('#(^|/)\.\.(/|$)#', $sNormal) || $sNormal === '' || $sNormal[0] === '/' || preg_match('#^[A-Za-z]:#', $sNormal)) {
				return null;
			}

			if (substr($sNormal, -1) !== '/' && in_array(basename($sNormal), self::IMPORT_KNOWN_FILES, true)) {
				$aEntries[] = $sName;
			}
		}

		return $aEntries;
	}

	public static function import($aFile)
	{
		$oAddon = rex_addon::get('gridblock2');

		$sReturn = $oAddon->i18n('a2034_error_templates_notimported');

		if (empty($aFile) || !is_uploaded_file($aFile["tmp_name"])) {
			return $sReturn;
		}
		if ((int) ($aFile["size"] ?? 0) > self::IMPORT_MAX_BYTES) {
			return $oAddon->i18n('a2034_error_templates_import_limits');
		}

		$sFolderImport = "import/import_" . rand(1000, 100000) . time();
		$sFolderImportPath = $oAddon->getDataPath($sFolderImport);
		if (!is_dir($sFolderImportPath) && !mkdir($sFolderImportPath, 0775, true)) {
			return $sReturn;
		}

		try {
			if (!move_uploaded_file($aFile["tmp_name"], $sFolderImportPath . "/import.zip")) {
				return $sReturn;
			}

			$zip = new ZipArchive;
			if ($zip->open($sFolderImportPath . "/import.zip") !== true) {
				return $sReturn;
			}
			$aEntries = self::selectImportEntries($zip);
			if ($aEntries === null) {
				$zip->close();
				return $oAddon->i18n('a2034_error_templates_import_limits');
			}
			if ($aEntries !== array() && !$zip->extractTo($sFolderImportPath, $aEntries)) {
				$zip->close();
				return $sReturn;
			}
			$zip->close();
			unlink($sFolderImportPath . "/import.zip");

			$sRealImportPath = realpath($sFolderImportPath);
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sFolderImportPath, FilesystemIterator::SKIP_DOTS)) as $oFile) {
				$sRealFilePath = realpath($oFile->getPathname());
				if ($sRealFilePath === false || strpos($sRealFilePath, $sRealImportPath . DIRECTORY_SEPARATOR) !== 0) {
					return $sReturn;
				}
			}

			$aItems = scandir($sFolderImportPath);

			// check for zipped folder workaround
			$sCheckFolderName = pathinfo($aFile["name"])["filename"];
			foreach ($aItems as $sItem) {
				if ($sItem == $sCheckFolderName) {
					$sFolderImport = $sFolderImport . "/" . $sCheckFolderName;
					$aItems = scandir($oAddon->getDataPath($sFolderImport));
					break;
				}
			}

			foreach ($aItems as $sItem) {

				if ($sItem == "." || $sItem == "..") {
					continue;
				}

				// template folder
				if (is_dir($oAddon->getDataPath($sFolderImport) . "/" . $sItem)) {
					foreach (self::$aTemplateFiles as $sTemplateFile) {
						if (!file_exists($oAddon->getDataPath($sFolderImport) . "/" . $sItem . "/" . $sTemplateFile)) {
							return $oAddon->i18n('a2034_error_templates_notimported_file_missing');
						}
					}

					$aTemplate = json_decode(file_get_contents($oAddon->getDataPath($sFolderImport) . "/" . $sItem . "/template.json"), true);
					$aDefinition = json_decode(file_get_contents($oAddon->getDataPath($sFolderImport) . "/" . $sItem . "/definition.json"), true);

					if (!is_array($aTemplate) || !is_array($aDefinition)) {
						return $oAddon->i18n('a2034_error_templates_notimported_file_missing');
					}

					$sCssClass = (string) ($aDefinition["cssclass"] ?? '');
					unset($aDefinition["cssclass"]);
					$sDefinition = json_encode($aDefinition);

					$iPrio = 1;
					if (isset($aTemplate["prio"]) && intval($aTemplate["prio"]) > 0) {
						$iPrio = intval($aTemplate["prio"]);
						$oDb = rex_sql::factory();
						$oDb->setQuery("UPDATE " . rex::getTable('2034_gridtemplates') . " SET prio=prio+1 WHERE prio >= ?", [$iPrio]);
					} else {
						$oDb = rex_sql::factory();
						$oDb->setQuery("SELECT id FROM " . rex::getTable('2034_gridtemplates'));
						$iPrio = $oDb->getRows() + 1;
					}

					$oDb = rex_sql::factory();
					$oDb->setTable(rex::getTable('2034_gridtemplates'));
					$oDb->setValue("title", (string) ($aTemplate["title"] ?? ''));
					$oDb->setValue("description", (string) ($aTemplate["description"] ?? ''));
					$oDb->setValue("columns", intval($aDefinition["totalcolumns"] ?? 0));
					$oDb->setValue("prio", $iPrio);
					$oDb->setValue("template", '');
					$oDb->setValue("preview", $sDefinition);
					$oDb->setValue("cssclass", $sCssClass);
					$oDb->setValue("status", (($aTemplate["status"] ?? '') === 'checked') ? 'checked' : '');
					$oDb->addGlobalCreateFields();
					$oDb->insert();

					$iTemplateId = $oDb->getLastId();

					if (file_exists($oAddon->getDataPath($sFolderImport) . "/" . $sItem . "/contentsettings.json")) {
						$sTemplateSettingsDir = $oAddon->getDataPath("contentsettings/templates/template_" . $iTemplateId);
						if (!is_dir($sTemplateSettingsDir) && !mkdir($sTemplateSettingsDir, 0775, true)) {
							return $sReturn;
						}
						copy($oAddon->getDataPath($sFolderImport) . "/" . $sItem . "/contentsettings.json", $oAddon->getDataPath("contentsettings/templates/template_" . $iTemplateId . "/contentsettings.json"));
					}

				// project contentsettings.json
				} else {
					if ($sItem == "contentsettings.json") {
						if (!file_exists($oAddon->getDataPath("contentsettings/" . $sItem))) {
							if (!is_dir($oAddon->getDataPath("contentsettings"))) {
								mkdir($oAddon->getDataPath("contentsettings"), 0775, true);
							}
							copy($oAddon->getDataPath($sFolderImport) . "/" . $sItem, $oAddon->getDataPath("contentsettings/" . $sItem));
						} else {
							copy($oAddon->getDataPath($sFolderImport) . "/" . $sItem, $oAddon->getDataPath("contentsettings/contentsettings_imported_" . date("Y-m-d_H-i-s") . ".json"));
						}
					}

					if ($sItem == "contentsettings.global.json") {
						if (!file_exists($oAddon->getDataPath("contentsettings/" . $sItem))) {
							if (!is_dir($oAddon->getDataPath("contentsettings"))) {
								mkdir($oAddon->getDataPath("contentsettings"), 0775, true);
							}
							copy($oAddon->getDataPath($sFolderImport) . "/" . $sItem, $oAddon->getDataPath("contentsettings/" . $sItem));
						} else {
							copy($oAddon->getDataPath($sFolderImport) . "/" . $sItem, $oAddon->getDataPath("contentsettings/contentsettings.global_imported_" . date("Y-m-d_H-i-s") . ".json"));
						}
					}
				}
			}

			//alle Templates synchronisieren, falls der (optionale) Synchronizer in den Einstellungen aktiviert ist
			if (@$oAddon->getConfig('config')['gb_builder_synchronizer_enabled'] === 'checked') {
				Gridblock2Synchronizer::sync();
			}

			$sReturn = "success";
		} finally {
			self::rmDir($oAddon->getDataPath("import/"));
		}

		return $sReturn;
	}

	public static function export()
	{
		$oAddon = rex_addon::get('gridblock2');

		$sReturn = $oAddon->i18n('a2034_error_templates_notexported');

		$sFolderExport = $oAddon->getDataPath("export/export_" . rand(1000, 100000) . time());
		if (!is_dir($sFolderExport) && !mkdir($sFolderExport, 0775, true)) {
			return $sReturn;
		}

		try {
			$oDb = rex_sql::factory();
			$oDb->setQuery("SELECT * FROM " . rex::getTable('2034_gridtemplates') . " ORDER BY prio ASC");
			foreach ($oDb as $oRes) {
				mkdir($sFolderExport . "/template_" . $oRes->getValue("id"), 0775, true);

				if (file_exists($oAddon->getDataPath("contentsettings/templates/template_" . $oRes->getValue("id") . "/contentsettings.json"))) {
					copy($oAddon->getDataPath("contentsettings/templates/template_" . $oRes->getValue("id") . "/contentsettings.json"), $sFolderExport . "/template_" . $oRes->getValue("id") . "/contentsettings.json");
				}

				//cssclass ist eine eigene DB-Spalte, wird aber mit in definition.json aufgenommen, damit die Layout-CSS-Klasse beim Import wieder mit ausgewertet werden kann
				$aDefinition = json_decode((string) $oRes->getValue("preview"), true);
				$aDefinition = is_array($aDefinition) ? $aDefinition : [];
				$aDefinition["cssclass"] = (string) $oRes->getValue("cssclass");

				$sFileDefinition = $sFolderExport . "/template_" . $oRes->getValue("id") . "/definition.json";
				file_put_contents($sFileDefinition, json_encode($aDefinition));

				$sFileData = $sFolderExport . "/template_" . $oRes->getValue("id") . "/template.json";
				$aData = array(
					"title" => $oRes->getValue("title"),
					"prio" => $oRes->getValue("prio"),
					"description" => $oRes->getValue("description"),
					"status" => $oRes->getValue("status")
				);
				file_put_contents($sFileData, json_encode($aData));
			}

			if (file_exists($oAddon->getDataPath("contentsettings/contentsettings.json"))) {
				copy($oAddon->getDataPath("contentsettings/contentsettings.json"), $sFolderExport . "/contentsettings.json");
			}

			if (file_exists($oAddon->getDataPath("contentsettings/contentsettings.global.json"))) {
				copy($oAddon->getDataPath("contentsettings/contentsettings.global.json"), $sFolderExport . "/contentsettings.global.json");
			}

			$sZipFile = $oAddon->getDataPath("export/export_gridblock2.zip");

			$oZip = new ZipArchive;
			$oZip->open($sZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

			$aFiles = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($sFolderExport),
				RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach ($aFiles as $oFile) {
				if (!$oFile->isDir()) {
					$sFilePath = $oFile->getRealPath();
					$sRelativePath = substr($sFilePath, strlen($sFolderExport) + 1);
					$oZip->addFile($sFilePath, $sRelativePath);
				}
			}

			$oZip->close();

			ob_clean();
			header("Content-type: application/zip");
			header("Content-Disposition: attachment; filename=gridblock2_export.zip");
			header("Pragma: no-cache");
			header("Expires: 0");
			readfile($sZipFile);
			unlink($sZipFile);

			$sReturn = "success";
		} finally {
			self::rmDir($oAddon->getDataPath("export/"));
		}

		return $sReturn;
	}

	private static function rmDir($sDir)
	{
		if (is_dir($sDir)) {
			$objects = scandir($sDir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($sDir . DIRECTORY_SEPARATOR . $object) && !is_link($sDir . "/" . $object)) {
						self::rmDir($sDir . DIRECTORY_SEPARATOR . $object);
					} else {
						unlink($sDir . DIRECTORY_SEPARATOR . $object);
					}
				}
			}
			rmdir($sDir);
		}
	}
}
