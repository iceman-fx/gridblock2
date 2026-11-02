<?php
/*
	Redaxo-Addon Gridblock²
	Ein-/Ausgabesteuerung der Inhaltsmodule
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class rex_article_content_gridblock2 extends rex_article_content_editor {

    private $values = [];					//array


	public function __construct()
	{
	}
	

	public function getModuleEdit($addModuleID = 0, $colID = 0, $uID = 0, $rexVars = array(), $action = "")
    {	$addModuleID = (int) $addModuleID;
		if (!empty($uID) && !self::isValidUID($uID)) { $uID = ''; }
		rex_gridblock2::isBackend(true);				//set isBackend=true in module edit
	
        $this->setEval(true);
        $this->setMode('edit');
        $this->setFunction('add');

        $MOD = rex_sql::factory();
        $MOD->setQuery('SELECT `key`, `input` FROM '.rex::getTablePrefix().'module WHERE id = "'.$addModuleID.'"');

        if ($MOD->getRows() != 1):
			$slice_content = rex_view::warning(rex_i18n::msg('module_doesnt_exist').' (ID: '.$addModuleID.')');
        else:
			//REX-MODULE-VARS holen
			$rexVars = (empty($rexVars) && isset($_SESSION['gridRexVars'])) ? $_SESSION['gridRexVars'] : $rexVars;
				//REX-MODULE-VARS erweitern
				$rexVars['moduleID'] = $addModuleID;
				$rexVars['moduleKEY'] = $MOD->getValue('key');
		
		
			//VALUES aus kopiertem Gridblock-Slice auslesen
			$cook = rex_var::toArray(rex_article_content_gridblock2::getCookie());
				$copUID = @$cook['uid'];
				$copCOLID = @intval($cook['colid']);
				$copSLID = @intval($cook['sliceid']);
			

			$useCopy = false;
			if ($action == 'copy' && self::checkCopyAvailable($copUID, $copCOLID, $copSLID)):
				$useCopy = true;
				
				rex_sql::setFactoryClass('rex_sql_gridblock2');
				
				$db = rex_sql::factory();
				$db->setQuery("SELECT value".$copCOLID." FROM ".rex::getTable("article_slice")." WHERE id = '".$copSLID."' LIMIT 0,1");
			
				$copValues = rex_var::toArray($db->getValue('value'.$copCOLID));
				$copValues = $copValues[$copUID];
				$this->setValues($copValues, $uID);
			endif;
			
		
			//VALUES aufbereiten (entweder alle aus Redaxo-Slice oder nur aus dem kopierten GB-Slice)
            $values = $this->values;
            foreach ($values as $key => $val):
                if (is_array($val)):
                    $values[$key] = json_encode($val);
                endif;
            endforeach;

			
			//VALUE-Ersetzungen vorbereiten
            $initDataSql = rex_sql::factory();
            $initDataSql
                ->setValues($values, $uID) // @phpstan-ignore arguments.count
                ->setValue('module_id', $addModuleID)
                ->setValue('ctype_id', $this->ctype);


            //MBlock-SQL-Anfrage abzufangen und Antwort fälschen (MBlock lädt sich normalerweise alle Inhalte aus article_slice selbst)
            $factoryClassBefore = rex_sql::getFactoryClass();
            $sliceIdInjected = false;
            rex_sql::setFactoryClass('rex_sql_gridblock2');
            if (rex_request('slice_id', 'int', 0) <= 0):
                $fallbackSliceId = self::getFallbackSliceId();
                if ($fallbackSliceId > 0):
                    $_REQUEST['slice_id'] = $fallbackSliceId;
                    $sliceIdInjected = true;
                endif;
            endif;
            $functionInjected = false;
            if (rex_request('function', 'string', '') === ''):
                $_REQUEST['function'] = 'edit';
                $functionInjected = true;
            endif;

            rex::setProperty('sql_fake_result', $values);
            rex::setProperty('sql_fake_strict', true);
				$moduleInput = rex_gridblock2_var_replacer::replaceModuleVars($MOD->getValue('input'), $rexVars);								//zuerst die RexVars ersetzen
				$moduleInput = $this->replaceVars($initDataSql, $moduleInput);																	//jetzt die restlichen Vars/Values ersetzen (über article_content_base)

				$slice_content = $this->getStreamOutput('module/' . $addModuleID . '/input', $moduleInput);
            rex::removeProperty('sql_fake_result');
            rex::removeProperty('sql_fake_strict');

            if ($sliceIdInjected):
                unset($_REQUEST['slice_id']);
            endif;
            if ($functionInjected):
                unset($_REQUEST['function']);
            endif;
            rex_sql::setFactoryClass($factoryClassBefore);

			
			//Inhalte ersetzen
			$slice_content = rex_gridblock2_var_replacer::replaceVars($colID, $slice_content, $uID, $rexVars);									//alle Inputs mit UID + COLID erweitern
		endif;
		
		
		//Disabled auswerten																													//Hinweis: Modulcontent muss wegen Speicherung immer vorhanden sein (display:none) !!!
		$disabled = ($addModuleID > 0 && !rex::getUser()->getComplexPerm('modules')->hasPerm($addModuleID)) ? true : false;

		$op = ($disabled) ? '<div class="gridblock-module-disabled">'.rex_i18n::msg('a2034_mod_noeditpermission').'</div>' : '';

		$contentStyle = ($disabled) ? ' style="display:none;"' : '';

		//Modul-Edit ausgeben
		$op .= '<div id="gridblockModuleContent'.$uID.'" class="column-input"'.$contentStyle.'>';
			$op .= $slice_content;
		$op .= '</div>';
		
        return $op;
    }

    public static function createUID()
	{	$uid = 'GBS'.sha1(uniqid().str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'));				//GBS-Kürzel ist wichtig für Suche/Ersetzen

		return $uid;
	}


	public static function isValidUID($uID)
	{	return is_string($uID) && $uID !== '' && strlen($uID) <= 128 && preg_match('/^[A-Za-z0-9_-]+$/', $uID) === 1;
	}
	

    private static function getFallbackSliceId(): int
    {	static $cache = null;
        if ($cache === null):
            $sql = rex_sql::factory();
            $sql->setQuery('SELECT id FROM '.rex::getTable('article_slice').' ORDER BY id LIMIT 1');
            $cache = ($sql->getRows() > 0) ? (int) $sql->getValue('id') : 0;
        endif;

        return $cache;
    }


    public function setValues($values, $uID = "")
    {	if (empty($values)):
            $this->values = rex_gridblock2::getBlankValues();
            return;
        endif;
		
        $this->values = [];
		
        if (isset($values['VALUE'])):
            foreach ($values['VALUE'] as $i => $value):
                $i = preg_replace('/_MBLOCK$/', '', (string) $i);
                $this->values['value'.$i] = $value;
            endforeach;
        endif;
		
        if (isset($values['MEDIA'])):
            foreach ($values['MEDIA'] as $i => $value):
                $this->values['media'.$i] = $value;
            endforeach;
        endif;
		
        if (isset($values['MEDIALIST'])):
            foreach ($values['MEDIALIST'] as $i => $value):
                $this->values['medialist'.$i] = $value;
            endforeach;
        endif;
		
        if (isset($values['LINK'])):
            foreach ($values['LINK'] as $i => $value):
                $this->values['link'.$i] = $value;
            endforeach;
        endif;
		
        if (isset($values['LINKLIST'])):
            foreach ($values['LINKLIST'] as $i => $value):
                $this->values['linklist'.$i] = $value;
            endforeach;
        endif;
		
    }


    public $gbBlockVars = null;

    public function getModuleOutput($moduleID, $uID, $rexVars = array(), $blockSettings = array())
	{	$moduleID = (int) $moduleID;
		$this->setEval(true);
        $this->setMode('edit');
		
		$MOD = rex_sql::factory();
		$MOD->setQuery('SELECT `key`, `output` FROM '.rex::getTablePrefix().'module WHERE id="'.$moduleID.'"');
		
		
		if ($MOD->getRows() != 1):
			$slice_content = (rex::isBackend()) ? rex_view::warning(rex_i18n::msg('module_doesnt_exist').' (ID: '.$moduleID.')') : '';
		else:
			foreach($this->values as $i => $value):
				if (is_array($value)):
					$this->values[$i] = json_encode($value);
				endif;
			endforeach;
			
			//aktuellen Artikel als Basis setzen (für REX_xxx[])
			$this->setClang(rex_clang::getCurrentId());
			$this->setArticleId(rex_article::getCurrentId());
			
			
						
			//REX-MODULE-VARS erweitern
			$rexVars['moduleID'] = $moduleID;
			$rexVars['moduleKEY'] = $MOD->getValue('key');			
			
			//VALUE-Ersetzungen vorbereiten
			$initDataSql = rex_sql::factory();
			$initDataSql
				->setValues($this->values, $uID) // @phpstan-ignore arguments.count
				->setValue('module_id', $moduleID)
				->setValue('ctype_id', $this->ctype);

				
			//Variablen ersetzen
			$op = $MOD->getValue('output');
				$op = rex_gridblock2_var_replacer::replaceModuleVars($op, $rexVars);																//zuerst die RexVars ersetzen
				
			$op = $this->replaceVars($initDataSql, $op);
			$gbPrefix = '';
			if (Gridblock2Builder_ContentSettings::hasModulesScope() && !preg_match('/^\s*<\?php\s+(declare|namespace)\b/i', $op)):
				$gbCs = new stdClass();
				Gridblock2Builder_Tree::attachBlockSettings($gbCs, (array) $blockSettings, ['kind' => 'redaxo', 'ids' => [(string) $moduleID, (string) $MOD->getValue('key')]]);
				$this->gbBlockVars = (object) ['cs' => $gbCs];
				$gbPrefix = '<?php $_gb = $this->gbBlockVars; ?>';
			endif;

			$slice_content = $this->getStreamOutput('module/'.$moduleID.'/output', $gbPrefix.$op);
		endif;
		
		unset($MOD);
		
        return $slice_content;
    }


	public static function checkCopyAvailable($copUID = "", $copCOLID = 0, $copSLID = 0)
	{	$return = false;
		$copCOLID = (int) $copCOLID;
		$copSLID = (int) $copSLID;
	
		if (!empty($copUID) && $copCOLID > 0 && $copSLID > 0):
			$db = rex_sql::factory();
			$db->setQuery("SELECT value".$copCOLID." FROM ".rex::getTable("article_slice")." WHERE id = '".$copSLID."' LIMIT 0,1");
			
			if ($db->getRows() > 0):
				$copValues = rex_var::toArray($db->getValue('value'.$copCOLID));
				$return = (isset($copValues[$copUID])) ? true : false;
			endif;
		endif;
		
		return $return;
	}
	
	
	public static function getCookieName()
	{	global $a2034_mypage;
		return 'rex_'.$a2034_mypage.'_cutncopy';
	}

	public static function getCookie($key = "")
	{	$cookie = @json_decode(rex_request::cookie(self::getCookieName(), 'string', ''), true);
		
		if (!empty($key) && is_string($key)):
			if (isset($cookie[$key])):
				return $cookie[$key];
			endif;
			
			return "";
		endif;
		
		return json_encode($cookie);
	}
	
}
?>