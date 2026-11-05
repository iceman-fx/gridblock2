<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

require_once __DIR__.'/helpers.php';

$values = (array) ($_gb->values ?? []);
$val = static fn (string $key): string => trim((string) ($values[$key] ?? ''));
$rowsOf = static function (string $key) use ($values): array {
	$rows = json_decode((string) ($values[$key] ?? ''), true);

	return is_array($rows) ? $rows : [];
};

$layout = GbHeroKit::pick($val('layout'), array_keys(GbHeroKit::LAYOUTS), 'nebeneinander');
$ratio = GbHeroKit::pick($val('ratio'), array_keys(GbHeroKit::RATIOS), '16-9');
$overlay = GbHeroKit::pick($val('overlay'), array_keys(GbHeroKit::OVERLAYS), 'mittel');
$side = GbHeroKit::pick($val('media_side'), ['links', 'rechts'], 'rechts');
$stack = GbHeroKit::pick($val('media_stack'), ['ueber', 'unter'], 'unter');
$cardPos = GbHeroKit::pick($val('card_pos'), ['aussen', 'innen'], 'aussen');
$cardV = GbHeroKit::pick($val('card_valign'), ['oben', 'mitte', 'unten'], 'mitte');
$blockLayout = in_array($layout, ['zentriert', 'vollflaechig'], true);
$align = $blockLayout ? GbHeroKit::pick($val('textalign'), ['links', 'mitte', 'rechts'], ($layout === 'zentriert') ? 'mitte' : 'links') : 'links';
$blockPosKey = $blockLayout ? GbHeroKit::pick($val('blockpos'), array_keys(GbHeroKit::BLOCK_POSITIONS), ($layout === 'zentriert') ? 'mitte' : 'links') : 'links';
$blockPos = GbHeroKit::BLOCK_POSITIONS[$blockPosKey][1];
$blockVPos = GbHeroKit::BLOCK_POSITIONS[$blockPosKey][2];
$color = static fn (string $key, string $role = 'bg'): string => GbHeroKit::resolveColor($val($key), $role);

$heading = $val('heading');
$text = $val('text');
$buttonEffect = GbHeroKit::effect($val('effect'));
$buttonsHtml = GbHeroKit::buttons($rowsOf('buttons'), $buttonEffect);
if ($heading === '' && $text === '' && $buttonsHtml === '' && !rex::isBackend()) {
	return;
}

$bild = $val('bild');
$video = in_array($layout, ['nebeneinander', 'zentriert', 'vollflaechig', 'geteilt'], true) ? $val('video') : '';
$isBe = rex::isBackend();
$vtt = ($video !== '' && $layout !== 'vollflaechig') ? $val('vtt') : '';
$vttLang = GbHeroKit::pick($val('vtt_lang'), array_keys(GbHeroKit::SUBTITLE_LANGS), 'de');

$bulletText = GbHeroKit::pick($val('bullet_style'), ['icon', 'text'], 'icon') === 'text';
$bulletPos = GbHeroKit::pick($val('bullet_pos'), ['unten', 'mitte', 'oben'], 'unten');
$bulletTop = $bulletPos === 'oben';
$bulletStack = !$bulletText && GbHeroKit::pick($val('bullet_layout'), ['inline', 'stack'], 'inline') === 'stack';
$bulletIcon = $bulletText ? '' : $val('bullet_icon');
$bullets = [];
foreach (preg_split('/\R/', (string) ($values['bullets'] ?? '')) ?: [] as $line) {
	if (trim($line) !== '') {
		$bullets[] = trim($line);
	}
}

$img = '';
if ($bild !== '') {
	$img = '<img class="gb-el-hero-img" src="'.rex_escape(GbHeroKit::mediaUrl($bild)).'" alt="'.rex_escape(GbHeroKit::mediaAlt($bild)).'" decoding="async">';
}
if ($bild === '' && $video === '' && $isBe) {
	$img = '<div class="gb-el-hero-img gb-el-hero-placeholder" aria-hidden="true"><svg viewBox="0 0 64 64" width="64" height="64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" focusable="false"><rect x="8" y="12" width="48" height="40" rx="5"/><circle cx="23" cy="26" r="5"/><path d="M8 45l15-13 11 10 8-7 14 12"/></svg></div>';
}
$posterAttribute = ($bild !== '') ? ' poster="'.rex_escape(GbHeroKit::mediaUrl($bild)).'"' : '';

$mediaHtml = $img;
$withBackgroundVideo = false;
if ($video !== '') {
	if ($isBe) {
		$mediaHtml = $img.'<span class="gb-el-hero-play'.(($layout === 'vollflaechig') ? ' gb-el-hero-play-corner' : '').'" aria-hidden="true"></span>';
	} elseif ($layout === 'vollflaechig') {
		$withBackgroundVideo = true;
		$mediaHtml = '<div class="gb-el-hero-bgvideo"><video class="gb-el-hero-video" autoplay muted loop playsinline preload="metadata"'.$posterAttribute.' aria-hidden="true" tabindex="-1"><source src="'.rex_escape(GbHeroKit::mediaUrl($video)).'" type="video/mp4"></video>'
			.'<button type="button" class="gb-el-hero-pause" aria-label="Hintergrundvideo pausieren"></button></div>';
	} elseif (rex_addon::exists('vidstack') && rex_addon::get('vidstack')->isAvailable() && class_exists('\FriendsOfRedaxo\VidStack\Video')) {
		$player = new \FriendsOfRedaxo\VidStack\Video($video, trim(str_replace('*', '', strip_tags($heading))), rex_clang::get(rex_clang::getCurrentId())->getValue('code'));
		$player->setAttributes([
			'preload' => 'metadata',
			'crossorigin' => 'anonymous',
			'controlsList' => 'nodownload',
			'referrerpolicy' => 'strict-origin-when-cross-origin',
		]);
		if ($bild !== '') {
			$player->setPoster(GbHeroKit::mediaUrl($bild), GbHeroKit::mediaAlt($bild));
		}
		if ($vtt !== '') {
			$player->addSubtitle(GbHeroKit::mediaUrl($vtt), 'captions', GbHeroKit::SUBTITLE_LANGS[$vttLang], $vttLang, true);
		}
		$mediaHtml = '<div class="gb-el-hero-player">'.$player->generateFull().'</div>';
	} else {
		$mediaHtml = '<video class="gb-el-hero-video" controls preload="metadata"'.$posterAttribute.'><source src="'.rex_escape(GbHeroKit::mediaUrl($video)).'" type="video/mp4">'
			.(($vtt !== '') ? '<track kind="captions" src="'.rex_escape(GbHeroKit::mediaUrl($vtt)).'" srclang="'.$vttLang.'" label="'.rex_escape(GbHeroKit::SUBTITLE_LANGS[$vttLang]).'" default>' : '')
			.'</video>';
	}
}

$cardsHtml = '';
if ($layout === 'karten') {
	$count = 0;
	foreach ($rowsOf('cards') as $row) {
		$row = (array) $row;
		$cardText = trim((string) ($row['ktext'] ?? ''));
		$cardImg = trim((string) ($row['kbild'] ?? ''));
		if (!empty($row['disabled']) || ($cardText === '' && $cardImg === '')) {
			continue;
		}
		if (++$count > GbHeroKit::MAX_CARDS) {
			break;
		}
		$cardsHtml .= '<div class="gb-el-hero-card">'
			.($cardImg !== '' ? '<span class="gb-el-hero-card-media'.((strtolower(pathinfo($cardImg, PATHINFO_EXTENSION)) === 'svg') ? ' gb-el-hero-card-media-icon' : '').'"><img class="gb-el-hero-card-img" src="'.rex_escape(GbHeroKit::mediaUrl($cardImg)).'" alt="" decoding="async"></span>' : '')
			.($cardText !== '' ? '<p>'.nl2br(GbHeroKit::bold($cardText)).'</p>' : '')
			.'</div>';
	}
}

$rootClass = 'gb-el-hero gb-el-hero-layout-'.$layout.' gb-el-hero-ratio-'.$ratio.' gb-el-hero-align-'.$align.' gb-el-hero-pos-'.$blockPos.(($layout === 'vollflaechig' && $blockVPos !== 'mitte') ? ' gb-el-hero-vpos-'.$blockVPos : '');
$inset = GbHeroKit::pick($val('inset'), array_keys(GbHeroKit::INSETS), 'standard');
if (($layout === 'vollflaechig' || $layout === 'geteilt') && $inset !== 'standard') {
	$rootClass .= ' gb-el-hero-inset-'.$inset;
}
if ($layout === 'nebeneinander' || $layout === 'karten' || $layout === 'geteilt') {
	$rootClass .= ' gb-el-hero-side-'.$side;
}
if ($layout === 'geteilt') {
	$rootClass .= ' gb-el-hero-split-'.GbHeroKit::pick($val('split'), array_keys(GbHeroKit::SPLITS), '60-40');
	$edge = GbHeroKit::pick($val('edge'), array_keys(GbHeroKit::EDGES), 'gerade');
	if ($edge !== 'gerade') {
		$rootClass .= ' gb-el-hero-edge-'.$edge;
	}
}
if ($layout === 'zentriert') {
	$rootClass .= ' gb-el-hero-stack-'.$stack;
}
if ($layout === 'vollflaechig') {
	$rootClass .= ' gb-el-hero-overlay-'.$overlay;
}
$rounded = array_key_exists('round_set', $values) ? !empty($values['round']) : empty($values['noround']);
if (!$rounded) {
	$rootClass .= ' gb-el-hero-square';
}
if (!empty($values['img_shadow'])) {
	$rootClass .= ' gb-el-hero-imgshadow';
}
$btnRadius = GbHeroKit::pick($val('btn_radius'), array_keys(GbHeroKit::BTN_RADII), 'pille');
if ($btnRadius !== 'pille') {
	$rootClass .= ' gb-el-hero-btnr-'.$btnRadius;
}
if ($layout === 'karten') {
	$rootClass .= ' gb-el-hero-cards-'.$cardPos.' gb-el-hero-cardsv-'.$cardV;
}
if ($mediaHtml === '' && $cardsHtml === '' && $layout !== 'vollflaechig') {
	$rootClass .= ' gb-el-hero-nomedia';
}

$rootStyle = '';
$focus = GbHeroKit::pick($val('focus'), array_keys(GbHeroKit::FOCUS), 'mitte');
if ($focus !== 'mitte') {
	$rootStyle .= '--gb-hero-focus:'.GbHeroKit::FOCUS[$focus][1].';';
}
$maxWidth = GbHeroKit::pick($val('maxwidth'), array_map('strval', array_keys(GbHeroKit::MAX_WIDTHS)), '');
if ($maxWidth !== '') {
	$rootStyle .= '--gb-hero-max:'.(int) $maxWidth.'px;';
}
if ($layout === 'vollflaechig' && $overlay !== 'keine' && $color('overlay_color') !== '') {
	$rootStyle .= '--gb-hero-overlay:'.rex_escape($color('overlay_color')).';';
}
if ($color('accent') !== '') {
	$rootStyle .= '--gb-hero-accent:'.rex_escape($color('accent')).';';
}
if ($layout === 'karten') {
	if ($color('card_bg') !== '') {
		$rootStyle .= '--gb-hero-card-bg:'.rex_escape($color('card_bg')).';';
	}
	if ($color('card_color', 'fg') !== '') {
		$rootStyle .= '--gb-hero-card-color:'.rex_escape($color('card_color', 'fg')).';';
	}
}

//Backend-Vorschau: Icon-Klassen des Addons fa_iconpicker (falls aktiv) laden - die Klassen-Zuordnung reicht sonst nicht in den Shadow-DOM der Vorschau
$faLink = '';
if ($isBe && ($buttonsHtml !== '' || $bulletIcon !== '') && rex_addon::get('fa_iconpicker')->isAvailable() && class_exists('rex_fa_iconpicker') && rex_fa_iconpicker::getCssPath() !== null) {
	$faLink = '<link rel="stylesheet" href="'.rex_escape(rtrim(rex::getServer(), '/').'/index.php?rex_media_type='.rex_i18n::msg('fa_iconpicker_mm_name').'&rex_media_file='.rex_fa_iconpicker::getActiveCssFileName()).'">'."\n";
}

$titleLevel = GbHeroKit::pick($val('heading_level'), ['h1', 'h2', 'h3', 'normal'], 'h1');
$titleIsHeading = $titleLevel !== 'normal';
$titleBig = !empty($values['heading_big']);
$titleTag = $titleIsHeading ? $titleLevel : 'div';
$titleClass = 'gb-el-hero-title'.($titleIsHeading ? ' gb-el-hero-title-'.$titleLevel : ' gb-el-hero-title-normal').($titleBig ? ' gb-el-hero-title-big' : '');
$titleHtml = GbHeroKit::heading($heading, $color('accent'));
if ($titleIsHeading && $titleBig) {
	$titleHtml = '<gb-hero-scale class="gb-el-hero-title-scale">'.$titleHtml.'</gb-hero-scale>';
}
$titleStyle = ($color('title_color') !== '') ? ' style="color:'.rex_escape($color('title_color')).';"' : '';
$textStyle = ($color('text_color') !== '') ? ' style="color:'.rex_escape($color('text_color')).';"' : '';
$bulletsHtml = '';
if ($bullets !== []) {
	$bulletsClass = 'gb-el-hero-bullets'.($bulletText ? ' gb-el-hero-bullets-text' : '').(($bulletIcon !== '') ? ' gb-el-hero-bullets-icon' : '').($bulletTop ? ' gb-el-hero-bullets-top' : '').($bulletStack ? ' gb-el-hero-bullets-stack' : '');
	$bulletsHtml = '<ul class="'.$bulletsClass.'"'.$textStyle.'>';
	foreach ($bullets as $bullet) {
		$bulletsHtml .= '<li>'.(($bulletIcon !== '') ? '<i class="'.rex_escape($bulletIcon).'" aria-hidden="true"></i> ' : '').rex_escape($bullet).'</li>';
	}
	$bulletsHtml .= '</ul>';
}
?>
<?php echo $faLink; ?><div class="<?php echo $rootClass; ?>"<?php echo ($rootStyle !== '') ? ' style="'.$rootStyle.'"' : ''; ?>>
	<?php if ($layout === 'vollflaechig'): ?>
	<div class="gb-el-hero-media"><?php echo $mediaHtml; ?><?php echo ($overlay !== 'keine') ? '<span class="gb-el-hero-overlay" aria-hidden="true"></span>' : ''; ?></div>
	<?php endif; ?>
	<div class="gb-el-hero-inner">
		<div class="gb-el-hero-text">
			<?php echo $bulletTop ? $bulletsHtml : ''; ?>
			<?php if ($heading !== ''): ?>
			<<?php echo $titleTag; ?> class="<?php echo $titleClass; ?>"<?php echo $titleStyle; ?>><?php echo $titleHtml; ?></<?php echo $titleTag; ?>>
			<?php endif; ?>
			<?php if ($text !== ''): ?>
			<p class="gb-el-hero-lead"<?php echo $textStyle; ?>><?php echo nl2br(rex_escape($text)); ?></p>
			<?php endif; ?>
			<?php echo ($bulletPos === 'mitte') ? $bulletsHtml : ''; ?>
			<?php if ($buttonsHtml !== ''): ?>
			<div class="gb-el-hero-buttons<?php echo $buttonEffect !== '' ? ' gb-el-hero-fx-'.$buttonEffect : ''; ?>"><?php echo $buttonsHtml; ?></div>
			<?php endif; ?>
			<?php echo ($bulletPos === 'unten') ? $bulletsHtml : ''; ?>
		</div>
		<?php if ($layout !== 'vollflaechig' && ($mediaHtml !== '' || $cardsHtml !== '')): ?>
		<div class="gb-el-hero-media">
			<?php echo $mediaHtml; ?>
			<?php if ($cardsHtml !== ''): ?>
			<div class="gb-el-hero-cards"><?php echo $cardsHtml; ?></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php if ($withBackgroundVideo): ?>
<script>
(function () {
	document.querySelectorAll('.gb-el-hero-bgvideo:not([data-gb-ready])').forEach(function (box) {
		box.setAttribute('data-gb-ready', '1');
		var video = box.querySelector('video');
		var button = box.querySelector('.gb-el-hero-pause');
		if (!video || !button) { return; }
		function sync() {
			button.classList.toggle('is-paused', video.paused);
			button.setAttribute('aria-label', video.paused ? 'Hintergrundvideo abspielen' : 'Hintergrundvideo pausieren');
		}
		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { video.pause(); }
		button.addEventListener('click', function () { if (video.paused) { video.play(); } else { video.pause(); } });
		video.addEventListener('play', sync);
		video.addEventListener('pause', sync);
		sync();
	});
})();
</script>
<?php endif; ?>
