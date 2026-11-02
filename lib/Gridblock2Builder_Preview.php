<?php
/*
	Redaxo-Addon Gridblock²
	Builder-Engine: Vorschau-Payload für Sektion/Spalten/Spalten-Slot (Shadow-DOM, nur CSS) und
					Inhaltsmodule (iframe, echtes CSS+JS) - siehe renderPayload() bzw. renderIframeDocument().
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)

	iframe-Äquivalent (ein iframe ist ein komplett eigenständiges Dokument). Deshalb bewusst zweigeteilt:
	Container-Knoten mit echten Kindern (Sektion/Spalten/Slot) bleiben Shadow-DOM und bekommen kein JS
	(siehe renderPayload()); Blattknoten ohne weitere Kinder (Module) bekommen ein echtes iframe mit
	echtem CSS+JS (siehe renderIframeDocument()).
	konsistent zum bestehenden, komplett unnamespaced gridblock-Code (rex_gridblock_*).
*/

class Gridblock2Builder_Preview
{
	//Liefert die geprüften, webroot-relativen PFADE der konfigurierten CSS-Dateien (gb_builder_content_stylesheets) - für <link>-Einbindung statt Text-Einbettung.
	private static function collectValidatedLines(string $lines): array
	{	$paths = preg_split('/\r\n|\r|\n/', $lines) ?: [];
		$valid = [];

		foreach ($paths as $line):
			$line = trim($line);
			if ($line === ''):
				continue;
			endif;

			if (self::resolveWithinWebroot($line) !== null):
				$valid[] = $line;
			endif;
		endforeach;

		return $valid;
	}

	public static function collectBaseCssLinks(): array
	{	return self::collectValidatedLines(Gridblock2Builder_MultiSite::getValue('gb_builder_content_stylesheets'));
	}


	private static function collectFiles(string $lines): string
	{	$paths = preg_split('/\r\n|\r|\n/', $lines) ?: [];
		$parts = [];

		foreach ($paths as $line):
			$line = trim($line);
			if ($line === ''):
				continue;
			endif;

			$abs = self::resolveWithinWebroot($line);
			if ($abs !== null):
				$parts[] = self::rewriteRelativeUrls(rex_file::get($abs), $line);
			endif;
		endforeach;

		return implode("\n", $parts);
	}

	private static function rewriteRelativeUrls(string $css, string $webPath): string
	{	$baseDir = rtrim(dirname('/'.ltrim($webPath, '/')), '/');

		return preg_replace_callback('/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i', static function (array $m) use ($baseDir): string {
			$quote = $m[1];
			$url = trim($m[2]);

			if ($url === '' || preg_match('~^(data:|https?://|//|/|#)~i', $url)):
				return $m[0];
			endif;

			$resolved = self::normalizeWebPath(($baseDir === '' ? '' : $baseDir).'/'.$url);
			return 'url('.$quote.$resolved.$quote.')';
		}, $css) ?? $css;
	}

	private static function collectFontFaceCss(string $lines): string
	{	$out = [];
		$seen = [];
		foreach (preg_split('/\r\n|\r|\n/', $lines) ?: [] as $line):
			$line = trim($line);
			if ($line !== '' && self::resolveWithinWebroot($line) !== null):
				self::collectFontFacesFromFile($line, $out, $seen, 0);
			endif;
		endforeach;

		return implode("\n", $out);
	}

	private static function collectFontFacesFromFile(string $webPath, array &$out, array &$seen, int $depth): void
	{	$webPath = '/'.ltrim($webPath, '/');
		$abs = self::resolveWithinWebroot($webPath);
		if ($depth > 3 || isset($seen[$webPath]) || $abs === null):
			return;
		endif;
		$seen[$webPath] = true;

		$css = preg_replace('~/\*.*?\*/~s', '', (string) rex_file::get($abs)) ?? '';
		$baseDir = rtrim(dirname($webPath), '/');

		if (preg_match_all('/@import\s+(?:url\(\s*)?[\'"]?([^\'")\s;]+)[\'"]?\s*\)?[^;]*;/i', $css, $imports)):
			foreach ($imports[1] as $import):
				if (preg_match('~^(https?:)?//~i', $import)):
					continue;
				endif;
				$import = (string) preg_replace('/[?#].*$/', '', $import);
				$target = (strpos($import, '/') === 0) ? $import : self::normalizeWebPath($baseDir.'/'.$import);
				self::collectFontFacesFromFile($target, $out, $seen, $depth + 1);
			endforeach;
		endif;

		if (preg_match_all('/@font-face\s*\{[^}]*\}/i', $css, $faces)):
			foreach ($faces[0] as $face):
				$out[] = self::rewriteRelativeUrls($face, $webPath);
			endforeach;
		endif;
	}

	private static function normalizeWebPath(string $path): string
	{	$segments = [];
		foreach (explode('/', $path) as $segment):
			if ($segment === '' || $segment === '.'):
				continue;
			elseif ($segment === '..'):
				array_pop($segments);
			else:
				$segments[] = $segment;
			endif;
		endforeach;

		return '/'.implode('/', $segments);
	}

	private static function resolveWithinWebroot(string $line): ?string
	{	$abs = rex_path::base(ltrim($line, '/'));
		$real = realpath($abs);
		$webroot = realpath(rex_path::base());
		if ($real === false || $webroot === false || !str_starts_with($real, $webroot.DIRECTORY_SEPARATOR)) {
			return null;
		}
		if (!in_array(strtolower(pathinfo($real, PATHINFO_EXTENSION)), ['css', 'js'], true)) {
			return null;
		}
		$backend = realpath(rex_path::backend());
		if ($backend !== false && str_starts_with($real, $backend.DIRECTORY_SEPARATOR)) {
			return null;
		}
		return is_file($real) ? $real : null;
	}

	private static function withTimebuster(string $href): string
	{	$real = self::resolveWithinWebroot($href);
		$stamp = ($real !== null) ? @filemtime($real) : false;
		if ($stamp === false):
			return $href;
		endif;

		return $href.(str_contains($href, '?') ? '&' : '?').'v='.$stamp;
	}


	public static function useIframeForModule(int $moduleID): bool
	{	if (rex_gridblock2::getConfig('gb_builder_preview_mode') === 'shadowdom'):
			return false;
		endif;

		return !preg_match('/#'.$moduleID.'#/', (string) rex_gridblock2::getConfig('gb_builder_preview_mode_modules'));
	}

	private static bool $iframeContext = false;

	public static function setIframeContext(bool $iframe): void
	{	self::$iframeContext = $iframe;
	}

	public static function isIframeContext(): bool
	{	return self::$iframeContext;
	}

	public static function useIframeForElement(string $elementType): bool
	{	$mode = Gridblock2Builder_Tree::discoverCustomElementTypes()[$elementType]['preview'] ?? '';
		if ($mode === 'iframe' || $mode === 'shadowdom'):
			return $mode === 'iframe';
		endif;

		return rex_gridblock2::getConfig('gb_builder_preview_mode') !== 'shadowdom'
			&& rex_gridblock2::getConfig('gb_builder_element_preview_iframe') === 'checked';
	}


	//Ebene 3 (Override je Modul): gb_builder_module_css_overrides, Zeilenformat "moduleID_oder_key = /pfad.css"
	public static function collectModuleCssOverride(int $moduleID, string $moduleKey): string
	{	$path = self::findModuleCssOverridePath($moduleID, $moduleKey);
		if ($path === null):
			return '';
		endif;

		$abs = self::resolveWithinWebroot($path);
		return ($abs !== null) ? rex_file::get($abs) : '';
	}

	private static function findModuleCssOverridePath(int $moduleID, string $moduleKey): ?string
	{	$lines = preg_split('/\r\n|\r|\n/', Gridblock2Builder_MultiSite::getValue('gb_builder_module_css_overrides')) ?: [];

		foreach ($lines as $line):
			$line = trim($line);
			if ($line === '' || strpos($line, '=') === false):
				continue;
			endif;

			[$match, $path] = array_map('trim', explode('=', $line, 2));
			if ($match === '' || $path === ''):
				continue;
			endif;

			if ($match === (string) $moduleID || $match === $moduleKey):
				return (self::resolveWithinWebroot($path) !== null) ? $path : null;
			endif;
		endforeach;

		return null;
	}


	private static function extractHostVariables(string $lines): string
	{	$css = self::collectFiles($lines);
		if (trim($css) === '' || !preg_match_all('/:root\s*\{([^}]*)\}/s', $css, $matches)):
			return '';
		endif;

		return ':host{'.implode('', $matches[1]).'}';
	}


	public static function collectHostVariables(): string
	{	return self::extractHostVariables(Gridblock2Builder_MultiSite::getValue('gb_builder_content_stylesheets'));
	}


	public static function getPreviewContext(): array
	{	$rexVars = (isset($_SESSION['gridRexVars']) && is_array($_SESSION['gridRexVars'])) ? $_SESSION['gridRexVars'] : [];
		$articleId = rex_request::get('gbart', 'int', 0) ?: rex_request::get('article_id', 'int', 0) ?: (int) ($rexVars['artID'] ?? 0) ?: (rex::isFrontend() ? (int) rex_article::getCurrentId() : 0);
		$clangId = rex_request::get('gbclang', 'int', 0) ?: rex_request::get('clang', 'int', 0) ?: (int) ($rexVars['clangID'] ?? 0) ?: rex_clang::getCurrentId();
		$sliceId = rex_request::get('sliceid', 'int', 0) ?: (int) ($rexVars['sliceID'] ?? 0);

		$domain = null;
		if ($articleId > 0 && Gridblock2Builder_MultiSite::isYrewriteAvailable()) {
			$domain = rex_yrewrite::getDomainByArticleId($articleId, $clangId);
		}

		return [
			'articleId' => $articleId,
			'clangId' => $clangId,
			'sliceId' => $sliceId,
			'domainId' => $domain ? (int) $domain->getId() : 0,
			'domainName' => $domain ? (string) $domain->getName() : '',
			'domainUrl' => $domain ? (string) $domain->getUrl() : '',
			'yrewriteDomain' => $domain,
		];
	}

	//Extension-Point GRIDBLOCK2_PREVIEW_CSS: andere Addons liefern CSS-Text (z. B. dynamische Projektfarben je Domain), der in die Vorschau eingebettet wird.
	public static function collectExtensionCss(bool $shadow): string
	{	$css = rex_extension::registerPoint(new rex_extension_point('GRIDBLOCK2_PREVIEW_CSS', '', self::getPreviewContext()));
		if (!is_string($css) || trim($css) === '') {
			return '';
		}
		$css = str_replace('<', '\\3C ', $css);
		if ($shadow) {
			$css = (string) preg_replace('/:root\b/', ':host', $css);
		}

		return $css;
	}

	public static function collectPreviewVisibilityFixes(): string
	{	return trim(Gridblock2Builder_MultiSite::getValue('gb_builder_preview_visibility_fixes'));
	}


	public static function collectPreviewBodyCss(): string
	{	$css = self::collectFiles(Gridblock2Builder_MultiSite::getValue('gb_builder_content_stylesheets'));
		if (trim($css) === '' || !preg_match_all('/([^{}]+)\{([^}]*)\}/s', $css, $blocks, PREG_SET_ORDER)):
			return '';
		endif;

		$declarations = '';
		foreach ($blocks as $block):
			$selectors = array_map('trim', explode(',', $block[1]));
			if (!in_array('.gb2-preview-body', $selectors, true)):
				continue;
			endif;
			$declarations .= trim($block[2]).' ';
		endforeach;

		return trim($declarations);
	}


	public static function collectBaseJsLinks(): array
	{	return self::collectValidatedLines(Gridblock2Builder_MultiSite::getValue('gb_builder_content_scripts'));
	}


	public static function getBackendThemeClass(): string
	{	if (!rex::isBackend()) {
			return '';
		}

		$user = rex::getUser();
		$theme = (string) (rex::getProperty('theme') ?: ($user ? $user->getValue('theme') : ''));

		return ($theme === 'dark') ? 'gb2-be-dark' : (($theme === 'light') ? 'gb2-be-light' : '');
	}


	public static function getPreviewWrapperClasses(): string
	{	$parts = preg_split('/[\s.]+/', trim(Gridblock2Builder_MultiSite::getValue('gb_builder_preview_wrapper_class'))) ?: [];
		$parts = array_filter($parts, static fn (string $c): bool => (bool) preg_match('/^[A-Za-z0-9_-]+$/', $c));

		return implode(' ', $parts);
	}

	private static function previewWrapperClassPrefix(): string
	{	$classes = self::getPreviewWrapperClasses();

		return $classes === '' ? '' : rex_escape($classes).' ';
	}

	public static function renderIframeDocument(string $html, int $moduleID, string $moduleKey, string $inlineCss = '', string $themeClass = '', string $themeStyle = '', array $extraCssLinks = []): string
	{	$cssLinks = self::collectBaseCssLinks();
		$overridePath = self::findModuleCssOverridePath($moduleID, $moduleKey);
		if ($overridePath !== null):
			$cssLinks[] = $overridePath;
		endif;

		$head = '<meta charset="utf-8">';
		foreach ($cssLinks as $href):
			$head .= '<link rel="stylesheet" href="'.rex_escape(self::withTimebuster($href)).'">';
		endforeach;
		foreach ($extraCssLinks as $href):
			$head .= '<link rel="stylesheet" href="'.rex_escape($href).'">';
		endforeach;
		$head .= '<style>html,body{margin:0;padding:0;overflow:hidden;background:transparent !important;}</style>';
		$extensionCss = self::collectExtensionCss(false);
		if ($extensionCss !== ''):
			$head .= '<style class="gb-preview-css-extension">'.$extensionCss.'</style>';
		endif;
		if ($inlineCss !== ''):
			$head .= '<style class="gb-preview-css-element">'.$inlineCss.'</style>';
		endif;
		foreach (self::collectBaseJsLinks() as $src):
			$head .= '<script src="'.rex_escape(self::withTimebuster($src)).'"></script>';
		endforeach;

		$themeClassCombined = trim($themeClass.' '.self::getBackendThemeClass());
		$themeClassAttr = ($themeClassCombined !== '') ? ' '.rex_escape($themeClassCombined) : '';
		$themeStyleAttr = ($themeStyle !== '') ? ' style="'.rex_escape($themeStyle).'"' : '';

		return '<!DOCTYPE html><html><head>'.$head.'</head><body class="'.self::previewWrapperClassPrefix().'gb2-preview-content'.$themeClassAttr.'"'.$themeStyleAttr.'>'.$html.'</body></html>';
	}


	public static function blockLinksEnabled(): bool
	{	$config = rex_gridblock2::getConfig();
		return !(is_array($config) && array_key_exists('gb_builder_preview_block_links', $config) && $config['gb_builder_preview_block_links'] !== 'checked');
	}

	public static function renderIframeEmbedPayload(string $html, int $moduleID, string $moduleKey): string
	{	$extraCssLinks = [];
		if (((string) rex_gridblock2::getConfig('gb_elements_css_frontend')) === 'checked'):
			$extraCssLinks[] = Gridblock2Builder_Tree::getElementsCssUrl();
		endif;
		$doc = self::renderIframeDocument($html, $moduleID, $moduleKey, '', '', '', $extraCssLinks);
		$maxHeight = ['small' => 300, 'medium' => 500, 'large' => 800][(string) rex_gridblock2::getConfig('gb_builder_slicelist_maxheight')] ?? 0;
		return '<script type="application/json" class="gb-iframe-doc"'.($maxHeight ? ' data-max-height="'.$maxHeight.'"' : '').' data-block-links="'.(self::blockLinksEnabled() ? '1' : '0').'">'.json_encode($doc, JSON_HEX_TAG).'</script>';
	}


	private static function toContainerCondition(string $condition): ?string
	{	$condition = trim($condition);
		if ($condition === '' || strpos($condition, ',') !== false):
			return null;
		endif;

		$condition = preg_replace('/^(only\s+)?(screen|all)\s+and\s+/i', '', $condition) ?? $condition;
		$condition = trim($condition);

		if ($condition === '' || preg_match('/print|speech|prefers-|orientation|hover|pointer|resolution|aspect-ratio|update|scripting|monochrome|color-gamut|color-index/i', $condition)):
			return null;
		endif;

		if (!preg_match('/^(\(\s*(min|max)-width\s*:\s*[0-9.]+(px|em|rem)\s*\)(\s+and\s+)?)+$/i', $condition)):
			return null;
		endif;

		return $condition;
	}

	private static function translateMediaQueriesToContainer(string $css): string
	{	$length = strlen($css);
		$pos = 0;
		$out = '';

		while (($start = stripos($css, '@media', $pos)) !== false):
			$braceOpen = strpos($css, '{', $start);
			if ($braceOpen === false):
				break;
			endif;

			$condition = substr($css, $start + 6, $braceOpen - $start - 6);

			$depth = 1;
			$i = $braceOpen + 1;
			while ($i < $length && $depth > 0):
				if ($css[$i] === '{'):
					$depth++;
				elseif ($css[$i] === '}'):
					$depth--;
				endif;
				$i++;
			endwhile;

			$blockContent = substr($css, $braceOpen + 1, max(0, $i - $braceOpen - 2));
			$pos = $i;

			$containerCondition = self::toContainerCondition($condition);
			if ($containerCondition !== null):
				$out .= '@container gbvp '.$containerCondition.'{'.$blockContent.'}';
			endif;
		endwhile;

		return $out;
	}

	public static function collectContainerQueryOverrides(string $lines): string
	{	if (rex_gridblock2::getConfig('gb_builder_preview_media_to_container') !== 'checked'):
			return '';
		endif;

		$css = self::collectFiles($lines);
		if (trim($css) === ''):
			return '';
		endif;

		$containerRules = self::translateMediaQueriesToContainer($css);
		if (trim($containerRules) === ''):
			return '';
		endif;

		return ':host{container-type:inline-size;}@supports (container-type: inline-size){'.$containerRules.'}';
	}


	public static function renderPayload(string $html, int $moduleID, string $moduleKey, string $inlineCss = '', string $themeClass = '', string $themeStyle = '', array $extraCssLinks = []): string
	{	$baseCssConfig = Gridblock2Builder_MultiSite::getValue('gb_builder_content_stylesheets');
		$cssLinks = [];
		$backendGridEnabled = ((string) (rex_gridblock2::getConfig('gb_builder_native_grid_css_backend') ?? 'checked')) === 'checked';
		if ($backendGridEnabled):
			if (Gridblock2Builder_Tree::nativeGridCssIsOutdated()):
				Gridblock2Builder_Tree::writeNativeGridCssFile();
			endif;
			$cssLinks[] = Gridblock2Builder_Tree::getNativeGridCssUrl();
		endif;
		$cssLinks = array_merge($cssLinks, self::collectValidatedLines($baseCssConfig));

		$overridePath = self::findModuleCssOverridePath($moduleID, $moduleKey);
		$varSourceLines = $baseCssConfig;
		if ($overridePath !== null):
			$cssLinks[] = $overridePath;
			$varSourceLines .= "\n".$overridePath;
		endif;

		$hostVars = self::extractHostVariables($varSourceLines).self::collectExtensionCss(true);
		$visibilityFixes = self::collectPreviewVisibilityFixes();
		$containerQueryOverrides = self::collectContainerQueryOverrides($varSourceLines);
		$fontFaces = self::collectFontFaceCss($varSourceLines);

		$op = '<style class="gb-preview-css-host-reset">:host{display:var(--gb-preview-display,block)!important}.gb2-preview-content{width:100%;box-sizing:border-box;}.col>div{flex:1 1 auto!important;min-width:0!important;}::slotted(.gb-builder-column-slot){flex:1 1 auto!important;min-width:0!important;width:100%!important;}</style>';
		$themeClassCombined = trim($themeClass.' '.self::getBackendThemeClass());
		$themeClassAttr = ($themeClassCombined !== '') ? ' '.rex_escape($themeClassCombined) : '';
		$themeStyleAttr = ($themeStyle !== '') ? ' style="'.rex_escape($themeStyle).'"' : '';
		$op .= '<template class="gb-preview-html"><div class="'.self::previewWrapperClassPrefix().'gb2-preview-content'.$themeClassAttr.'"'.$themeStyleAttr.'>'.$html.'</div></template>';
		foreach ($cssLinks as $href):
			$op .= '<link rel="stylesheet" class="gb-preview-css-link" href="'.rex_escape(self::withTimebuster($href)).'">';
		endforeach;
		foreach ($extraCssLinks as $href):
			$op .= '<link rel="stylesheet" class="gb-preview-css-link" href="'.rex_escape($href).'">';
		endforeach;
		if ($backendGridEnabled):
			$op .= '<style class="gb-preview-css-native-grid-container-query">'.Gridblock2Builder_Tree::getPreviewContainerQueryCss().'</style>';
		endif;
		if ($hostVars !== ''):
			$op .= '<style class="gb-preview-css-vars">'.$hostVars.'</style>';
		endif;
		if ($fontFaces !== ''):
			$op .= '<style class="gb-preview-css-fontfaces">'.$fontFaces.'</style>';
		endif;
		if ($visibilityFixes !== ''):
			$op .= '<style class="gb-preview-css-visibility-fixes">'.$visibilityFixes.'</style>';
		endif;
		if ($containerQueryOverrides !== ''):
			$op .= '<style class="gb-preview-css-container-queries">'.$containerQueryOverrides.'</style>';
		endif;
		if ($inlineCss !== ''):
			$op .= '<style class="gb-preview-css-element">'.$inlineCss.'</style>';
		endif;

		return $op;
	}
}
?>
