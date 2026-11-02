<?php
/*
	Redaxo-Addon Gridblock²
	Helper-Funktionen für contentsettings
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class rex_gridblock2_helper
{

	public static function injectSliceFullscreenButton($ep)
	{	$content = (string) $ep->getSubject();

		$config = rex_gridblock2::getConfig();
		if (is_array($config) && array_key_exists('gb_builder_slicelist_fullscreen', $config) && $config['gb_builder_slicelist_fullscreen'] !== 'checked') { return $content; }

		$moduleId = (int) $ep->getParam('module_id');
		$user = rex::getUser();
		if (!$user || !rex_gridblock2::isGridblockModule($moduleId) || !$user->getComplexPerm('modules')->hasPerm($moduleId)) { return $content; }

		$articleId = (int) $ep->getParam('article_id');
		$clang = (int) $ep->getParam('clang');
		$ctype = (int) $ep->getParam('ctype');
		$sliceId = (int) $ep->getParam('slice_id');
		if (!Gridblock2Builder_Tree::isModuleAllowedByTemplate($moduleId, ['artID' => $articleId, 'clangID' => $clang, 'ctypeID' => $ctype])) { return $content; }

		$url = rex_url::backendPage('content/edit', ['article_id' => $articleId, 'clang' => $clang, 'ctype' => $ctype, 'slice_id' => $sliceId, 'function' => 'edit']).'#slice'.$sliceId;
		$label = rex_i18n::msg('a2034_slicelist_fullscreen');

		return '<div class="gb-slicelist-fullscreen-wrap"><a href="'.$url.'" class="btn btn-primary btn-xs gb-slicelist-fullscreen">'.$label.'</a>'.$content.'</div>';
	}


	//Ajax-Inhalte holen
	public static function bindAjax($ep)
	{	$op = $ep->getSubject();
		$op = preg_replace('/(.*<\!-- ###AJAX### -->)(.*)(<\!-- ###\/AJAX### -->.*)/s', '$2', $op);
		
		return $op;
	}


	//Timebuster
	public static function getTimebuster($filepath, $addfrontend=true)
	{	$return = $abspath = $filepath;
	
		$path = rex_path::frontend();
			$path = str_replace('\\', '/', $path);
		$abspath = ($addfrontend) ? str_replace('//', '/', $path.preg_replace('#^../#', '/', $filepath)) : $abspath;
		
		if (file_exists($abspath)):
			$stamp = filemtime($abspath);											//Letzte Änderungszeit
				$stamp = (empty($stamp)) ? md5_file($abspath) : $stamp;				//Dateihash
			
			$return = $filepath.'?v='.$stamp;
		endif;
		
		return $return;
	}


	public static function duplicateName($str, $depth = 0)
	{	if (!empty($str) && $depth < 20):
			$db = rex_sql::factory();
			$db->setQuery("SELECT id, title FROM ".rex::getTable('2034_gridtemplates')." WHERE title = ".rex_sql::factory()->escape((string) $str));

			if ($db->getRows() > 0):
				$str = $str.rex_i18n::msg('a2034_copiedname').' '.($depth + 1);
				$str = self::duplicateName($str, $depth + 1);
			endif;
		endif;

		return $str;
	}


	public static function generatePreview($preview, $id = 0, $columns = 0, $title = "", $desc = "", $selected = false, $setid = false)
	{	global $a2034_mypage;

		//Vorgaben einlesen/setzen
		$id = intval($id);
		$columns = intval($columns);
		
		$config = rex_config::get($a2034_mypage, 'config');						//Addon-Konfig einladen
		
		
		$op = '';
		if ($id > 0 && $columns > 0):
			$selected = ($selected) ? 'selected' : '';
			$setid = ($setid) ? 'id="gridblock-template'.$id.'"' : '';
			
			$data = $setid.' data-id="'.$id.'" data-columns="'.$columns.'"';

			if (!empty($preview) && is_array($preview)):
				$count = intval($preview['totalcolumns']);
				$cols = $preview['columns'];
				
				$preview = ''; $colnames = array();
				for ($p=0; $p < $count; $p++):
					$w = intval($cols[$p]['width']);
					$t = $cols[$p]['title'];
						array_push($colnames, $t);
						
					$t = (@$config['hidepreviewcoltitles'] != 'checked') ? $t : '';
					$preview .= '<div style="width: '.$w.'%"><span>'.rex_escape($t).'</span></div>';
				endfor;
				$op = (!empty($preview)) ? '<div class="gridblock-preview '.$selected.'" title="'.rex_escape($desc).'" '.$data.' data-colnames="'.urlencode(json_encode($colnames)).'">'.$preview.'</div>' : '';

			elseif (!empty($title)):
				$op = '<div class="gridblock-preview gridblock-previewtext '.$selected.'" title="'.rex_escape($desc).'" '.$data.' data-colnames=""><span>'.rex_escape($title).'</span></div>';
			endif;
		endif;
		
		return $op;
	}


	public static function maskChar($str)
	{	if (!empty($str)):
            if (is_array($str)):
                $str = self::maskArray($str);
            else:
                $str = trim(rex_escape((string) $str));
            endif;
		endif;

		return $str;
	}


	public static function maskArray($arr)
	{	//maskiert Array-Werte mit maskChar()
		if (is_array($arr)):
			$arr = array_map(function($str) { return self::maskChar($str); }, $arr);
		endif;
		
		return $arr;
	}


	public static function maskSingleQuote($str)
	{	//ersetzt Single-Quotes: '
		return (!empty($str)) ? str_replace("'", "&#039;", $str) : $str;
	}


	public static function maskDoubleQuote($str)
	{	//ersetzt Double-Quotes: "
		return (!empty($str)) ? str_replace('"', "&quot;", $str) : $str;
	}


	public static function maskSql($str)
	{
		//nur noch für Abwärtskompatiblität
		if (empty($str)):
			return $str;
		endif;
		$quoted = rex_sql::factory()->escape((string) $str);
		return substr($quoted, 1, -1);
	}


	public static function unmaskQuotes($str)
	{	//de-maskiert folgende Anführungszeichen: " '
		return (!empty($str)) ? str_replace(array("&quot;", "&#039;"), array('"', "'"), $str) : $str;
	}


	public static function revChar($str)
	{	//de-maskiert folgende Sonderzeichen: & " < > '
		if (!empty($str)):
			$chars = array("&amp;amp;quot;"=>'"', "&amp;quot;"=>'"', "&amp;"=>"&", "&lt;"=>"<", "&gt;"=>">", "&quot;"=>'"', "&#039;"=>"'");
			foreach ($chars as $key => $value):
				$str = str_replace($key, $value, $str);
			endforeach;
		endif;
		
		return $str;
	}


	public static function blockTags($str, $mask = false)
	{	//entfernt bekannte Tags (PHP, JS, HTML)
		if ($str != ""):
			$str = str_replace("\xc2\xa0", ' ', $str);														//&nbsp; ersetzen in normales WhiteSpace (UTF8)
			$str = strip_tags($str, '<br>');
				$phps = array("/<\?php/i", "/<\?/i", "/<%/i", "/<script language=\"php\">/i", "/<script language='php'>/i", "/\?>/i", "/%>/i");
					foreach ($phps as $key):
						$str = preg_replace($key, " ", $str);
					endforeach;
				$js = array("/<script.*>/i", "/<\/script>/i");
					foreach ($js as $key):
						$str = preg_replace($key, " ", $str);
					endforeach;
			$str = preg_replace("/\h+/", " ", $str);
			$str = trim($str);

			//Zeichen maskieren
			$str = ($mask) ? self::maskChar($str) : $str;
		endif;

		return $str;
	}


	public static function textOnly($str, $nobreak = true, $mask = false)
	{	/*
		entfernt HTML-Tags, Zeilenumbrüche und Tabstops
		Aufruf: textOnly($str);																			//gibt den String als Text zurück & Umbrüche wurden entfernt
				textOnly($str, false, true);															//entfernt keine Umbrüche, maskiert die Ausgabe zusätzlich
		*/        
		if ($str != ""):
			$str = str_replace(array("\xc2\xa0", "&nbsp;"), " ", $str);									//&nbsp; ersetzen in normales WhiteSpace (UTF8, Entity)
			$str = str_replace("\t", ' ', $str);														//Tabstop (\t) ersetzen in normales WhiteSpace
            $str = preg_replace('/\h+/', " ", $str);                                                    //Mehrfache (horizontale) Leerzeichen entfernen
			$str = self::blockTags($str);																//Script-Tags entfernen
				
                //Zeilenumbrüche entfernen
				if ($nobreak):
					$str = nl2br($str);
					$str = str_replace(array("<br>", "<br/>", "<br />"), " ", $str);
					$str = strip_tags($str);
					$str = str_replace(array("\r\n", "\n\r", "\n", "\r"), " ", $str);
                else:
                    $str = str_replace(array("<br>", "<br/>", "<br />"), "\n", $str);
                    $str = strip_tags($str);
                    $str = str_replace(array("\r\n", "\n\r", "\n", "\r"), "\n", $str);                  //alle Arten von Umbrüchen auf \n reduzieren
                    $str = preg_replace("/\n\h+\n/i", "\n\n", $str);                                    //Umbruch-Leerzeichen-Umbruch ersetzen
                    $str = preg_replace("/\n{3,}/i", "\n\n", $str);                                     //3+ Umbrüche auf 2 reduzieren
				endif;
                
				//Zeichen maskieren
				$str = ($mask) ? self::maskChar($str) : $str;
                
            $str = preg_replace('/\h+/', " ", $str);                                                    //Mehrfache (horizontale) Leerzeichen entfernen
			$str = trim($str);
		endif;
        
		return $str;
	}


	public static function parseMdiValue(?string $raw, ?string $outputdevice = null, string $outputtype = 'css'): array|string|null
	{
		//v1.0.1
		
		$result = [];

		foreach (explode('|', (string) $raw) as $part):
			$segments = explode(':', $part);
			if (count($segments) < 2) continue;

			$device = $segments[0]; 				// "desktop", "tablet", "mobile"
			$value  = $segments[1]; 				// "80"
			$unit   = $segments[2] ?? 'px'; 		// "px", "rem", "%" …

			$result[$device] = [
				'value' => $value,
				'unit'  => $unit,
				'css'   => ($value === '' || $value === null) ? '' : $value.$unit
			];
		endforeach;

		if ($outputdevice !== null):
			$key = (is_string($outputtype) && in_array($outputtype, ['value', 'unit', 'css'])) ? $outputtype : 'css';
			return $result[$outputdevice][$key] ?? null;
		endif;
		
		return $result;
	}
	
	
    public static function getTemplateClasses($cs, $options = array(), $with = false)
	{	/*
		Liest die Werte der definierten Template-Settings aus und gibt diese als CSS-Klassen-Aufzählung aus.
		
		Aufruf:
		echo rex_gridblock2_helper::getTemplateClasses($cs, array('gutter', 'padding', 'width', 'class'));
		echo rex_gridblock2_helper::getTemplateClasses($cs, array('gutter', 'padding', 'width', 'class'), true|false);
		
		$cs 			= $contentsettings
		array() 		= Array der gewünschten Optionsfelder aus $cs (Definition: 'Feldname')
		true|false		= Ausgabe mit oder ohne umschließendes class="" (default: false)
		*/
		
        return self::getClasses($cs, $col=0, $options, 'template', $with);
    }
	
	
    public static function getColumnClasses($cs, $col, $options = array(), $with = false)
	{	/*
		Liest die Werte der definierten Spalten-Settings aus und gibt diese als CSS-Klassen-Aufzählung aus.
		
		Aufruf:
		echo rex_gridblock2_helper::getColumnClasses($cs, 1, array('gutter', 'padding', 'width', 'class'));
		echo rex_gridblock2_helper::getColumnClasses($cs, 2, array('gutter', 'padding', 'width', 'class'), true|false);
		
		$cs 			= $contentsettings
		1...12			= Spaltennummer 1...12
		array() 		= Array der gewünschten Optionsfelder aus $cs (Definition: 'Feldname')
		true|false		= Ausgabe mit oder ohne umschließendes class="" (default: false)
		*/
		
        return self::getClasses($cs, $col, $options, 'column', $with);
    }


    public static function getColumnsRowClasses($cs, $options = array(), $with = false)
	{	/*
		Liest die Werte der definierten Spalten-ZEILEN-Settings (eigener "template"-Scope, siehe
		Contentsettings) aus und gibt diese als CSS-Klassen-Aufzählung aus - identisch zu getTemplateClasses(),
		eigener Name fuer die Zeilen-spezifischen Optionsfelder (z.B. colsGap/colsWidth).

		Aufruf:
		echo rex_gridblock2_helper::getColumnsRowClasses($cs, array('colsGap', 'colsWidth'));

		$cs 			= $contentsettings
		array() 		= Array der gewünschten Optionsfelder aus $cs (Definition: 'Feldname')
		true|false		= Ausgabe mit oder ohne umschließendes class="" (default: false)
		*/

        return self::getClasses($cs, $col=0, $options, 'template', $with);
    }


    public static function getSectionClasses($cs, $options = array(), $with = false)
	{	/*
		Liest die Werte der definierten Sektions-Settings (eigener "section"-Scope, siehe Contentsettings)
		aus und gibt diese als CSS-Klassen-Aufzählung aus - analog getTemplateClasses(), aber für die
		Sektion (eigenständiger, optionaler Container um beliebigen Inhalt) statt für die Spalten-Zeile.

		Aufruf:
		echo rex_gridblock2_helper::getSectionClasses($cs, array('bgColor', 'paddingTop'));

		$cs 			= $contentsettings
		array() 		= Array der gewünschten Optionsfelder aus $cs (Definition: 'Feldname')
		true|false		= Ausgabe mit oder ohne umschließendes class="" (default: false)
		*/

        return self::getClasses($cs, $col=0, $options, 'section', $with);
    }


    private static function getClasses($cs, $col = 0, $options = array(), $type = "", $with = false)
	{
		$op = "";
		$type = ($type == 'column') ? 'column_'.intval($col) : $type;
		
		if (!empty($options) && !empty($cs)):
			foreach ($options as $option):
				$onlyClass = false;
				if (strpos($option, '|') !== false):
					$tmp = explode('|', $option, 2);
					$option = $tmp[0];
					$onlyClass = ($tmp[1] === 'class');
				endif;
				$cso = @$cs->$type->$option;
				if ($onlyClass && !Gridblock2Palette::isClassValue((string) $cso)):
					continue;
				endif;
				$op .= (!empty($cso)) ? htmlspecialchars((string) $cso, ENT_QUOTES).' ' : '';
			endforeach;

			$op = ($with && !empty($op)) ? 'class="'.trim($op).'"' : $op;
		endif;
		
        return trim($op);
    }
	
	
    public static function getTemplateStyles($cs, $options = array(), $with = false)
	{	/*
		Liest die Werte der definierten Template-Settings aus und gibt diese als Style-Aufzählung aus.

		Aufruf:
		echo rex_gridblock2_helper::getTemplateStyles($cs, array('background-image'=>'bgimage', 'background-color'=>'bgcolor'));
		echo rex_gridblock2_helper::getTemplateStyles($cs, array('background-image'=>'bgimage', 'background-color'=>'bgcolor'), true|false);
		echo rex_gridblock2_helper::getTemplateStyles($cs, array('min-height|px'=>'minheight'));
		echo rex_gridblock2_helper::getTemplateStyles($cs, array('min-height|px'=>'minheight|empty'));
		
		$cs 			= $contentsettings
		array() 		= Array der gewünschten Optionsfelder aus $cs (Definition: 'CSS-Attribut'=>'Feldname' oder 'CSS-Attribut|Einheit'=>'Feldname|Prüfroutine')
		true|false		= Ausgabe mit oder ohne umschließendes style="" (default: false)
		
		Mögliche Prüfroutinen = empty
		*/
		
        return self::getStyles($cs, $col=0, $options, 'template', $with);
    }


    public static function getSectionStyles($cs, $options = array(), $with = false)
	{	/*
		Liest die Werte der definierten Sektions-Settings (eigener "section"-Scope) aus und gibt diese als
		Style-Aufzählung aus - analog getTemplateStyles(), aber für die Sektion statt für die Spalten-Zeile.

		Aufruf:
		echo rex_gridblock2_helper::getSectionStyles($cs, array('background-color'=>'bgcolor'));

		$cs 			= $contentsettings
		array() 		= Array der gewünschten Optionsfelder aus $cs (Definition: 'CSS-Attribut'=>'Feldname' oder 'CSS-Attribut|Einheit'=>'Feldname|Prüfroutine')
		true|false		= Ausgabe mit oder ohne umschließendes style="" (default: false)

		Mögliche Prüfroutinen = empty
		*/

        return self::getStyles($cs, $col=0, $options, 'section', $with);
    }
	
	
    public static function getColumnStyles($cs, $col, $options = array(), $with = false)
	{	/*
		Liest die Werte der definierten Spalten-Settings aus und gibt diese als Style-Aufzählung aus.
		
		Aufruf:
		echo rex_gridblock2_helper::getColumnStyles($cs, 1, array('background-image'=>'bgimage', 'background-color'=>'bgcolor'));
		echo rex_gridblock2_helper::getColumnStyles($cs, 2, array('background-image'=>'bgimage', 'background-color'=>'bgcolor'), true|false);
		echo rex_gridblock2_helper::getColumnStyles($cs, 1, array('min-height|px'=>'minheight'));
		echo rex_gridblock2_helper::getColumnStyles($cs, 2, array('min-height|px'=>'minheight|empty'));
		echo rex_gridblock2_helper::getColumnStyles($cs, 2, array('nokey'=>'cssstyle|empty'));
		
		$cs 			= $contentsettings
		1...12			= Spaltennummer 1...12
		array() 		= Array der gewünschten Optionsfelder aus $cs (Definition: 'CSS-Attribut'=>'Feldname' oder 'CSS-Attribut|Einheit'=>'Feldname|Prüfroutine')
						  Mit dem 'nokey'-Schlüsselwort wird nur der Feldname (optional mit der Einheit) zurückgegeben.
		true|false		= Ausgabe mit oder ohne umschließendes style="" (default: false)
		
		Mögliche Prüfroutinen = empty
		*/
		
        return self::getStyles($cs, $col, $options, 'column', $with);
    }
	

    private static function getStyles($cs, $col = 0, $options = array(), $type = "", $with = false)
	{
		$op = "";
		$type = ($type == 'column') ? 'column_'.intval($col) : $type;
		
		if (!empty($options) && !empty($cs)):
			foreach ($options as $key=>$val):
			
				//Prüfroutine extrahieren
				$check = "";
				if (!empty($val) && strpos($val, "|") !== false):
					$tmp = explode("|", $val);
					$val = $tmp[0];
					$check = $tmp[1];
				endif;
			
				//gespeicherten Wert aus $cs holen
				$val = @$cs->$type->$val;
								
				//Prüfroutinen ausführen				
				if ($check == 'empty'):
					if (empty($val)) { continue; }
				endif;
				
				$autoBackground = false;
				if (strtolower($key) == 'background-auto'):
					if (isset($val) && Gridblock2Palette::isClassValue((string) $val)) { continue; }
					$autoBackground = true;
					$key = (isset($val) && stripos((string) $val, 'gradient(') !== false) ? 'background-image' : 'background-color';
				endif;

				//Style-Definition erstellen
				if (!empty($key) && isset($val) && trim($val) != ""):
					//Einheit extrahieren
					$unit = '';
						if (strpos($key, "|") !== false):
							$tmp = explode("|", $key);
							$key = $tmp[0];
							$unit = $tmp[1];
						endif;
				
					$val = (strtolower($key) == 'background-image' && !$autoBackground) ? 'url(/media/'.$val.')' : $val;
					$val = htmlspecialchars((string) $val, ENT_QUOTES);
					$op .= ($key != 'nokey') ? $key.': '.$val.$unit.'; ' : $val.$unit;
				endif;
				
			endforeach;
			
			$op = ($with && !empty($op)) ? 'style="'.trim($op).'"' : $op;
		endif;
		
        return trim($op);
    }
	
	
    public static function getTemplateData($cs, $options = array())
	{	/*
		Liest die Werte der definierten Template-Settings aus und gibt diese als Data-Attribute aus.

		Aufruf:
		echo rex_gridblock2_helper::getTemplateData($cs, array('data-aos'=>'aoseffect', 'data-aos-anchor'=>'aosanchor'));
		echo rex_gridblock2_helper::getTemplateData($cs, array('data-aos'=>'aoseffect|empty'));
		
		$cs 			= $contentsettings
		array() 		= Array der gewünschten Optionsfelder aus $cs (Definition: 'Data-Attribut'=>'Feldname' oder 'Data-Attribut'=>'Feldname|Prüfroutine')
		
		Mögliche Prüfroutinen = empty
		*/
		
        return self::getDatas($cs, $col=0, $options, 'template');
    }


    public static function getSectionData($cs, $options = array())
	{	/*
		Liest die Werte der definierten Sektions-Settings (eigener "section"-Scope) aus und gibt diese als
		Data-Attribute aus - analog getTemplateData(), aber für die Sektion statt für die Spalten-Zeile.

		Aufruf:
		echo rex_gridblock2_helper::getSectionData($cs, array('data-aos'=>'aoseffect'));

		$cs 			= $contentsettings
		array() 		= Array der gewünschten Optionsfelder aus $cs (Definition: 'Data-Attribut'=>'Feldname' oder 'Data-Attribut'=>'Feldname|Prüfroutine')

		Mögliche Prüfroutinen = empty
		*/

        return self::getDatas($cs, $col=0, $options, 'section');
    }
	
	
    public static function getColumnData($cs, $col, $options = array())
	{	/*
		Liest die Werte der definierten Spalten-Settings aus und gibt diese als Data-Attribute aus.
		
		Aufruf:
		echo rex_gridblock2_helper::getColumnData($cs, 1, array('data-aos'=>'aoseffect', 'data-aos-anchor'=>'aosanchor'));
		echo rex_gridblock2_helper::getColumnData($cs, 1, array('data-aos'=>'aoseffect|empty'));
		
		$cs 			= $contentsettings
		1...12			= Spaltennummer 1...12
		array() 		= Array der gewünschten Optionsfelder aus $cs (Definition: 'Data-Attribut'=>'Feldname' oder 'Data-Attribut|Einheit'=>'Feldname|Prüfroutine')
		
		Mögliche Prüfroutinen = empty
		*/
		
        return self::getDatas($cs, $col, $options, 'column');
    }
	

    private static function getDatas($cs, $col = 0, $options = array(), $type = "")
	{
		$op = "";
		$type = ($type == 'column') ? 'column_'.intval($col) : $type;
		
		if (!empty($options) && !empty($cs)):
			foreach ($options as $key=>$val):
			
				//Prüfroutine extrahieren
				$check = "";
				if (!empty($val) && strpos($val, "|") !== false):
					$tmp = explode("|", $val);
					$val = $tmp[0];
					$check = $tmp[1];
				endif;
			
				//gespeicherten Wert aus $cs holen
				$val = @$cs->$type->$val;
								
				//Prüfroutinen ausführen				
				if ($check == 'empty'):
					if (empty($val)) { continue; }
				endif;
				
				//Data-Definition erstellen
				if (!empty($key) && trim($val) != ""):
					$val = htmlspecialchars((string) $val, ENT_QUOTES);
					$op .= $key.'="'.$val.'" ';
				endif;
				
			endforeach;
		endif;
		
        return trim($op);
    }
	

    public static function getTemplateHeader($cs, $header, $type = "h2", $align_class = "", $class = "", $with = true)
	{	/*
		Aufruf:
		echo rex_gridblock2_helper::getTemplateHeader($cs, 'header', 'header_type', 'header_align');
		echo rex_gridblock2_helper::getTemplateHeader($cs, 'header', 'header_type', 'header_align', 'myClass');
		echo rex_gridblock2_helper::getTemplateHeader($cs, 'header', 'header_type', 'header_align', 'myClass', true|false);
		
		header 			= Feldname der Überschrift
		header_type 	= Feldname der H-Größe (H1-H6)
		header_align	= Feldname der Ausrichtung (CSS-Klasse)
		myClass			= zus. eigene CSS-Klasse für den Block
		true|false		= Ausgabe mit oder ohne umschließendes DIV (default: true)
		*/
		
		$op = "";
		$header = @$cs->template->$header;
		$type = @$cs->template->$type;
			$type = (!preg_match("/h[1-6]{1}/i", $type)) ? 'h2' : $type;
		$align_class = @$cs->template->$align_class;
		$class = (empty($class)) ? 'gridblock-mainheader' : $class;
		
		if (trim($header) != ""):
			if ($with):
				$op .= '<div class="'.$class.' '.$align_class.'"><'.$type.'>'.$header.'</'.$type.'></div>';
			else:
				$op .= '<'.$type.' class="'.$class.' '.$align_class.'">'.$header.'</'.$type.'>';
			endif;
		endif;
		
        return trim($op);
    }
	
}
?>