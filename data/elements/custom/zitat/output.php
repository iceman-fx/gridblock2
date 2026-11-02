<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

$values = (array) ($_gb->values ?? []);

if (array_key_exists('zitate', $values)) {
	$rows = json_decode((string) $values['zitate'], true);
	$rows = is_array($rows) ? $rows : [];
} else {
	$rows = [['text' => $values['text'] ?? '', 'author' => $values['author'] ?? '', 'source' => $values['source'] ?? '']];
}

$quotes = [];
foreach ($rows as $row) {
	$row = (array) $row;
	if (!empty($row['disabled']) || trim((string) ($row['text'] ?? '')) === '') {
		continue;
	}
	$quotes[] = $row;
}
if ($quotes === []) {
	return;
}

$layout = (string) ($values['layout'] ?? 'einzel');
if (!in_array($layout, ['einzel', 'raster', 'slider'], true)) {
	$layout = 'einzel';
}
if ($layout === 'einzel') {
	$quotes = [$quotes[0]];
}
$cols = (string) ($values['cols'] ?? '3');
if (!in_array($cols, ['1', '2', '3', '4'], true)) {
	$cols = '3';
}
$align = (string) ($values['align'] ?? 'links');
if (!in_array($align, ['links', 'mitte', 'rechts'], true)) {
	$align = 'links';
}
$imgRight = ((string) ($values['imgpos'] ?? 'links')) === 'rechts';

$wrapperClass = 'gb-el-zitate gb-el-zitate-layout-'.$layout.' gb-el-zitate-cols-'.$cols.' gb-el-zitate-imgpos-'.($imgRight ? 'rechts' : 'links');
if (!empty($values['imground'])) {
	$wrapperClass .= ' gb-el-zitate-round';
}
if (!empty($values['nobg'])) {
	$wrapperClass .= ' gb-el-zitate-nobg';
}
$listAttributes = ($layout === 'slider') ? ' role="region" aria-label="Zitate" tabindex="0"' : '';
?>
<div class="<?php echo $wrapperClass; ?>">
	<div class="gb-el-zitate-list"<?php echo $listAttributes; ?>>
	<?php foreach ($quotes as $quote):
		$text = trim((string) $quote['text']);
		$author = trim((string) ($quote['author'] ?? ''));
		$role = trim((string) ($quote['role'] ?? ''));
		$source = trim((string) ($quote['source'] ?? ''));
		$image = trim((string) ($quote['bild'] ?? ''));

		$linkData = json_decode((string) ($quote['link'] ?? ''), true) ?: [];
		$href = (($linkData['type'] ?? '') === 'url') ? trim((string) ($linkData['href'] ?? '')) : '';
		if ($href !== '' && $source === '') {
			$source = (string) (parse_url($href, PHP_URL_HOST) ?: $href);
		}

		$parts = [];
		if ($author !== '') {
			$parts[] = '<span class="gb-el-zitat-author">'.rex_escape($author).'</span>';
		}
		if ($role !== '') {
			$parts[] = '<span class="gb-el-zitat-role">'.rex_escape($role).'</span>';
		}
		if ($source !== '') {
			$sourceHtml = ($href !== '')
				? '<a href="'.rex_escape($href).'" target="_blank" rel="noopener" aria-label="'.rex_escape($source.' (öffnet in neuem Tab)').'">'.rex_escape($source).'</a>'
				: rex_escape($source);
			$parts[] = '<cite class="gb-el-zitat-source">'.$sourceHtml.'</cite>';
		}

		$imageHtml = '';
		if ($image !== '') {
			$contain = strtolower(pathinfo($image, PATHINFO_EXTENSION)) === 'svg' ? ' gb-el-zitat-img-contain' : '';
			$imageHtml = '<img class="gb-el-zitat-img'.$contain.'" src="'.rex_escape(rex_url::media($image)).'" alt="" loading="lazy" decoding="async">';
		}
		?>
		<figure class="gb-el-zitat gb-el-zitat-align-<?php echo $align; ?>">
			<blockquote class="gb-el-zitat-text"<?php echo ($href !== '') ? ' cite="'.rex_escape($href).'"' : ''; ?>>
				<p><?php echo nl2br(rex_escape($text)); ?></p>
			</blockquote>
			<?php if ($parts !== [] || $imageHtml !== ''): ?>
			<figcaption class="autor<?php echo ($imageHtml !== '') ? ' gb-el-zitat-cap-img' : ''; ?>">
				<?php if (!$imgRight) { echo $imageHtml; } ?>
				<?php if ($parts !== []): ?>
				<span class="gb-el-zitat-cap-text"><span class="gb-el-zitat-sep" aria-hidden="true">&mdash; </span><?php echo implode('<span class="gb-el-zitat-sep" aria-hidden="true">, </span>', $parts); ?></span>
				<?php endif; ?>
				<?php if ($imgRight) { echo $imageHtml; } ?>
			</figcaption>
			<?php endif; ?>
		</figure>
	<?php endforeach; ?>
	</div>
</div>
