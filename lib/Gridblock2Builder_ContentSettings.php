<?php
/*
	Redaxo-Addon Gridblock²
	Rückwärtskompatibilität ContentSettings
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class Gridblock2Builder_ContentSettings
{
    public $addon;
    public $fileGlobal;
    public $fileProject;
    public $iSettingsId;
    public $iTemplateId;
    public $iColumnId;
    public $sInstanceUid = '';
    public $modulesContext = null;
    public $aSettings;
    public $globalData;
    public $projectData;
    public $templateData;
    public $templateGeneralData;


    public function __construct($iModuleId = null)
    {
        $this->addon = rex_addon::get('gridblock2');
        $this->migrateLegacyDataIfNeeded();

        $this->fileGlobal = $this->addon->getDataPath('contentsettings/contentsettings.global.json');
        $this->fileProject = $this->addon->getDataPath('contentsettings/contentsettings.json');

        $this->iSettingsId = "20";
        $this->iTemplateId = "0";
        $this->iColumnId = "0";
        $this->getAllSettings();
    }

    private static bool $legacyDataSyncedThisRequest = false;

    private function migrateLegacyDataIfNeeded()
    {
        if (self::$legacyDataSyncedThisRequest) {
            return;
        }
        self::$legacyDataSyncedThisRequest = true;

        $oldAddon = rex_addon::get('gridblock');
        if (!$oldAddon || !$oldAddon->isAvailable()) {
            return;
        }

        $sOldPath = rex_path::pluginData('gridblock', 'contentsettings');
        if (!is_dir($sOldPath)) {
            return;
        }

        $sNewPath = $this->addon->getDataPath('contentsettings');
        rex_dir::create($sNewPath);
        rex_dir::copy($sOldPath, $sNewPath);

        rex_file::put($this->addon->getDataPath('migration-info.txt'),
            "Hinweis (wird bei jedem Seitenaufruf automatisch aktualisiert)\n" .
            "----------------------------------------------------------------------------------\n\n" .
            "Solange die alte Gridblock-1.x-Installation (Addon \"gridblock\") noch installiert und " .
            "aktiv ist, wird der Ordner \"contentsettings/\" bei jedem Seitenaufruf automatisch aus " .
            "deren echten Contentsettings-Plugin-Daten aktualisiert (nur gleichnamige Dateien werden " .
            "ueberschrieben, nichts wird geloescht).\n\n" .
            "Sobald Gridblock 1.x deaktiviert wird, endet diese automatische Uebernahme - ab dann " .
            "werden hier ausschliesslich die eigenen, eigenstaendig gepflegten Gridblock-2.0-Daten " .
            "verwendet.\n\n" .
            "Letzter automatischer Abgleich: " . date('Y-m-d H:i:s'));
    }

    function getAllSettings($sType = "template")
    {
        $this->aSettings = [];
        $bModulesScope = ($sType === 'modules');

        if (file_exists($this->fileGlobal)) {
            $this->globalData = self::readJsonData($this->fileGlobal);
        }


        if (file_exists($this->fileProject)) {
            $this->projectData = self::readJsonData($this->fileProject);
        }

        if ($this->iTemplateId) {
            if (!$this->iColumnId) {
                if (file_exists($this->addon->getDataPath("contentsettings/templates/template_" . $this->iTemplateId . "/contentsettings.json"))) {
                    $sTemplate = $this->getJsonContent($this->addon->getDataPath("contentsettings/templates/template_" . $this->iTemplateId . "/contentsettings.json"));
                    $this->templateData = json_decode($sTemplate, true)["template"];
                }
            } else {
                if (file_exists($this->addon->getDataPath("contentsettings/templates/template_" . $this->iTemplateId . "/contentsettings.json"))) {
                    $sTemplate = $this->getJsonContent($this->addon->getDataPath("contentsettings/templates/template_" . $this->iTemplateId . "/contentsettings.json"));
                    $this->templateData = json_decode($sTemplate, true)["columns"][$this->iColumnId];
                }
            }
            if (file_exists($this->addon->getDataPath("contentsettings/templates/template_" . $this->iTemplateId . "/contentsettings.json"))) {
                $sTemplate = $this->getJsonContent($this->addon->getDataPath("contentsettings/templates/template_" . $this->iTemplateId . "/contentsettings.json"));
                $this->templateGeneralData = json_decode($sTemplate, true);
            }
        }


        // ShowOptions


        if (!$bModulesScope && isset($this->globalData["showOptions"])) {
            if (count($this->globalData["showOptions"])) {
                $this->aSettings["showOptions"] = $this->globalData["showOptions"];
            }
        }

        if (isset($this->globalData[$sType]["showOptions"])) {
            if (count($this->globalData[$sType]["showOptions"])) {
                $this->aSettings["showOptions"] = $this->globalData[$sType]["showOptions"];
            }
        }


        if (!$bModulesScope && isset($this->projectData["showOptions"])) {
            if (count($this->projectData["showOptions"])) {
                $this->aSettings["showOptions"] = $this->projectData["showOptions"];
            }
        }

        if (isset($this->projectData[$sType]["showOptions"])) {
            if (count($this->projectData[$sType]["showOptions"])) {
                $this->aSettings["showOptions"] = $this->projectData[$sType]["showOptions"];
            }
        }

        if (isset($this->templateData["showOptions"])) {
            if (count($this->templateData["showOptions"])) {
                $this->aSettings["showOptions"] = $this->templateData["showOptions"];
            }
        }

        $this->aSettings["categories"] = array();

        if (isset($this->globalData["categories"])) {
            if (count($this->globalData["categories"])) {
                foreach ($this->globalData["categories"] as $aCategory) {
                    $sKey = $aCategory["key"];
                    $sIcon = isset($aCategory["icon"]) ? $aCategory["icon"] : "";
                    $this->aSettings["categories"][$sKey] = array("label" => $aCategory["label"], "icon" => $sIcon);
                }
            }
        }

        if (isset($this->projectData["categories"])) {
            if (count($this->projectData["categories"])) {
                foreach ($this->projectData["categories"] as $aCategory) {
                    $sKey = $aCategory["key"];
                    $sIcon = isset($aCategory["icon"]) ? $aCategory["icon"] : "";
                    $this->aSettings["categories"][$sKey] = array("label" => $aCategory["label"], "icon" => $sIcon);
                }
            }
        }

        if (isset($this->templateData["categories"])) {
            if (count($this->templateData["categories"])) {
                foreach ($this->templateData["categories"] as $aCategory) {
                    $sKey = $aCategory["key"];
                    $sIcon = isset($aCategory["icon"]) ? $aCategory["icon"] : "";
                    $this->aSettings["categories"][$sKey] = array("label" => $aCategory["label"], "icon" => $sIcon);
                }
            }
        }

        // Hide Options
        if (isset($this->templateData["hideOptions"])) {
            foreach ($this->templateData["hideOptions"] as $sKey) {
                if (($iX = array_search($sKey, $this->aSettings["showOptions"])) !== false) {
                    unset($this->aSettings["showOptions"][$iX]);
                }
            }
        }


        // Options
        $aUsedKeys = [];


        if (isset($this->globalData["options"])) {
            if (count($this->globalData["options"])) {
                foreach ($this->globalData["options"] as $aOption) {
                    $sKey = $aOption["key"];
                    if (!in_array($sKey, $aUsedKeys)) {
                        $this->aSettings["options"][$sKey] = $aOption;
                        array_push($aUsedKeys, $sKey);
                    } else {
                        foreach ($aOption as $sOptionKey => $mOptionVal) {
                            if (isset($mOptionVal)) {
                                $this->aSettings["options"][$sKey][$sOptionKey] = $mOptionVal;
                            }
                        }
                    }
                }
            }
        }

        if (isset($this->globalData[$sType]["options"])) {
            if (count($this->globalData[$sType]["options"])) {
                foreach ($this->globalData[$sType]["options"] as $aOption) {
                    $sKey = $aOption["key"];
                    if (!in_array($sKey, $aUsedKeys)) {
                        $this->aSettings["options"][$sKey] = $aOption;
                        array_push($aUsedKeys, $sKey);
                    } else {
                        foreach ($aOption as $sOptionKey => $mOptionVal) {
                            if (isset($mOptionVal)) {
                                $this->aSettings["options"][$sKey][$sOptionKey] = $mOptionVal;
                            }
                        }
                    }
                }
            }
        }



        if (isset($this->projectData["options"])) {
            if (count($this->projectData["options"])) {
                foreach ($this->projectData["options"] as $aOption) {
                    $sKey = $aOption["key"];
                    if (!in_array($sKey, $aUsedKeys)) {
                        $this->aSettings["options"][$sKey] = $aOption;
                        array_push($aUsedKeys, $sKey);
                    } else {
                        foreach ($aOption as $sOptionKey => $mOptionVal) {
                            if (isset($mOptionVal)) {
                                $this->aSettings["options"][$sKey][$sOptionKey] = $mOptionVal;
                            }
                        }
                    }
                }
            }
        }

        if (isset($this->projectData[$sType]["options"])) {
            if (count($this->projectData[$sType]["options"])) {
                foreach ($this->projectData[$sType]["options"] as $aOption) {
                    $sKey = $aOption["key"];
                    if (!in_array($sKey, $aUsedKeys)) {
                        $this->aSettings["options"][$sKey] = $aOption;
                        array_push($aUsedKeys, $sKey);
                    } else {
                        foreach ($aOption as $sOptionKey => $mOptionVal) {
                            if (isset($mOptionVal)) {
                                $this->aSettings["options"][$sKey][$sOptionKey] = $mOptionVal;
                            }
                        }
                    }
                }
            }
        }

        if (isset($this->templateGeneralData["options"])) {
            if (count($this->templateGeneralData["options"])) {
                foreach ($this->templateGeneralData["options"] as $aOption) {
                    $sKey = $aOption["key"];
                    if (!in_array($sKey, $aUsedKeys)) {
                        $this->aSettings["options"][$sKey] = $aOption;
                        array_push($aUsedKeys, $sKey);
                    } else {
                        foreach ($aOption as $sOptionKey => $mOptionVal) {
                            if (isset($mOptionVal)) {
                                $this->aSettings["options"][$sKey][$sOptionKey] = $mOptionVal;
                            }
                        }
                    }
                }
            }
        }

        if (isset($this->templateData["options"])) {
            if (count($this->templateData["options"])) {
                foreach ($this->templateData["options"] as $aOption) {
                    $sKey = $aOption["key"];
                    if (!in_array($sKey, $aUsedKeys)) {
                        $this->aSettings["options"][$sKey] = $aOption;
                        array_push($aUsedKeys, $sKey);
                    } else {
                        foreach ($aOption as $sOptionKey => $mOptionVal) {
                            if (isset($mOptionVal)) {
                                $this->aSettings["options"][$sKey][$sOptionKey] = $mOptionVal;
                            }
                        }
                    }
                }
            }
        }


        // parents
		if (isset($this->aSettings["options"])) {
			foreach ($this->aSettings["options"] as $sKey => $aOptions) {
				if (isset($aOptions["parent"])) {
					if (isset($this->aSettings["options"][$sKey])) {
						$sParent = $aOptions["parent"];
						foreach ($this->aSettings["options"][$sParent] as $sKeyParent => $sValueParent) {
							if (!isset($aOptions[$sKeyParent])) {
								$this->aSettings["options"][$sKey][$sKeyParent] = $sValueParent;
							}
						}
					}
				}
			}
		}


        if ($bModulesScope) {
            $this->applyModulesRestrict();
        }

        // start_group & end_group
        if (isset($this->aSettings["showOptions"])) {
            $iPos = "1";

            foreach ($this->aSettings["showOptions"] as $sOption) {
                $aOption = $this->aSettings["options"][$sOption];
                if ($aOption["type"] == "group") {
                    $aNewShowOptions = array();
                    $sLabel = $this->aSettings["options"][$sOption]["label"];
                    $this->aSettings["options"][$sOption]["type"] = "html";

                    $sAccordionOpen = $sAriaExpanded = "";
                    if (isset($this->aSettings["options"][$sOption]["open"])) {
                        if ($this->aSettings["options"][$sOption]["open"] == true) {
                            $sAccordionOpen = "in";
                            $sAriaExpanded = 'aria-expanded="true"';
                        }
                    }

                    $sHtml = '<div class="gridblockcontentsettings-group" id="gridblockcontentsettings-accordion_' . $this->sInstanceUid . '_' . $sType . '_' . $this->iColumnId . '_' . $aOption["key"] . '"><div class="gridblockcontentsettings-heading"><a data-toggle="collapse" data-parent="#gridblockcontentsettings-accordion_' . $this->sInstanceUid . '_' . $sType . '_' . $this->iColumnId . '_' . $aOption["key"] . '" href="#gridblockcontentsettings-accordion_collapes_' . $this->sInstanceUid . '_' . $sType . '_' . $this->iColumnId . '_' . $aOption["key"] . '" ' . $sAriaExpanded . '>';
                    if (isset($this->aSettings["options"][$sOption]["icon"]) && $this->aSettings["options"][$sOption]["icon"] != "") {
                        $sTippy = "";
                        if (isset($this->aSettings["options"][$sOption]["icon_tooltip"])) {
                            $sTippy = ' data-tippy-content="' . $this->aSettings["options"][$sOption]["icon_tooltip"] . '"';
                        }
                        $sHtml .= ' <i class="' . $this->aSettings["options"][$sOption]["icon"] . '" style="padding-right:10px" ' . $sTippy . '></i>';
                    }
                    $sHtml .= $sLabel . '</a></div><div id="gridblockcontentsettings-accordion_collapes_' . $this->sInstanceUid . '_' . $sType . '_' . $this->iColumnId . '_' . $aOption["key"] . '" class="gridblockcontentsettings-body collapse ' . $sAccordionOpen . '"><div class="gbpanel-body">';

                    $this->aSettings["options"][$sOption]["text"] = $sHtml;
                    $this->aSettings["options"][$sOption]["label"] = "";

                    $sCategory = "";
                    if (isset($aOption["category"])) {
                        $sCategory = $aOption["category"];
                    }

                    $aGroupOptions = $aOption["options"];
                    foreach ($aGroupOptions as $sGroupOption) {

                        $iArrKey = array_search($sGroupOption, $this->aSettings["showOptions"]);
                        if ($iArrKey != "") {
                            if ($this->aSettings["options"][$sGroupOption]["type"] != "html") {
                                $this->aSettings["showOptions"][$iArrKey] = "gridblockcontentsettings_deleted_option";
                            }
                        }

                        $aNewShowOptions[] = $sGroupOption;
                        $this->aSettings["options"][$sGroupOption]["category"] = $sCategory;
                    }

                    $aNewShowOptions[] = "gridblockcontentsettings_group_end_" . $sType . '_' . $this->iColumnId . '_' . $aOption["key"];

                    $this->aSettings["options"]["gridblockcontentsettings_group_end_" . $sType . '_' . $this->iColumnId . '_' . $aOption["key"]] = array("type" => "html", "text" => "</div></div></div>", "category" => $sCategory);

                    array_splice($this->aSettings["showOptions"], $iPos, 0, $aNewShowOptions);
                    $iPos = $iPos + count($aGroupOptions) + 1;
                }
                $iPos++;
            }

            $aTmp = array();
            foreach ($this->aSettings["showOptions"] as $sOption) {
                if ($sOption != "gridblockcontentsettings_deleted_option") {
                    $aTmp[] = $sOption;
                }
            }
            $this->aSettings["showOptions"] = $aTmp;
        }

        if (isset($this->aSettings["showOptions"])) {
            foreach (Gridblock2Builder_Tree::getMappedCsFieldKeys($sType) as $sKey) {
                if (($iX = array_search($sKey, $this->aSettings["showOptions"])) !== false) {
                    unset($this->aSettings["showOptions"][$iX]);
                }
            }
        }



        // Kategorien aufbauen
        $this->aSettings["options_in_categories"] = false;
        if (isset($this->aSettings["showOptions"])) {
            $sCategory = "";
            foreach ($this->aSettings["showOptions"] as $sOption) {
                $aOption = $this->aSettings["options"][$sOption];

                if (!isset($aOption["use_last_category"])) {
                    $sCategory = "";
                }
                if (isset($aOption["category"])) {
                    $sCategory = $aOption["category"];
                }
                if ($sCategory != "") {
                    if (!isset($this->aSettings["categories"][$sCategory])) {
                        $this->aSettings["categories"][$sCategory] = array("label" => $sCategory);
                    }
                    $this->aSettings["categories"][$sCategory]["showOptions"][] = $sOption;
                    $this->aSettings["options_in_categories"] = true;
                }
            }
        }

        if ($this->aSettings["options_in_categories"] == true) {
            foreach ($this->aSettings["showOptions"] as $sOption) {
                $aOption = $this->aSettings["options"][$sOption];
                $sCategory = "";
                if (isset($aOption["category"])) {
                    $sCategory = $aOption["category"];
                }
                if ($sCategory == "" && !isset($aOption["use_last_category"])) {
                    $sCategory = "gridblockcontentsettingsgeneral";
                    if (!isset($this->aSettings["categories"][$sCategory])) {
                        $this->aSettings["categories"][$sCategory] = array("label" => "Sonstige Einstellungen");
                    }
                    $this->aSettings["categories"][$sCategory]["showOptions"][] = $sOption;
                }
            }
        }

        if ($this->aSettings["options_in_categories"] == false) {
            $this->aSettings["categories"] = array();
            $this->aSettings["categories"]["gridblockcontentsettingsgeneral"] = array("label" => "Keine Kategorien verwendet");
            if (isset($this->aSettings["showOptions"])) {
                foreach ($this->aSettings["showOptions"] as $sOption) {
                    $this->aSettings["categories"]["gridblockcontentsettingsgeneral"]["showOptions"][] = $sOption;
                }
            }
        }
    }

    private static array $decodedFileCache = [];

    private static function readJsonData($sFile)
    {
        if (!file_exists($sFile)) {
            return null;
        }

        $iMtime = filemtime($sFile);
        if (isset(self::$decodedFileCache[$sFile]) && self::$decodedFileCache[$sFile][0] === $iMtime) {
            return self::$decodedFileCache[$sFile][1];
        }

        $sContent = file_get_contents($sFile);
        $aData = json_decode($sContent, true);
        if ($sContent && json_last_error() != 0 && rex::isBackend()) {
            throw new rex_exception("gridblockContentSettings: json Error in File $sFile");
        }

        self::$decodedFileCache[$sFile] = [$iMtime, $aData];
        return $aData;
    }

    private static function renderPaletteField(array $aPalette, string $sRaw, string $sInput): string
    {
        static $sVarsJson = null;
        if ($sVarsJson === null) {
            $sVarsJson = json_encode(array('css' => Gridblock2Palette::previewVarsCss()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        }

        $sMatch = null;
        if ($sRaw !== '') {
            foreach ($aPalette as $aEntry) {
                if (strcasecmp((string) $aEntry['value'], $sRaw) === 0) {
                    $sMatch = (string) $aEntry['value'];
                    break;
                }
            }
            if ($sMatch === null) {
                foreach ($aPalette as $aEntry) {
                    if ((string) $aEntry['class'] !== '' && strcasecmp((string) $aEntry['class'], $sRaw) === 0) {
                        $sMatch = (string) $aEntry['value'];
                        break;
                    }
                }
            }
        }
        $bAlt = ($sRaw !== '' && $sMatch === null && Gridblock2Palette::isClassValue($sRaw));
        $bInd = ($sRaw !== '' && $sMatch === null && !$bAlt);

        $sOptions = '<option value=""' . ($sRaw === '' ? ' selected="selected"' : '') . '>- keine Farbe -</option>';
        if ($bAlt) {
            $sOptions .= '<option value="' . rex_escape($sRaw) . '" data-gb-alt="1" selected="selected">Alt: ' . rex_escape($sRaw) . '</option>';
        }
        $bSelected = false;
        foreach ($aPalette as $aEntry) {
            $sSelected = '';
            if ($sMatch !== null && !$bSelected && (string) $aEntry['value'] === $sMatch) {
                $sSelected = ' selected="selected"';
                $bSelected = true;
            }
            $sOptions .= '<option value="' . rex_escape((string) $aEntry['value']) . '" data-gb-bg="' . rex_escape((string) $aEntry['bg']) . '" data-gb-fg="' . rex_escape((string) $aEntry['fg']) . '"' . $sSelected . '>' . rex_escape((string) $aEntry['label']) . '</option>';
        }
        $sOptions .= '<option value="__ind__" data-gb-ind="1"' . ($bInd ? ' selected="selected"' : '') . '>Individuell</option>';

        return '<div data-gb-palette-wrap>'
            . '<select class="selectpicker form-control" data-gb-palette data-gb-palette-field data-live-search="true" data-width="100%">' . $sOptions . '</select>'
            . '<script type="application/json" class="gb-pal-vars">' . $sVarsJson . '</script>'
            . '<div data-gb-palette-ind' . ($bInd ? '' : ' style="display:none"') . '>' . $sInput . '</div>'
            . '</div>';
    }

    public static function hasModulesScope()
    {
        $addon = rex_addon::get('gridblock2');
        foreach (['contentsettings/contentsettings.global.json', 'contentsettings/contentsettings.json'] as $sFile) {
            $aData = self::readJsonData($addon->getDataPath($sFile));
            if (is_array($aData) && !empty($aData['modules']['showOptions']) && is_array($aData['modules']['showOptions'])) {
                return true;
            }
        }
        return false;
    }

    private function applyModulesRestrict()
    {
        if (!is_array($this->modulesContext) || empty($this->aSettings['showOptions']) || !is_array($this->aSettings['showOptions'])) {
            return;
        }

        $mIncludeSystem = $this->projectData['modules']['includeSystem'] ?? $this->globalData['modules']['includeSystem'] ?? true;
        if (($this->modulesContext['kind'] ?? '') === 'system' && in_array($mIncludeSystem, [false, 0, '0', 'false'], true)) {
            $this->aSettings['showOptions'] = [];
            return;
        }

        $aRestrict = array_replace(
            (array) ($this->globalData['modules']['restrict'] ?? []),
            (array) ($this->projectData['modules']['restrict'] ?? [])
        );
        if (!$aRestrict) {
            return;
        }

        $fAllowed = function ($sKey) use ($aRestrict) {
            return !isset($aRestrict[$sKey]) || $this->modulesRuleAllows((array) $aRestrict[$sKey]);
        };

        $aKeep = [];
        foreach ($this->aSettings['showOptions'] as $sOption) {
            if (!$fAllowed($sOption)) {
                continue;
            }
            $aOption = $this->aSettings['options'][$sOption] ?? [];
            if (($aOption['type'] ?? '') === 'group' && isset($aOption['options']) && is_array($aOption['options'])) {
                $aMembers = array_values(array_filter($aOption['options'], $fAllowed));
                $this->aSettings['options'][$sOption]['options'] = $aMembers;
                if (!$aMembers) {
                    continue;
                }
            }
            $aKeep[] = $sOption;
        }
        $this->aSettings['showOptions'] = $aKeep;
    }

    private function modulesRuleAllows(array $aRule)
    {
        if (!empty($aRule['deny']) && $this->modulesContextListed((array) $aRule['deny'])) {
            return false;
        }
        if (!empty($aRule['allow']) && !$this->modulesContextListed((array) $aRule['allow'])) {
            return false;
        }
        return true;
    }

    private function modulesContextListed(array $aLists)
    {
        $sKind = (string) ($this->modulesContext['kind'] ?? '');
        $mEntry = $aLists[$sKind] ?? null;
        if ($mEntry === null || $mEntry === false) {
            return false;
        }
        if ($mEntry === true) {
            return true;
        }

        $aIds = array_map('strval', (array) ($this->modulesContext['ids'] ?? []));
        foreach ((array) $mEntry as $mCandidate) {
            $sCandidate = (string) $mCandidate;
            if ($sCandidate === '*') {
                return true;
            }
            if ($sCandidate !== '' && in_array($sCandidate, $aIds, true)) {
                return true;
            }
        }
        return false;
    }

    public function getOptions($sKey)
    {
        $aSelectData = $this->getSelectData($sKey);
        if (isset($aSelectData)) {
            if (count($aSelectData)) {
                $this->aSettings["options"][$sKey]["selectdata"] = $aSelectData;
            }
        }
        return $this->aSettings["options"][$sKey];
    }

    public function getDefault($sKey)
    {
        if (isset($this->aSettings["options"][$sKey]["default"])) {
            return $this->aSettings["options"][$sKey]["default"];
        }
    }

    function getOptionLabel($sKey, $sValue)
    {
        return $this->aSettings["options"][$sKey]["selectdata"][$sValue];
    }

    function getSelectData($sKey)
    {
        $aData = array();

        $aOption = $this->aSettings["options"][$sKey];

        if (isset($aOption["data"])) {
            foreach ($aOption["data"] as $sOptionsKey => $sOptionsVal) {
                if (isset($aOption["default"])) {
                    if ($sOptionsKey == $aOption["default"]) {
                        $sOptionsKey = "gridblockcontentsettingsdefault";
                        $sOptionsVal .= " " . $this->addon->i18n("gridblockcontentsettings_default_option");
                    }
                }
                $aData[$sOptionsKey] = $sOptionsVal;
            }
        }

        return $aData;
    }


    function getGridblock2ContentSettingsForm($aSavedOptions = array(), $iTemplateId = "0", $iColumnId = "0", $sRootScope = "template")
    {
        $this->iTemplateId = $iTemplateId;
        $this->iColumnId = $iColumnId;
        $this->sInstanceUid = bin2hex(random_bytes(5));
        $sType = $iColumnId ? "columns" : $sRootScope;
        $this->getAllSettings($sType);

        $sForm = '<div class="gridblockcontentsettings-form gridblockcontentsettings-type-' . $sType . '">';

        if ($iColumnId) {
            $sType = "column_" . $iColumnId;
        } else {
            $sType = $sRootScope;
        }
        $aUsedTypes = array();
        $iTabRand = $this->sInstanceUid;
        if (isset($this->aSettings["categories"])) {

            $sForm .= '<ul class="nav nav-tabs tab-nav">';
            if ($this->aSettings["options_in_categories"] == true) {
                $iX = 0;
                foreach ($this->aSettings["categories"] as $aCategory) {
                    if (isset($aCategory["showOptions"])) {
                        $sForm .= '<li><a href="#gridblockcontentsettings-content-' . $iTabRand . '-' . $iX . '" data-toggle="tab">';
                        if (isset($aCategory["icon"]) && $aCategory["icon"] != "") {
                            $sTippy = "";
                            if (isset($aCategory["icon_tooltip"])) {
                                $sTippy = ' data-tippy-content="' . $aCategory["icon_tooltip"] . '"';
                            }
                            $sForm .= ' <i class="' . $aCategory["icon"] . '" style="padding-right:10px" ' . $sTippy . '></i>';
                        }
                        $sForm .= $aCategory["label"] . '</a></li>' . PHP_EOL;
                        $iX++;
                    }
                }
            }
            $sForm .= '</ul>';
            $sForm .= '<div class="tab-content" id="gridblockcontentsettings-tab-content-' . $iTabRand . '">';
            $iX = 0;

            foreach ($this->aSettings["categories"] as $aCategory) {
                if (isset($aCategory["showOptions"])) {
                    if ($this->aSettings["options_in_categories"] == true) {
                        $sForm .= '<div class="tab-pane fade" id="gridblockcontentsettings-content-' . $iTabRand . '-' . $iX . '">';
                    }
                    $sForm .= '<div class="rex-form-group active">';

                    foreach ($aCategory["showOptions"] as $sKey) {
                        $aOption = $this->aSettings["options"][$sKey];
                        if (isset($aOption)) {
                            if (count($aOption)) {

                                if (!in_array($aOption["type"], $aUsedTypes)) {
                                    array_push($aUsedTypes, $aOption["type"]);
                                }

                                if ($aOption["type"] != "html") {
                                    $sForm .= '<dl class="rex-form-group form-group gridblockcontentsettings">' . PHP_EOL;
                                }
                                if (isset($aOption["label"])) {
                                    if ($aOption["label"] != "") {
                                        $sTippy = "";
                                        if (isset($aOption["label_tooltip"])) {
                                            $sTippy = ' data-tippy-content="' . $aOption["label_tooltip"] . '"';
                                        }
                                        $sForm .= '<dt><label for=""><span ' . $sTippy . '>' . $aOption["label"] . '</span>';
                                        if (isset($aOption["icon"]) && $aOption["icon"] != "") {
                                            $sTippy = "";
                                            if (isset($aOption["icon_tooltip"])) {
                                                $sTippy = ' data-tippy-content="' . $aOption["icon_tooltip"] . '"';
                                            }
                                            $sForm .= ' <i class="' . $aOption["icon"] . '" style="padding-left:10px" ' . $sTippy . '></i>';
                                        }
                                        $sForm .= '</label></dt>' . PHP_EOL;
                                    }
                                }

                                $sDisabled = '';
                                if (isset($aOption["disabled"])) {
                                    if ($aOption["disabled"] == true) {
                                        $sDisabled = 'disabled="disabled" ';
                                    }
                                }

								$uid = uniqid().ceil(rand(0,9999999));

                                switch ($aOption["type"]) {
                                    case "text":
                                    case "tel":
                                    case "url":
                                    case "number":
                                    case "color":
                                    case "numeric":
                                    case "email":
                                    case "date":
                                    case "datetime":
                                    case "datetime-local":
                                    case "month":
                                    case "week":

                                        $sFieldType = $aOption["type"];

                                        $sClass = 'class="form-control"';
                                        if (isset($aOption["class"])) {
                                            $sClass = 'class="' . $aOption['class'] . '"';
                                        }
                                        $sPlaceholder = '';
                                        if (isset($aOption["placeholder"])) {
                                            $sPlaceholder = 'placeholder="' . $aOption['placeholder'] . '"';
                                        }
                                        $sValue = "";
                                        if (isset($aSavedOptions[$sType][$sKey])) {
                                            $sValue = rex_escape($aSavedOptions[$sType][$sKey]);
                                        } else if (isset($aOption["default"]) && $aOption["default"] != "") {
                                            $sValue = $aOption["default"];
                                        }

                                        $sAttributes = "";
                                        if (isset($aOption["attributes"])) {
                                            $sAttributes = $aOption["attributes"];
                                        }

                                        $sForm .= '<dd><input name="REX_INPUT_VALUE[' . $this->iSettingsId . '][' . $sType . '][' . $sKey . ']" type="' . $sFieldType . '" ' . $sClass . ' ' . $sAttributes . ' value="' . $sValue . '" ' . $sPlaceholder . ' ' . $sDisabled . ' id="'.$uid.'"></dd>' . PHP_EOL;
                                        break;

                                    case "textarea":
                                        $sClass = 'class="form-control"';
                                        if (isset($aOption["class"])) {
                                            $sClass = 'class="' . $aOption['class'] . '"';
                                        }
                                        $sPlaceholder = '';
                                        if (isset($aOption["placeholder"])) {
                                            $sPlaceholder = 'placeholder="' . $aOption['placeholder'] . '"';
                                        }
                                        $sValue = "";
                                        if (isset($aSavedOptions[$sType][$sKey])) {
                                            $sValue = rex_escape($aSavedOptions[$sType][$sKey]);
                                        } else if (isset($aOption["default"]) && $aOption["default"] != "") {
                                            $sValue = $aOption["default"];
                                        }

                                        $sAttributes = "";
                                        if (isset($aOption["attributes"])) {
                                            $sAttributes = $aOption["attributes"];
                                        }

                                        $sForm .= '<dd><textarea name="REX_INPUT_VALUE[' . $this->iSettingsId . '][' . $sType . '][' . $sKey . ']" ' . $sClass . ' ' . $sAttributes . ' ' . $sPlaceholder . ' ' . $sDisabled . ' id="'.$uid.'">' . $sValue . '</textarea></dd>' . PHP_EOL;
                                        break;

                                    case "colorpicker":
                                        $sClass = 'class="form-control"';
                                        if (isset($aOption["class"])) {
                                            $sClass = 'class="' . $aOption['class'] . '"';
                                        }
                                        $sPlaceholder = 'placeholder="Bsp. #003366"';
                                        if (isset($aOption["placeholder"])) {
                                            $sPlaceholder = 'placeholder="' . $aOption['placeholder'] . '"';
                                        }
                                        $sValue = "";
                                        if (isset($aSavedOptions[$sType][$sKey])) {
                                            $sValue = rex_escape($aSavedOptions[$sType][$sKey]);
                                        } else if (isset($aOption["default"]) && $aOption["default"] != "") {
                                            $sValue = $aOption["default"];
                                        }

                                        $sAttributes = "";
                                        if (isset($aOption["attributes"])) {
                                            $sAttributes = $aOption["attributes"];
                                        }

                                        $sPickerMode = (isset($aOption["mode"]) && in_array($aOption["mode"], array('solid', 'both', 'gradient'), true)) ? $aOption["mode"] : 'solid';
                                        $sPickerInput = '<input data-parsley-excluded="true" data-colorpicker data-colorpicker-mode="' . $sPickerMode . '" type="text" name="REX_INPUT_VALUE[' . $this->iSettingsId . '][' . $sType . '][' . $sKey . ']" ' . $sAttributes . ' value="' . $sValue . '" ' . $sPlaceholder . ' ' . $sClass . ' ' . $sDisabled . ' id="'.$uid.'">';
                                        $aPalette = (!empty($aOption["palette"])) ? Gridblock2Palette::getEntries() : array();
                                        if ($aPalette && $aOption["palette"] === "colors") {
                                            $aPalette = Gridblock2Palette::colorEntries($aPalette);
                                        }
                                        if ($aPalette) {
                                            $sRawValue = isset($aSavedOptions[$sType][$sKey]) ? (string) $aSavedOptions[$sType][$sKey] : ((isset($aOption["default"]) && $aOption["default"] != "") ? (string) $aOption["default"] : '');
                                            $sForm .= '<dd>' . self::renderPaletteField($aPalette, $sRawValue, $sPickerInput) . PHP_EOL;
                                        } else {
                                            $sForm .= '<dd>' . $sPickerInput . PHP_EOL;
                                        }
                                        break;

                                    case "select":
                                        $sClass = 'class="selectpicker w-100"';
                                        if (isset($aOption["class"])) {
                                            $sClass = 'class="' . $aOption['class'] . '"';
                                        }

                                        $sAttributes = "";
                                        if (isset($aOption["attributes"])) {
                                            $sAttributes = $aOption["attributes"];
                                        }

                                        $aSelectData = $this->getSelectData($sKey);
                                        $sForm .= '<dd><select name="REX_INPUT_VALUE[' . $this->iSettingsId . '][' . $sType . '][' . $sKey . ']" ' . $sClass . ' ' . $sAttributes . ' ' . $sDisabled . ' data-live-search="true" id="'.$uid.'">' . PHP_EOL;
                                        foreach ($aSelectData as $sSelectKey => $sSelectValue) :
                                            $sSelected = '';
                                            $sSelectLabel = "";
                                            $sSelectData = "";
                                            if (is_array($sSelectValue)) {
                                                foreach ($sSelectValue as $sSelectValueKey => $sSelectValueValue) {
                                                    if ($sSelectValueKey == "label") {
                                                        $sSelectLabel = $sSelectValueValue;
                                                    } else {
                                                        $sSelectData .= $sSelectValueKey . '="' . $sSelectValueValue . '" ';
                                                    }
                                                }
                                            } else {
                                                $sSelectLabel = $sSelectValue;
                                            }

                                            if (isset($aSavedOptions[$sType][$sKey])) {
                                                if ($sSelectKey == @$aSavedOptions[$sType][$sKey] or ($sSelectKey == "gridblockcontentsettingsdefault" && $aOption["default"] == @$aSavedOptions[$sType][$sKey])) {
                                                    $sSelected = 'selected="selected"';
                                                    if ($sSelectKey == "gridblockcontentsettingsdefault" && @$aSavedOptions[$sType][$sKey] != "gridblockcontentsettingsdefault") {
                                                        $sSelectKey = rex_escape(@$aSavedOptions[$sType][$sKey]);
                                                    }
                                                }
                                            } else {
                                                $sSelected = ($sSelectKey == "gridblockcontentsettingsdefault") ? 'selected="selected"' : '';
                                            }
                                            $sForm .= '<option value="' . $sSelectKey . '" ' . $sSelected . ' ' . $sSelectData . '>' . $sSelectLabel . '</option>' . PHP_EOL;
                                        endforeach;
                                        $sForm .= '</select></dd>' . PHP_EOL;
                                        break;

                                    case "checkbox":
                                        $sClass = 'class=""';
                                        if (isset($aOption["class"])) {
                                            $sClass = 'class="' . $aOption['class'] . '"';
                                        }
                                        $sAttributes = "";
                                        if (isset($aOption["attributes"])) {
                                            $sAttributes = $aOption["attributes"];
                                        }

                                        $sValue = "1";
                                        if (isset($aOption["value"])) {
                                            $sValue = $aOption["value"];
                                        }

                                        $sText = "";
                                        if (isset($aOption["text"])) {
                                            $sText = " " . $aOption["text"];
                                        }

                                        if (isset($aOption["checked"])) {
                                            if ($aOption["checked"] == "1" && !isset($aSavedOptions)) {
                                                $sChecked = 'checked="checked"';
                                            }
                                        }
                                        if (!isset($sChecked)) {
                                            $sChecked = ($sValue == @$aSavedOptions[$sType][$sKey]) ? 'checked="checked"' : '';
                                        }
                                        $sForm .= '<dd><div class="checkbox toggle"><label><input type="checkbox" name="REX_INPUT_VALUE[' . $this->iSettingsId . '][' . $sType . '][' . $sKey . ']" value="' . $sValue . '" ' . $sChecked . ' ' . $sClass . ' ' . $sAttributes . ' ' . $sDisabled . ' id="'.$uid.'">' . $sText . '</label></div></dd>' . PHP_EOL;
                                        unset($sChecked);
                                        break;

                                    case "radio":
                                        $sClass = 'class=""';
                                        if (isset($aOption["class"])) {
                                            $sClass = 'class="' . $aOption['class'] . '"';
                                        }
                                        $sForm .= '<dd><div class="radio toggle switch">' . PHP_EOL;
                                        $aSelectData = $this->getSelectData($sKey);
										$sC = 0;
                                        foreach ($aSelectData as $sSelectKey => $sSelectValue):
                                            //$iRand = rand(0, 1000000) * rand(0, 100000);
                                            $sSelected = '';
                                            if (isset($aSavedOptions[$sType][$sKey])) {
                                                if ($sSelectKey == @$aSavedOptions[$sType][$sKey] or ($sSelectKey == "gridblockcontentsettingsdefault" && $aOption["default"] == @$aSavedOptions[$sType][$sKey])) {
                                                    $sSelected = 'checked="checked"';
                                                    if ($sSelectKey == "gridblockcontentsettingsdefault" && @$aSavedOptions[$sType][$sKey] != "gridblockcontentsettingsdefault") {
                                                        $sSelectKey = rex_escape(@$aSavedOptions[$sType][$sKey]);
                                                    }
                                                }
                                            } else {
                                                $sSelected = ($sSelectKey == "gridblockcontentsettingsdefault") ? 'checked="checked"' : '';
                                            }


                                            $sForm .= '<label for="' . $uid.$sC . '">' . PHP_EOL;
                                            $sForm .= '<input id="' . $uid.$sC . '" name="REX_INPUT_VALUE[' . $this->iSettingsId . '][' . $sType . '][' . $sKey . ']" type="radio" value="' . $sSelectKey . '" ' . $sSelected . ' ' . $sClass . '  ' . $sDisabled . '/> ' . $sSelectValue . PHP_EOL;
                                            $sForm .= '</label>';

											$sC++;
                                        endforeach;
                                        $sForm .= '</div></dd>' . PHP_EOL;
                                        break;

                                    case "media":
                                        $aArgs = array();
                                        if (isset($aOption["preview"])) {
                                            if ($aOption["preview"]) {
                                                $aArgs["preview"] = "1";
                                            }
                                        }
                                        if (isset($aOption["types"])) {
                                            if ($aOption["types"]) {
                                                $aArgs["types"] = $aOption["types"];
                                            }
                                        }
                                        //$iRand = rand(0, 1000000) * rand(0, 100000);
                                        $sForm .= rex_var_media::getWidget($uid, "REX_INPUT_VALUE[$this->iSettingsId][$sType][$sKey]", @$aSavedOptions[$sType][$sKey], $aArgs);
                                        break;

                                    case "medialist":
                                        $aArgs = array();
                                        if (isset($aOption["preview"])) {
                                            $aArgs["preview"] = "1";
                                        }
                                        if (isset($aOption["types"])) {
                                            $aArgs["types"] = $aOption["types"];
                                        }
                                        //$iRand = rand(0, 1000000) * rand(0, 100000);
                                        $sForm .= rex_var_medialist::getWidget($uid, "REX_INPUT_VALUE[$this->iSettingsId][$sType][$sKey]", @$aSavedOptions[$sType][$sKey], $aArgs);
                                        break;

                                    case "link":
                                        $aArgs = array();
                                        //$iRand = rand(0, 1000000) * rand(0, 100000);
                                        $sForm .= rex_var_link::getWidget($uid, "REX_INPUT_VALUE[$this->iSettingsId][$sType][$sKey]", @$aSavedOptions[$sType][$sKey], $aArgs);
                                        break;

                                    case "linklist":
                                        $aArgs = array();
                                        //$iRand = rand(0, 1000000) * rand(0, 100000);
                                        $sForm .= rex_var_linklist::getWidget($uid, "REX_INPUT_VALUE[$this->iSettingsId][$sType][$sKey]", @$aSavedOptions[$sType][$sKey], $aArgs);
                                        break;
                                    case "customlink":
                                        if (!rex_addon::get('mform')->isAvailable()) {
                                            $sForm .= 'To use customlink please install addon mform';
                                        } else {
                                            $aArgs = array();
                                            //$iRand = rand(0, 100) * rand(0, 100);
                                            $sForm .= rex_var_custom_link::getWidget($uid, "REX_INPUT_VALUE[$this->iSettingsId][$sType][$sKey]", @$aSavedOptions[$sType][$sKey], $aArgs);
                                        }
                                        break;
                                    case "multilink":
                                        $aArgs = array();
                                        if (isset($aOption["types"]) && $aOption["types"]) {
                                            $aArgs["types"] = is_array($aOption["types"]) ? $aOption["types"] : array_map('trim', explode(',', $aOption["types"]));
                                        }
                                        if (isset($aOption["mediatypes"]) && $aOption["mediatypes"]) {
                                            $aArgs["mediaTypes"] = $aOption["mediatypes"];
                                        }
                                        if (isset($aOption["mediacategory"]) && $aOption["mediacategory"]) {
                                            $aArgs["mediaCategory"] = (int) $aOption["mediacategory"];
                                        }
                                        $sForm .= Gridblock2Builder_Tree::renderMultilink("REX_INPUT_VALUE[$this->iSettingsId][$sType][$sKey]", @$aSavedOptions[$sType][$sKey], $aArgs);
                                        break;

                                    case "html":
                                        $sForm .= $aOption["text"];
                                        break;

                                    case "slider":
                                        $sClass = 'class="form-control bootstap-slider"';
                                        if (isset($aOption["class"])) {
                                            $sClass = 'class="' . rex_escape((string) $aOption['class']) . '"';
                                        }
                                        $sValue = '';
                                        if (@$aSavedOptions[$sType][$sKey] != "") {
                                            $sValue = rex_escape((string) @$aSavedOptions[$sType][$sKey]);
                                        } else if (isset($aOption["default"])) {
                                            $sValue = rex_escape((string) $aOption["default"]);
                                        }
                                        $sSliderMin = '';
                                        if (isset($aOption["slider-min"])) {
                                            $sSliderMin = 'data-slider-min="' . rex_escape((string) $aOption['slider-min']) . '"';
                                        }
                                        $sSliderMax = '';
                                        if (isset($aOption["slider-max"])) {
                                            $sSliderMax = 'data-slider-max="' . rex_escape((string) $aOption['slider-max']) . '"';
                                        }

                                        $sSliderTooltipSplit = 'data-slider-tooltip-split="true"';
                                        if (isset($aOption["slider-tooltip-split"])) {
                                            $sSliderTooltipSplit = 'data-slider-tooltip-split="' . rex_escape((string) $aOption["slider-tooltip-split"]) . '"';
                                        }

                                        $sSliderRange = '';
                                        if (isset($aOption["slider-range"]) && ($aOption["slider-range"] == "1")) {
                                            $sSliderRange = 'data-slider-range="' . rex_escape((string) $aOption['slider-range']) . '"';
                                        } else {
                                            $sSliderTooltipSplit = '';
                                        }

                                        $sSliderStep = 'data-slider-step="1"';
                                        if (isset($aOption["slider-step"])) {
                                            $sSliderStep = 'data-slider-step="' . rex_escape((string) $aOption['slider-step']) . '"';
                                        }
                                        if (strpos($sValue, ',') !== false) {
                                            $sSliderValue = 'data-slider-value="[' . $sValue . ']"';
                                        } else {
                                            $sSliderValue = 'data-slider-value="' . $sValue . '"';
                                        }

                                        $sSliderShowTooltip = 'data-slider-tooltip="always"';
                                        if (isset($aOption["slider-tooltip"])) {
                                            if ($aOption["slider-tooltip"] == "hover") {
                                                $aOption["slider-tooltip"] = "show";
                                            }
                                            $sSliderShowTooltip = 'data-slider-tooltip="' . rex_escape((string) $aOption["slider-tooltip"]) . '"';
                                        }


                                        //$iRand = rand(0, 1000000) * rand(0, 100000);


                                        $sForm .= '<dd><input id="gridblockcontentsettings-slider-' . $uid . '" name="REX_INPUT_VALUE[' . $this->iSettingsId . '][' . $sType . '][' . $sKey . ']" type="text" ' . $sClass . ' value="' . $sValue . '" ' . $sSliderTooltipSplit . ' ' . $sSliderMin . ' ' . $sSliderMax . ' ' . $sSliderRange . ' ' . $sSliderStep . ' ' . $sSliderValue . ' ' . $sSliderShowTooltip . ' ' . $sDisabled . '>';
                                        if (isset($aOption["slider-unit"])) {
                                            $sForm .= "<script>

                                        $('#gridblockcontentsettings-slider-" . $uid . "').slider({
                                            formatter: function(value) {
                                                return value + ' ' + " . json_encode((string) $aOption["slider-unit"], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ";
                                            }
                                        });
                                        </script>";
                                        }
                                        $sForm .= '</dd>' . PHP_EOL;
                                        break;
                                }

                                if ($aOption["type"] != "html") {
                                    $sForm .= '</dl>' . PHP_EOL;
                                }
                            }
                        }
                    }

                    $sForm .= '</div>';
                    $sForm .= '</div>';
                    $iX++;
                }
            }
            $sForm .= '</div>';
            if ($this->aSettings["options_in_categories"] == true) {
                $sForm .= '<script>$(function(){ $(\'a[href="#gridblockcontentsettings-content-' . $iTabRand . '-0"]\').tab("show"); });</script>';                    //immer ersten Tab einblenden
            }
            $sForm .= '</div>';

            if (!count($aUsedTypes)) {
                return;
            }
            return $sForm;
        }
    }

    function parseGridblock2ContentSettings($aArr, $iTemplateId)
    {
        $iTemplateId = intval($iTemplateId);
        $iNumsColumns = 0;

        $db = rex_sql::factory();
        $db->setQuery("SELECT columns, preview FROM " . rex::getTable('2034_gridtemplates') . " WHERE id = '" . $iTemplateId . "'");
        if ($db->getRows() > 0) {
            $iNumsColumns = (int) $db->getValue('columns');
        }

        $this->iTemplateId = $iTemplateId;
        $this->getAllSettings();

        $aData = array();
        $aDataLabels = array();

        // template
        if (isset($this->aSettings["showOptions"])) {
            foreach ($this->aSettings["showOptions"] as $sKey) {
                if ($this->aSettings["options"][$sKey]["type"] != "html") {
                    $aData["template"][$sKey] = "";

                    if (!isset($aArr["template"][$sKey]) or $aArr["template"][$sKey] == "gridblockcontentsettingsdefault" or @$this->aSettings["options"][$sKey]["disabled"]) {
                        $aData["template"][$sKey] = $this->getDefault($sKey);
                    } else {
                        $aData["template"][$sKey] = $aArr["template"][$sKey];
                    }

                    $aDataLabels["template"][$sKey] = "";
                    $sValue = $aData["template"][$sKey];
                    $aDataLabels["template"][$sKey] = array("key" => $sValue, "label" => @$this->aSettings["options"][$sKey]["label"], "value" => @$this->aSettings["options"][$sKey]["data"][$sValue]);
                }
            }
        }

        // columns
        for ($iX = 1; $iX <= $iNumsColumns; $iX++) {
            $this->iColumnId = $iX;
            $this->getAllSettings("columns");
            if (isset($this->aSettings["showOptions"])) {
                foreach ($this->aSettings["showOptions"] as $sKey) {
                    if ($this->aSettings["options"][$sKey]["type"] != "html") {
                        $aData["column_" . $iX][$sKey] = "";
                        if (!isset($aArr["column_" . $iX][$sKey]) or $aArr["column_" . $iX][$sKey] == "gridblockcontentsettingsdefault" or @$this->aSettings["options"][$sKey]["disabled"]) {
                            $aData["column_" . $iX][$sKey] = $this->getDefault($sKey);
                        } else {
                            $aData["column_" . $iX][$sKey] = $aArr["column_" . $iX][$sKey];
                        }

                        $aDataLabels["column_" . $iX][$sKey] = "";
                        $sValue = $aData["column_" . $iX][$sKey];
                        $aDataLabels["column_" . $iX][$sKey] = array("key" => $sValue, "label" => @$this->aSettings["options"][$sKey]["label"], "value" => @$this->aSettings["options"][$sKey]["data"][$sValue]);
                    }
                }
            }
        }

        $aData["data_with_labels"] = $aDataLabels;

        $oData = json_decode(json_encode($aData), FALSE);
        return $oData;
    }

    public static function parseCustomLink($sLink = null)
    {
        if ($sLink == "") {
            return;
        }

        $sSchemeProbe = preg_replace('/[\x00-\x20\x7f]+/', '', (string) $sLink);
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $sSchemeProbe, $aScheme) && !in_array(strtolower($aScheme[1]), array('http', 'https', 'mailto', 'tel'), true)) {
            return null;
        }

        if (strpos($sLink, "mailto:") !== false) {
            $sEmail = str_replace("mailto:", "", $sLink);
            if (filter_var($sEmail, FILTER_VALIDATE_EMAIL)) {
                $aArr = array(
                    "email" => $sEmail,
                    "type" => "email",
                    "source" => $sLink,
                );
                return $aArr;
            };
        }

        // tel
        if (strpos($sLink, "tel:") !== false) {
            $sNr = str_replace("tel:", "", $sLink);
            if (preg_match('/^[+\d][\d\s\-\/()]*$/', $sNr)) {
                $aArr = array(
                    "number" => $sNr,
                    "type" => "tel",
                    "source" => $sLink,
                );
                return $aArr;
            };
        }

        // external url
        if (filter_var($sLink, FILTER_VALIDATE_URL)) {
            $aArr = array(
                "url" => $sLink,
                "target" => "_blank",
                "type" => "external_url",
                "source" => $sLink,
            );
            return $aArr;
        };



        // internal url
        if (filter_var($sLink, FILTER_VALIDATE_INT)) {
            $aArr = array(
                "url" => rex_getUrl($sLink),
                "target" => "_self",
                "type" => "internal_url",
                "source" => $sLink,
            );
            return $aArr;
        };

        // media
        $oMedia = rex_media::get($sLink);
        if ($oMedia) {
            $aArr = array(
                "url" => $oMedia->getUrl(),
                "type" => "media",
                "media" => $oMedia,
                "source" => $sLink,
            );
            return $aArr;
        }

        // other
        $aArr = array(
            "url" => $sLink,
            "type" => "other",
            "source" => $sLink,
        );
        return $aArr;
    }

    public function getJsonContent($sFile)
    {
        $sContent = "";
        if (file_exists($sFile)) {
            $sContent = file_get_contents($sFile);
            json_decode($sContent);
            if ($sContent && json_last_error() != "0") {
                if (rex::isBackend()) {
                    throw new rex_exception("gridblockContentSettings: json Error in File $sFile");
                }
            }
        }
        return $sContent;
    }

    public function getBackendSummary(?object $oData = null, $iTemplateId = 0)
    {

        $aColumns = array();
        $iColumns = "16";
        $aPreview = array();
        if ($iTemplateId) {
            $iTemplateId = intval($iTemplateId);
            $oDb = rex_sql::factory();
            $oDb->setQuery("SELECT columns,preview FROM " . rex::getTable('2034_gridtemplates') . " WHERE id = '$iTemplateId' Limit 1");
            if ($oDb->getRows() > 0) {
                $iColumns = $oDb->getValue("columns");
                $aDecodedPreview = json_decode((string) $oDb->getValue("preview"), true);
                $aPreview = is_array($aDecodedPreview) ? $aDecodedPreview : array();
            }
        }




        $sHtml = "";
        $iBlockId = rand(0, 100000) . time() . rand(0, 10000000);
        $sHtml .= '<br /><a href="javascript:void(0)" class="btn btn-abort w-100 text-center gridblockcontentsettings-toggler gridblockcontentsettings-toggler-' . $iBlockId . '" data-id="#gridblockcontentsettings-' . $iBlockId . '" style="width:100%"><strong style="float:left">ContentSettings</strong> &nbsp; <i class="fa fa-cog" style="float:right;padding-top:3px"></i></a><br />' . PHP_EOL;
        $sHtml .= '<div class="gridblockcontentsettings-options" id="gridblockcontentsettings-' . $iBlockId . '">' . PHP_EOL;

        if (is_object($oData) && isset($oData->template)) {
            $sHtml .= '<p><strong>Template</strong></p>' . PHP_EOL;
            $sHtml .= '<ul class="list-group">' . PHP_EOL;
            foreach ($oData->template as $sKey => $oItem) {
                $sLabel = rex_escape($oItem->label) . " (" . rex_escape($sKey) . ")";
                $sValue = rex_escape($oItem->key);
                if ($oItem->value != "") {
                    if (!is_object($oItem->value)) {
                        $sValue .= " (" . rex_escape($oItem->value) . ")";
                    } else {
                        $sValue .= " (" . rex_escape($oItem->value->label) . ")";
                    }
                }
                $sHtml .= '<li class="list-group-item"><div class="row"><div class="col-12 col-lg-6" style="padding:0">' . $sLabel . '</div><div class="col-12 col-lg-6" style="padding:0">' . $sValue . '</div></div></li>' . PHP_EOL;
            }
            $sHtml .= '</ul>' . PHP_EOL;
        }
        for ($iX = 1; $iX <= $iColumns; $iX++) {
            if (is_object($oData) && isset($oData->{"column_" . $iX})) {
                $sColumnLabel = "Spalte " . $iX;
                $iColumn = $iX - 1;
                $sColumnTitle = (string) ($aPreview["columns"][$iColumn]["title"] ?? "");
                if ($sColumnTitle !== "") {
                    $sColumnLabel = "Spalte $iX - " . rex_escape($sColumnTitle);
                }

                $sHtml .= '<p><strong>' . $sColumnLabel . '</strong></p>' . PHP_EOL;
                $sHtml .= '<ul class="list-group">' . PHP_EOL;
                foreach ($oData->{"column_" . $iX} as $sKey => $oItem) {
                    $sLabel = rex_escape($oItem->label) . " (" . rex_escape($sKey) . ")";
                    $sValue = rex_escape($oItem->key);
                    if ($oItem->value != "") {
                        $sValue .= " (" . rex_escape($oItem->value) . ")";
                    }
                    $sHtml .= '<li class="list-group-item"><div class="row"><div class="col-12 col-lg-6" style="padding:0">' . $sLabel . '</div><div class="col-12 col-lg-6" style="padding:0">' . $sValue . '</div></div></li>' . PHP_EOL;
                }
                $sHtml .= '</ul>' . PHP_EOL;
            }
        }
        $sHtml .= '</div>' . PHP_EOL;
        $sHtml .= '<script>' . PHP_EOL;
        $sHtml .= '$( document ).ready(function() {
			$(".gridblockcontentsettings-toggler-' . $iBlockId . '").click(function(){
				var iBlockId = $(this).attr("data-id");
				$(iBlockId).slideToggle();
			});
		})' . PHP_EOL;
        $sHtml .= '</script>' . PHP_EOL;



        return $sHtml;
    }
}

class_alias('Gridblock2Builder_ContentSettings', 'Gridblock2ContentSettings');
