<?php
/*
	Redaxo-Addon Gridblock²
	API-Anbindung
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class rex_api_gridblock2_loadPreview extends rex_api_function
{
	public function execute()
	{	$moduleID	= rex_request::get('moduleid', 'int', null);
		$colID		= rex_request::get('colid', 'int', null);
		$uID		= rex_request::get('uid');

		if (!$moduleID || !$colID || empty($uID) || !rex_article_content_gridblock2::isValidUID($uID)):
			throw new rex_functional_exception('Module-ID, column-ID and unique-ID parameters are required!');
		endif;

		if (!rex::getUser()->getComplexPerm('modules')->hasPerm($moduleID) && !Gridblock2Builder_Tree::isModulePlacedInSlice(rex_request::get('sliceid', 'int', 0) ?: (int) ($_SESSION['gridRexVars']['sliceID'] ?? 0), $uID, $moduleID)):
			throw new rex_functional_exception('No permission for this module!');
		endif;

		session_write_close();

		$raw = $_POST['REX_INPUT_VALUE'][$colID][$uID] ?? [];

		$MOD = rex_sql::factory();
		$MOD->setQuery('SELECT `key` FROM '.rex::getTablePrefix().'module WHERE id="'.$moduleID.'"');
		$moduleKey = ($MOD->getRows() == 1) ? (string) $MOD->getValue('key') : '';

		$rexVars = (isset($_SESSION['gridRexVars'])) ? $_SESSION['gridRexVars'] : [];
		$rexVars['grid_colNR'] = $colID;

		rex_gridblock2::isBuilderPreview(true);
		$editor = new rex_article_content_gridblock2();
		$editor->setValues($raw, $uID);
		$blockRaw = $_POST['REX_INPUT_VALUE'][Gridblock2Builder_Tree::SLOT_SETTINGS][$uID]['modules'] ?? [];
		$html = $editor->getModuleOutput($moduleID, $uID, $rexVars, is_array($blockRaw) ? $blockRaw : []);

		$themeClass = rex_request::get('gb2themeclass', 'string', '');
		$themeClass = preg_match('/^[a-zA-Z0-9_\- ]*$/', $themeClass) ? $themeClass : '';
		$themeStyle = rex_request::get('gb2themestyle', 'string', '');
		$themeStyle = (strpbrk($themeStyle, '"\'<>') === false) ? $themeStyle : '';

		if (Gridblock2Builder_Preview::useIframeForModule($moduleID)):
			echo Gridblock2Builder_Preview::renderIframeDocument($html, $moduleID, $moduleKey, '', $themeClass, $themeStyle);
		else:
			echo Gridblock2Builder_Preview::renderPayload($html, $moduleID, $moduleKey, '', $themeClass, $themeStyle);
		endif;
		exit;
	}
}


class rex_api_gridblock2_loadElementPreview extends rex_api_function
{
	public function execute()
	{
		//dieser Endpunkt liest/schreibt $_SESSION überhaupt nicht, die Sperre kann daher sofort weg.
		session_write_close();

		$type	= rex_request::get('type', 'string', '');
		$uID	= rex_request::get('uid', 'string', '');

		if (empty($type) || empty($uID) || !rex_article_content_gridblock2::isValidUID($uID)):
			throw new rex_functional_exception('Type and unique-ID parameters are required!');
		endif;

		$raw = $_POST['REX_INPUT_VALUE'][Gridblock2Builder_Tree::SLOT_SETTINGS][$uID] ?? [];
		$raw = is_array($raw) ? $raw : [];

		rex_gridblock2::isBuilderPreview(true);

		switch ($type):
			case 'section':
				$html = Gridblock2Builder_Tree::previewSection((array) ($raw['section'] ?? []), (array) ($raw['modules'] ?? []));
				break;

			case 'columnsrow':
				$gap = rex_request::get('gap', 'string', 'md');

				$templateId = rex_request::get('templateid', 'int', 0);
				$widths = ($templateId > 0) ? Gridblock2Builder_Tree::getLegacyTemplateWidths($templateId) : [];

				$ratioPreset = Gridblock2Builder_Tree::getRatioTemplate(rex_request::get('ratioid', 'int', 0));
				if ($ratioPreset !== null && $templateId <= 0) {
					$widths = $ratioPreset['widths'];
				}

				if (!$widths) {
					$widthsRaw = rex_request::get('widths', 'string', '50,50');
					$widths = array_map('floatval', array_filter(explode(',', $widthsRaw), static fn (string $part): bool => $part !== ''));
					$widths = $widths ?: [50, 50];
				}
				$columnSettingsList = [];
				foreach ($widths as $i => $w) {
					$columnSettingsList[] = (array) ($raw['column_'.($i + 1)] ?? []);
				}
				$html = Gridblock2Builder_Tree::previewColumnsRow((array) ($raw['template'] ?? []), $gap, $widths, $columnSettingsList, ($ratioPreset !== null && $templateId <= 0) ? $ratioPreset['cssClass'] : null, (array) ($raw['modules'] ?? []));
				break;

			default:
				throw new rex_functional_exception('Unknown type parameter!');
		endswitch;

		echo Gridblock2Builder_Preview::renderPayload($html, 0, '');
		exit;
	}
}


class rex_api_gridblock2_loadCustomElementPreview extends rex_api_function
{
	public function execute()
	{	session_write_close();

		$elementType = rex_request::get('elementtype', 'string', '');
		if ($elementType === '' || !Gridblock2Builder_Tree::isCustomElementTypeAllowed($elementType)):
			throw new rex_functional_exception('unknown custom element type');
		endif;

		$valuesJson = rex_post('values', 'string', '{}');
		$values = json_decode($valuesJson, true);
		$values = is_array($values) ? $values : [];

		$blockRaw = [];
		$previewUid = rex_request::get('uid', 'string', '');
		if ($previewUid !== '' && rex_article_content_gridblock2::isValidUID($previewUid)):
			$blockRaw = $_POST['REX_INPUT_VALUE'][Gridblock2Builder_Tree::SLOT_SETTINGS][$previewUid]['modules'] ?? [];
			$blockRaw = is_array($blockRaw) ? $blockRaw : [];
		endif;

		rex_gridblock2::isBuilderPreview(true);
		Gridblock2Builder_Preview::setIframeContext(Gridblock2Builder_Preview::useIframeForElement($elementType));
		$html = Gridblock2Builder_Tree::renderCustomElementOutput(['type' => 'element', 'elementType' => $elementType, 'status' => 1, 'values' => $values, 'blockSettings' => $blockRaw]);
		$inlineCss = Gridblock2Builder_Tree::getCustomElementPreviewCss($elementType);
		$extraCssLinks = Gridblock2Builder_Tree::getCustomElementPreviewCssLinks($elementType);

		$themeClass = rex_request::get('gb2themeclass', 'string', '');
		$themeClass = preg_match('/^[a-zA-Z0-9_\- ]*$/', $themeClass) ? $themeClass : '';
		$themeStyle = rex_request::get('gb2themestyle', 'string', '');
		$themeStyle = (strpbrk($themeStyle, '"\'<>') === false) ? $themeStyle : '';

		echo Gridblock2Builder_Preview::useIframeForElement($elementType)
			? Gridblock2Builder_Preview::renderIframeDocument($html, 0, '', $inlineCss, $themeClass, $themeStyle, $extraCssLinks)
			: Gridblock2Builder_Preview::renderPayload($html, 0, '', $inlineCss, $themeClass, $themeStyle, $extraCssLinks);
		exit;
	}
}


class rex_api_gridblock2_builderAddElement extends rex_api_function
{
	public function execute()
	{	$type = rex_request::get('type', 'string', 'module');
		$depth = rex_request::get('depth', 'int', 0);
		$rexVars = $_SESSION['gridRexVars'] ?? [];
		$moduleValuesOverride = [];

		if (in_array($type, ['section', 'columns'], true) && !Gridblock2Builder_Tree::isDepthAllowed($depth)):
			throw new rex_functional_exception('max nesting depth exceeded');
		endif;

		$copyTreeRaw = rex_request::post('copytree', 'string', '');
		if ($copyTreeRaw !== '' && in_array($type, ['section', 'columns'], true)):
			if ($type === 'section' && !Gridblock2Builder_Tree::getSectionElementMeta()['enabled']):
				throw new rex_functional_exception('section element disabled');
			endif;
			$isDemoContext = Gridblock2Builder_Tree::isDemoVars($rexVars);
			[$copiedNode, $copiedModuleValues] = Gridblock2Builder_Tree::buildCopiedSubtree($type, $copyTreeRaw, $depth, $isDemoContext, Gridblock2Builder_Tree::applyRequestContext($rexVars));
			echo Gridblock2Builder_Tree::renderNode($copiedNode, $copiedModuleValues, $rexVars, $depth);
			exit;
		endif;

		switch ($type):
			case 'module':
				$moduleID = rex_request::get('moduleid', 'int', 0);
				if ($moduleID <= 0):
					throw new rex_functional_exception('moduleid required');
				endif;
				if (!isset($_SESSION['gridAllowedModules'][$moduleID])):
					throw new rex_functional_exception('module not allowed in gridblock');
				endif;
				if (!rex::getUser()->getComplexPerm('modules')->hasPerm($moduleID)):
					throw new rex_functional_exception('no permission for this module');
				endif;
				if (!Gridblock2Builder_Tree::isModuleAllowedByTemplate($moduleID, Gridblock2Builder_Tree::applyRequestContext($rexVars))):
					throw new rex_functional_exception('module not allowed in template');
				endif;
				if (!Gridblock2Builder_Availability::isAllowed(Gridblock2Builder_Availability::moduleKey($moduleID), Gridblock2Builder_Tree::applyRequestContext($rexVars))):
					throw new rex_functional_exception('module not available for this domain');
				endif;
				$node = ['type' => 'module', 'uid' => rex_article_content_gridblock2::createUID(), 'id' => $moduleID, 'status' => 1];

				$copyUid = rex_request::get('copyuid', 'string', '');
				if ($copyUid !== '' && rex_article_content_gridblock2::isValidUID($copyUid)):
					$copyValues = $_POST['REX_INPUT_VALUE'][Gridblock2Builder_Tree::SLOT_MODULES][$copyUid] ?? [];
					if (!empty($copyValues)):
						$moduleValuesOverride = [$node['uid'] => $copyValues];
					endif;
					$copyBlock = $_POST['REX_INPUT_VALUE'][Gridblock2Builder_Tree::SLOT_SETTINGS][$copyUid]['modules'] ?? [];
					if (is_array($copyBlock) && $copyBlock !== []):
						$node['blockSettings'] = $copyBlock;
					endif;
				endif;
				break;

			case 'section':
				if (!Gridblock2Builder_Tree::getSectionElementMeta()['enabled']):
					throw new rex_functional_exception('section element disabled');
				endif;
				if (!Gridblock2Builder_Tree::canUseSection(Gridblock2Builder_Tree::isDemoVars($rexVars))):
					throw new rex_functional_exception('no permission for this element');
				endif;
				if (!Gridblock2Builder_Availability::isAllowed(Gridblock2Builder_Availability::sectionKey(), Gridblock2Builder_Tree::applyRequestContext($rexVars))):
					throw new rex_functional_exception('element not available for this domain');
				endif;
				$node = ['type' => 'section', 'uid' => rex_article_content_gridblock2::createUID(), 'settings' => [], 'children' => []];
				break;

			case 'columns':
				$ratioPresets = Gridblock2Builder_Tree::getRatioPresets();
				if ($ratioPresets === []):
					throw new rex_functional_exception('no column layout templates available');
				endif;
				$ratio = rex_request::get('ratio', 'string', '');
				$preset = $ratioPresets[(int) $ratio] ?? reset($ratioPresets);
				$widths = $preset['widths'];
				$slots = [];
				foreach ($widths as $w):
					$slots[] = ['settings' => [], 'children' => []];
				endforeach;
				$node = ['type' => 'columns', 'uid' => rex_article_content_gridblock2::createUID(), 'layout' => ['mode' => 'ratio', 'templateId' => $preset['id'], 'columns' => $widths, 'gap' => Gridblock2Builder_Tree::getDefaultGapValue()], 'settings' => [], 'slots' => $slots];
				break;

			case 'element':
				$elementType = rex_request::get('elementtype', 'string', '');
				$isDemoContext = Gridblock2Builder_Tree::isDemoVars($rexVars);
				if ($elementType === '' || !Gridblock2Builder_Tree::isCustomElementTypeAddable($elementType, $isDemoContext)):
					throw new rex_functional_exception('unknown custom element type');
				endif;
				if (!Gridblock2Builder_Tree::canUseCustomElement($elementType, $isDemoContext)):
					throw new rex_functional_exception('no permission for this element');
				endif;
				if (!Gridblock2Builder_Availability::isAllowed(Gridblock2Builder_Availability::customKey($elementType), Gridblock2Builder_Tree::applyRequestContext($rexVars))):
					throw new rex_functional_exception('element not available for this domain');
				endif;

				$values = [];
				$copyValuesRaw = rex_request::post('copyvalues', 'string', '');
				if ($copyValuesRaw !== ''):
					$decodedValues = json_decode($copyValuesRaw, true);
					if (is_array($decodedValues)):
						$values = $decodedValues;
					endif;
				endif;

				$node = ['type' => 'element', 'uid' => rex_article_content_gridblock2::createUID(), 'elementType' => $elementType, 'status' => 1, 'values' => $values];

				$copyUid = rex_request::get('copyuid', 'string', '');
				$copySettingsRaw = rex_request::post('copysettings', 'string', '');
				if ($copySettingsRaw !== '' && $copyUid !== '' && rex_article_content_gridblock2::isValidUID($copyUid)):
					parse_str($copySettingsRaw, $copySettings);
					$copyBlock = $copySettings['REX_INPUT_VALUE'][Gridblock2Builder_Tree::SLOT_SETTINGS][$copyUid]['modules'] ?? [];
					if (is_array($copyBlock) && $copyBlock !== []):
						$node['blockSettings'] = $copyBlock;
					endif;
				endif;
				break;

			default:
				throw new rex_functional_exception('unknown element type');
		endswitch;

		echo Gridblock2Builder_Tree::renderNode($node, $moduleValuesOverride, $rexVars, $depth);
		exit;
	}
}

class rex_api_gridblock2_builderRenderColumnSlot extends rex_api_function
{
	public function execute()
	{	$uid = rex_request::get('uid', 'string', '');
		$index = rex_request::get('index', 'int', -1);
		$depth = rex_request::get('depth', 'int', 0);

		if ($uid === '' || $index < 0 || !rex_article_content_gridblock2::isValidUID($uid)):
			throw new rex_functional_exception('uid/index required');
		endif;

		echo Gridblock2Builder_Tree::renderBlankColumnSlot($uid, $index, $depth);
		exit;
	}
}

?>