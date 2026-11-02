<?php
/*
	Redaxo-Addon Gridblock²
	Grid-Basisklasse
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class rex_gridblock2 {

	private static $isBackend = false;		//bool
	private static $isBuilderEdit = false;		//bool
	private static $isBuilderPreview = false;		//bool
	private static $identifier = '/* GRID_MODULE_IDENTIFIER | DONT REMOVE */';
	
	public $sliceID = 0;					//int
	public $artID = 0;						//int
	public $tmplID = 0;						//int
	public $clangID = 0;					//int
	public $ctypeID = 0;					//int -> ist aktuell nicht auslesbar !!!
	
	public $rexVars = [];					//array -> ctypeID ist aktuell nicht verfügbar !!!
	
    public $ingoredModules = [];			//array
	public $allowedModules = [];			//array
    private $values = [];					//array

	public $maxCols = 12;					//int
	

    public function getModules()
	{
		//Standard-Modulauswahl holen
		$config = rex_addon::get('gridblock2')->getConfig('config');
		if (!empty($config['modules'])):
			if (@$config['modulesmode'] == 'ignore'):
				$this->ignoreModule(explode("#", $config['modules']));
			else:
				$this->allowModule(explode("#", $config['modules']));
			endif;
		endif;
		
		//Modulauswahl setzen
		$where_im = (count($this->ingoredModules) > 0 ? 'id not in ('.join(',', array_map('intval', $this->ingoredModules)).')' : '');
		$where_am = (count($this->allowedModules) > 0 ? 'id in ('.join(',', array_map('intval', $this->allowedModules)).')' : '');
			
		$where = "";	
			$where .= (!empty($where_im)) ? " AND $where_im" : '';
			$where .= (!empty($where_am)) ? " AND $where_am" : '';
		$where = (!empty($where) || @$config['modulesmode'] == 'ignore') ? 'input NOT LIKE "%/* GRID_MODULE_IDENTIFIER | DONT REMOVE */%" '.$where : '0';
		
		//Module aus DB holen und zwischenspeichern
		$sql = 'SELECT id, name, output, input FROM '.rex::getTable('module').' WHERE '.$where.' ORDER BY name, id';
		$db = rex_sql::factory();
		$db->setQuery($sql); // @phpstan-ignore rexstan.rexSqlInjection

        $modules = [];
        while ($db->hasNext()):
            $modules[$db->getValue('id')] = [
                'name' => $db->getValue('name'),
                'output' => $db->getValue('output'),
                'input' => $db->getValue('input'),
            ];
			
            $db->next();
        endwhile;
		
		if (rex::isBackend()) { $_SESSION['gridAllowedModules'] = $modules; }
        return $modules;
    }
	

    public function ignoreModule($moduleID)
	{
		$this->checkModule($moduleID, 'ignore');
    }


    public function allowModule($moduleID)
	{
		$this->checkModule($moduleID, 'allow');
    }


    private function checkModule($moduleID, $type = 'allow')
	{
		if (is_array($moduleID)):
			foreach ($moduleID as $mid):
				if (!empty($mid)):
					if ($type == 'ignore'):
						$this->ingoredModules[] = (int) $mid;
					else:
						$this->allowedModules[] = (int) $mid;
					endif;
				endif;
			endforeach;
		
		elseif (is_int($moduleID)):
			if ($type == 'ignore'):
				$this->ingoredModules[] = (int) $moduleID;
			else:
				$this->allowedModules[] = (int) $moduleID;
			endif;
		endif;
    }
	

    public function getSliceValues($sliceID)
    {
		$sliceID = intval($sliceID);
		$this->rexVars['sliceID'] = (int) $sliceID;
		
		$art = rex_article::get(rex_article::getCurrentId());
		if ($art):
			$this->rexVars['artID'] = (int) $art->getId();
			$this->rexVars['tmplID'] = (int) $art->getTemplateId();
			$this->rexVars['clangID'] = (int) $art->getClangId();
			$this->rexVars['ctypeID'] = rex_request('ctype', 'int');
			$this->rexVars['revision'] = 0;
			if (rex::isBackend() && rex::getProperty('login')):
				$this->rexVars['revision'] = (int) @rex::getProperty('login')->getSessionVar('rex_version_article')[rex_article::getCurrentId()];
			endif;
		else:
			$this->rexVars['artID'] = $this->rexVars['tmplID'] = $this->rexVars['clangID'] = $this->rexVars['revision'] = 0;
		endif;
		
		$_SESSION['gridRexVars'] = $this->rexVars;
		
        $sql = rex_sql::factory();
        $sql->prepareQuery('SELECT value1, value2, value3, value4, value5, value6, value7, value8, value9, value10, value11, value12, value13, value14, value15, value16, value17, value18, value19, value20 FROM '.rex::getTable('article_slice').' WHERE id = ?');
        $sql->execute([$sliceID]);
		
        if ($sql->hasNext()):
            $this->values = [];
			
            for ($i = 1; $i <= 20; ++$i):
                $this->values[$i] = $sql->getValue('value'.$i);
            endfor;
        endif;
    }


    public function getModuleOutput()
	{
        $sliceID = (int) ($this->rexVars['sliceID'] ?? 0);
        $sliceValues = Gridblock2Builder_Tree::loadSliceValues($sliceID);

        if (Gridblock2Builder_Tree::hasNewFormat($sliceValues)) {
            $structure = Gridblock2Builder_Tree::loadStructure($sliceValues);
            $moduleValues = Gridblock2Builder_Tree::loadModuleValues($sliceValues);

            return Gridblock2Builder_Tree::renderOutput($structure, $moduleValues, $this->rexVars);
        }

        $fragment = new rex_fragment();
        $fragment->setVar('maxCols', $this->maxCols, false);
		$fragment->setVar('values', $this->values, false);
		$fragment->setVar('rexVars', $this->rexVars, false);

        return $fragment->parse('gridblock2/module_output.php');
    }


    public function getModuleInput()
	{
        $this->getModules();

        $sliceID = (int) ($this->rexVars['sliceID'] ?? 0);
        $sliceValues = Gridblock2Builder_Tree::loadSliceValues($sliceID);
        $structure = Gridblock2Builder_Tree::loadStructure($sliceValues);
        $moduleValues = Gridblock2Builder_Tree::loadModuleValues($sliceValues);

        return Gridblock2Builder_Tree::renderRoot($structure, $moduleValues, $this->rexVars);
    }


    public static function getBlankValues()
	{
        $values = [];
		
        for ($i = 1; $i <= 20; ++$i):
            $values['value'.$i] = '';
        endfor;
		
        for ($i = 1; $i <= 10; ++$i):
            $values['media'.$i] = '';
        endfor;
		
        for ($i = 1; $i <= 10; ++$i):
            $values['medialist'.$i] = '';
        endfor;
		
        for ($i = 1; $i <= 10; ++$i):
            $values['link'.$i] = '';
        endfor;
		
        for ($i = 1; $i <= 10; ++$i):
            $values['linklist'.$i] = '';
        endfor;
		
        return $values;
    }


    public static function getConfig($sKey=null)
	{
        $aConfig = rex_addon::get('gridblock2')->getConfig('config');
        if ($sKey != "") {
            return $aConfig[$sKey] ?? null;
        }
        return $aConfig;
    }
	

	public static function getSettings()
	{
		//alle Settings aus Property auslesen
		$settings = rex_addon::get('gridblock2')->getProperty('REX_GRID_SETTINGS');
		
		return $settings;
	}
	

	//prüfen ob im Editmode des Gridblock (BE)
	public static function isBackend($override = false)
	{
		if ($override) { self::$isBackend = true; }
		return (rex::isBackend() && self::$isBackend) ? true : false;
	}


	public static function isBuilderEdit($override = false)
	{
		if ($override) { self::$isBuilderEdit = true; }
		return (rex::isBackend() && self::$isBuilderEdit) ? true : false;
	}


	public static function isBuilderPreview($override = false)
	{
		if ($override) { self::$isBuilderPreview = true; }
		return (rex::isBackend() && self::$isBuilderPreview) ? true : false;
	}


	//prüfen ob Modul das Gridblock-Modul ist
	public static function isGridblockModule($moduleID = 0)
	{
		$return = false;
		$id = intval($moduleID);
		
		if ($id > 0):
			$db = rex_sql::factory();
			$db->setQuery('SELECT id FROM '.rex::getTable('module').' WHERE id = "'.$id.'" AND (input LIKE "%'.self::$identifier.'%" OR output LIKE "%'.self::$identifier.'%")'); // @phpstan-ignore rexstan.rexSqlInjection

			$return = ($db->hasNext()) ? true : false;
		endif;
		
		return $return;
	}
	

	//alle Gridblock-Module holen
	public static function getGridblockModules()
	{
		$db = rex_sql::factory();
		$db->setQuery('SELECT id, `key`, name FROM '.rex::getTable('module').' WHERE (input LIKE "%'.self::$identifier.'%" OR output LIKE "%'.self::$identifier.'%")'); // @phpstan-ignore rexstan.rexSqlInjection

		return $db->getArray();
	}


	private static $legacyIdentifierMap = [
		'rex_api_gridblock_builderAddElement' => 'rex_api_gridblock2_builderAddElement',
		'rex_api_gridblock_builderRenderColumnSlot' => 'rex_api_gridblock2_builderRenderColumnSlot',
		'rex_api_gridblock_loadCustomElementPreview' => 'rex_api_gridblock2_loadCustomElementPreview',
		'rex_api_gridblock_loadElementPreview' => 'rex_api_gridblock2_loadElementPreview',
		'rex_api_gridblock_loadPreview' => 'rex_api_gridblock2_loadPreview',
		'rex_article_content_gridblock' => 'rex_article_content_gridblock2',
		'rex_sql_gridblock' => 'rex_sql_gridblock2',
		'rex_gridblock_helper' => 'rex_gridblock2_helper',
		'rex_gridblock_var_replacer' => 'rex_gridblock2_var_replacer',
		'rex_gridblock' => 'rex_gridblock2',
		'GridblockBuilder_ContentSettings' => 'Gridblock2Builder_ContentSettings',
		'GridblockBuilder_Preview' => 'Gridblock2Builder_Preview',
		'GridblockBuilder_Tree' => 'Gridblock2Builder_Tree',
		'fmGridblockElementHelper' => 'fmGridblock2ElementHelper',
		'getGridblockContentSettingsForm' => 'getGridblock2ContentSettingsForm',
		'parseGridblockContentSettings' => 'parseGridblock2ContentSettings',
		'GridblockContentSettings' => 'Gridblock2ContentSettings',
	];


	public static function replaceLegacyIdentifiers(string $code): string
	{
		foreach (self::$legacyIdentifierMap as $legacyName => $newName) {
			$code = preg_replace('/\b'.preg_quote($legacyName, '/').'\b/', $newName, $code);
		}
		return str_replace("rex_addon::get('gridblock')", "rex_addon::get('gridblock2')", $code);
	}

}
?>