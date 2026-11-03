<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

require_once __DIR__.'/helpers.php';

$v = (array) ($_gb->values ?? []);
$cards = json_decode((string) ($v['cards'] ?? ''), true);
if (!is_array($cards) || $cards === []) {
	return;
}

$val = static fn (string $key): string => (string) ($v[$key] ?? '');

$style = GbCardsKit::pick($val('style') === 'rand' ? 'kachel' : $val('style'), array_keys(GbCardsKit::STYLES), 'klassisch');
$withBtn = ($style === 'kachelbtn');
if ($withBtn) {
	$style = 'kachel';
}
$align = GbCardsKit::pick($val('align'), ['links', 'mitte', 'rechts'], 'links');
$cardalign = GbCardsKit::pick($val('cardalign'), ['links', 'mitte', 'rechts'], 'links');
$ratio = GbCardsKit::pick($val('ratio'), ['1-1', '4-3', '3-2', '16-9', '21-9', 'original'], '');
$imgpos = ($style === 'kachel') ? GbCardsKit::pick($val('imgpos'), ['oben', 'links', 'rechts'], 'oben') : 'oben';
$width = GbCardsKit::pick($val('width'), ['schmal', 'normal', 'breit', 'sehr-breit', 'voll'], ($style === 'kompakt') ? 'breit' : (($imgpos !== 'oben') ? 'sehr-breit' : 'normal'));
$gap = (int) $val('gap');
if ($gap <= 0) {
	$gap = 24;
}

$cardBg = ($style !== 'offen') ? GbCardsKit::resolveColor($val('card_bg')) : '';
$cardColor = GbCardsKit::resolveColor($val('card_color'), 'bg');
$accent = GbCardsKit::resolveColor($val('accent'));
$accentText = GbCardsKit::resolveColor($val('accent_text'));

$classes = ['gb-el-cards', 'gb-el-cards-s-'.$style, 'gb-el-cards-w-'.$width, 'gb-el-cards-a-'.$align, 'gb-el-cards-ca-'.$cardalign];
$vars = '--gb-cards-gap:'.$gap.'px;';
if ($imgpos !== 'oben') {
	$classes[] = 'gb-el-cards-img-'.$imgpos;
}
$imgsize = ($style === 'klassisch') ? GbCardsKit::pick($val('imgsize'), ['mittel', 'klein'], '') : '';
if ($imgsize !== '') {
	$classes[] = 'gb-el-cards-is-'.$imgsize;
}
if ($ratio === 'original') {
	$classes[] = 'gb-el-cards-ratio-original';
} elseif ($ratio !== '') {
	$classes[] = 'gb-el-cards-has-ratio';
	$vars .= '--gb-cards-ratio:'.str_replace('-', ' / ', $ratio).';';
}
$roundOn = array_key_exists('round_set', $v) ? !empty($v['round']) : ($val('radius') !== 'keine');
if (!$roundOn) {
	$vars .= '--gb-cards-radius:0px;--gb-cards-btn-radius:0px;';
}
$shadowOn = array_key_exists('shadow_set', $v) ? !empty($v['shadow']) : empty($v['noshadow']);
if (!$shadowOn) {
	$classes[] = 'gb-el-cards-noshadow';
}
if ($style === 'kachel' && !empty($v['stripe'])) {
	$classes[] = 'gb-el-cards-stripe';
}
if ($cardBg !== '') {
	$vars .= '--gb-cards-bg:'.$cardBg.';';
}
if ($cardColor !== '') {
	$vars .= '--gb-cards-fg:'.$cardColor.';';
	$classes[] = 'gb-el-cards-has-fg';
}
if ($accent !== '') {
	$vars .= '--gb-cards-accent:'.$accent.';';
}
if ($accentText !== '') {
	$vars .= '--gb-cards-on-accent:'.$accentText.';';
}

if ($withBtn) {
	$classes[] = 'gb-el-cards-has-btn';
}
$overlayLabel = ($style === 'kachel');
?>
<ul class="<?php echo rex_escape(implode(' ', $classes)); ?>" style="<?php echo rex_escape($vars); ?>">
	<?php foreach ($cards as $card):
		$card = (array) $card;
		if (!empty($card['disabled'])) {
			continue;
		}

		$label = trim((string) ($card['label'] ?? ''));
		$titel = trim((string) ($card['titel'] ?? ''));
		$text = trim((string) ($card['text'] ?? ''));
		$bild = trim((string) ($card['bild'] ?? ''));

		if ($label === '' && $titel === '' && $text === '' && $bild === '') {
			continue;
		}

		$link = GbCardsKit::link((string) ($card['link'] ?? ''));
		$href = $link['href'];
		$hasLink = ($href !== '');
		$button = $hasLink && $withBtn;
		$tag = $hasLink ? 'a' : 'div';
		$attrs = $hasLink ? ' href="'.rex_escape($href).'"'.($link['blank'] ? ' target="_blank" rel="noopener"' : '') : '';
		$cardClass = 'gb-el-cards-card'.($hasLink ? ' gb-el-cards-link' : '');
		$labelHtml = ($label !== '') ? '<span class="gb-el-cards-label">'.rex_escape($label).'</span>' : '';
		$isIcon = GbCardsKit::isIcon($bild);
		$mediaClass = 'gb-el-cards-media'.($isIcon ? ' gb-el-cards-media-icon' : '');
		$overlay = $overlayLabel && !$isIcon;
		?>
		<li class="gb-el-cards-item">
			<<?php echo $tag; ?> class="<?php echo $cardClass; ?>"<?php echo $attrs; ?>>
				<?php if ($bild !== ''): ?>
				<div class="<?php echo $mediaClass; ?>">
					<img src="<?php echo rex_escape(rex_url::media($bild)); ?>" alt="<?php echo rex_escape(GbCardsKit::mediaAlt($bild)); ?>" loading="lazy" decoding="async">
					<?php echo $overlay ? $labelHtml : ''; ?>
				</div>
				<?php endif; ?>
				<?php if ($titel !== '' || $text !== '' || $button || ($labelHtml !== '' && !($overlay && $bild !== ''))): ?>
				<div class="gb-el-cards-body">
					<?php echo ($overlay && $bild !== '') ? '' : $labelHtml; ?>
					<?php if ($titel !== ''): ?><div class="gb-el-cards-title"><?php echo rex_escape($titel); ?></div><?php endif; ?>
					<?php if ($text !== ''): ?><p class="gb-el-cards-text"><?php echo nl2br(rex_escape($text)); ?></p><?php endif; ?>
					<?php if ($button): ?><span class="gb-el-cards-btn" aria-hidden="true"></span><?php endif; ?>
				</div>
				<?php endif; ?>
			</<?php echo $tag; ?>>
		</li>
	<?php endforeach; ?>
</ul>
