<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

$cards = json_decode((string) ($_gb->values['cards'] ?? ''), true);
if (!is_array($cards) || $cards === []) {
	return;
}

$gap = (int) ($_gb->values['gap'] ?? 24);
if ($gap <= 0) {
	$gap = 24;
}
?>
<div class="gb-el-cards" style="--gb-el-cards-gap: <?php echo $gap; ?>px;">
	<?php foreach ($cards as $card):
		$card = (array) $card;
		if (!empty($card['disabled'])) {
			continue;
		}

		$titel = trim((string) ($card['titel'] ?? ''));
		$text = trim((string) ($card['text'] ?? ''));
		$bild = trim((string) ($card['bild'] ?? ''));

		$linkData = json_decode((string) ($card['link'] ?? ''), true) ?: [];
		$linkValue = (string) ($linkData['value'] ?? '');
		$href = match ($linkData['type'] ?? '') {
			'tel', 'email', 'url' => (string) ($linkData['href'] ?? ''),
			'link' => ((int) $linkValue > 0) ? rex_getUrl((int) $linkValue) : '',
			'media' => ($linkValue !== '') ? rex_url::media($linkValue) : '',
			default => '',
		};
		$target = (($linkData['type'] ?? '') === 'url') ? ' target="_blank"' : '';
		$tag = ($href !== '') ? 'a' : 'div';

		if ($titel === '' && $text === '' && $bild === '') {
			continue;
		}
		?>
		<<?php echo $tag; ?> class="gb-el-cards-item"<?php echo ($href !== '') ? ' href="'.rex_escape($href).'"'.$target : ''; ?>>
			<?php if ($bild !== ''): ?>
			<div class="gb-el-cards-item-media"><img src="<?php echo rex_escape(rex_url::media($bild)); ?>" alt="" loading="lazy"></div>
			<?php endif; ?>
			<?php if ($titel !== ''): ?><div class="gb-el-cards-item-title"><?php echo rex_escape($titel); ?></div><?php endif; ?>
			<?php if ($text !== ''): ?><p class="gb-el-cards-item-text"><?php echo nl2br(rex_escape($text)); ?></p><?php endif; ?>
		</<?php echo $tag; ?>>
	<?php endforeach; ?>
</div>
