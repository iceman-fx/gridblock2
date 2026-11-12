<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

require_once __DIR__.'/helpers.php';

$outputBGImage = true;
$mmtype = 'web_2000w';
$mmtypeMobile = 'web_900w';
$lbAttributes = 'data-photoswipe-mobilesource="###image_mobilesrc###" data-photoswipe-group="###uid###" data-photoswipe-alt="###image_alt###" data-photoswipe-title="###image_title###" data-photoswipe-copyright="###image_copyright###"';

$values = (array) ($_gb->values ?? []);
$val = static fn (string $key): string => trim((string) ($values[$key] ?? ''));
$isBe = rex::isBackend();

if (!rex_addon::get('media_manager')->isAvailable()) {
	if ($isBe) {
		echo '<p style="color: red"><strong>ACHTUNG: Das Addon "MediaManager" ist nicht installiert bzw. aktiviert!</strong></p>';
	}

	return;
}

$items = [];
foreach (GbCollageKit::images($val('collage')) as $image) {
	$media = rex_media::get($image['file']);
	if ($media && GbCollageKit::isImage($image['file'])) {
		$items[] = $image + ['media' => $media];
	}
}
if ($items === []) {
	if ($isBe) {
		echo '<p style="color: red"><strong>ACHTUNG: Es wurden noch keine Bilder ausgewählt!</strong></p>';
	}

	return;
}

$optNoLinks = $val('s_nolinks') === 'checked';
$optGreytone = $val('s_greytone') === 'checked';
$mouseover = GbCollageKit::pick($val('mouseover'), array_keys(GbCollageKit::HOVERS), '');

$uid = 'icg'.uniqid().random_int(0, 9999999);
$totalHeight = $isBe ? max(1.0, GbCollageKit::layoutHeight($items)) : 0.0;

$itemsHtml = '';
foreach ($items as $item) {
	$file = $item['file'];
	$media = $item['media'];
	$isSvg = GbCollageKit::isSvg($file);
	$radiusClass = ($item['radius'] !== 'keine') ? ' icGalerie-r-'.$item['radius'] : '';
	$pictureClass = trim($radiusClass.($item['shadow'] ? ' has-shadow' : ''));

	$width = (string) $media->getWidth();
	$height = (string) $media->getHeight();
	if ($isSvg) {
		$dimensions = GbCollageKit::svgDimensions($file);
		$width = $dimensions['width'];
		$height = $dimensions['height'];
	}
	$title = rex_escape((string) $media->getTitle());
	$alt = rex_escape((trim((string) $media->getValue('med_decorative'), '|0 ') !== '') ? '' : (trim((string) $media->getValue('med_alt')) ?: trim((string) $media->getTitle())));
	$description = rex_escape((string) $media->getValue('med_description'));
	$copyright = rex_escape((string) $media->getValue('med_copyright'));

	$src = $isSvg ? rex_url::media($file) : GbCollageKit::mediaUrl('web_600w', $file);
	$sources = '';
	if (!$isBe && !$isSvg) {
		foreach ([1600 => 'web_2000w', 1200 => 'web_1600w', 900 => 'web_1200w', 600 => 'web_900w'] as $minWidth => $type) {
			if (GbCollageKit::typeExists($type)) {
				$sources .= '<source media="(min-width: '.$minWidth.'px)" srcset="'.rex_escape(rex_media_manager::getUrl($type, $file)).'">';
			}
		}
	}
	$media = '<picture class="'.$pictureClass.'">'.$sources
		.'<img src="'.rex_escape($src).'" loading="lazy" alt="'.$alt.'"'.($width !== '' ? ' width="'.rex_escape($width).'"' : '').($height !== '' ? ' height="'.rex_escape($height).'"' : '').'>'
		.'</picture>';

	if (!$isBe) {
		if (!$optNoLinks) {
			$lbPath = $isSvg ? rex_url::media($file) : GbCollageKit::mediaUrl($mmtype, $file);
			$mobileSrc = $isSvg ? rex_url::media($file) : GbCollageKit::mediaUrl($mmtypeMobile, $file);
			$lb = str_replace(
				['###image_mobilesrc###', '###image_alt###', '###image_title###', '###image_desc###', '###image_copyright###', '###uid###'],
				[rex_escape($mobileSrc), $alt, $title, $description, $copyright, $uid],
				$lbAttributes
			);
			$media = '<a href="'.rex_escape($lbPath).'" rel="lightbox-gal'.$uid.'" class="'.trim($radiusClass).'" target="_blank" title="Bild vergrößern" '.$lb.'>'.$media.'</a>';
		}
		if ($outputBGImage) {
			$media .= '<div style="background-image: url('.rex_escape($src).');"></div>';
		}
	}

	$style = 'left: '.GbCollageKit::num($item['x']).'%; width: '.GbCollageKit::num($item['width']).'%; z-index: '.$item['z'].';';
	if ($isBe) {
		$style .= ' top: '.GbCollageKit::num($item['y'] / $totalHeight * 100).'%;';
	}
	$itemsHtml .= '<div class="icGalerie-item ratio-'.$item['format'].'" style="'.$style.'"'
		.' data-x="'.GbCollageKit::num($item['x']).'" data-y="'.GbCollageKit::num($item['y']).'" data-width="'.GbCollageKit::num($item['width']).'"'
		.' data-parallax="'.$item['parallax'].'" data-ratio="'.$item['format'].'">'
		.'<div class="icGalerie-inner">'.$media.'</div></div>';
}

$galleryClass = 'icGalerie'.($optNoLinks ? ' icG-nolinks' : '').($optGreytone ? ' icG-greytone' : '').(($mouseover !== '') ? ' '.$mouseover : '');
$galleryStyle = $isBe ? ' style="aspect-ratio: 100 / '.GbCollageKit::num($totalHeight).';"' : '';

?>
<div class="<?php echo rex_escape($galleryClass); ?>" id="<?php echo $uid; ?>"<?php echo $galleryStyle; ?>>
	<?php echo $itemsHtml; ?>
</div>
<?php if (!$isBe && !rex::getProperty('gb_collage_js', false)): ?>
<?php rex::setProperty('gb_collage_js', true); ?>
<script>
(function (window, document) {
	'use strict';

	if (window.ICGalerie) { return; }

	class ICGalerie {
		constructor(container) {
			this.container = container;
			this.items = Array.from(container.querySelectorAll('.icGalerie-item'));
			this.speedMap = {
				'slow-up': -0.12,
				'slow-down': 0.12,
				'medium-up': -0.25,
				'medium-down': 0.25,
				'fast-up': -0.45,
				'fast-down': 0.45
			};
			this.reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
			this.parallaxItems = [];
			this.init();
		}

		init() {
			this.collectParallaxItems();
			this.positionItems();
			this.bindEvents();
			requestAnimationFrame(() => { this.updateParallax(); });
		}

		parseRatio(ar) {
			const normalized = (ar || '1-1').replace('/', '-');
			const parts = normalized.split('-').map(s => parseFloat(s.trim()));
			return (parts[0] || 1) / (parts[1] || 1);
		}

		positionItems() {
			const w = this.container.offsetWidth;
			if (!w) { return; }

			let maxBottom = 0;
			let hasShadow = false;

			this.items.forEach(item => {
				const y = parseFloat(item.dataset.y) || 0;
				const width = parseFloat(item.dataset.width) || 50;
				const ratio = this.parseRatio(item.dataset.ratio);

				const wPx = (width / 100) * w;
				const hPx = wPx / ratio;
				const yPx = (y / 100) * w;

				item.style.height = hPx + 'px';
				item.style.top = yPx + 'px';

				if (item.querySelector('picture.has-shadow')) { hasShadow = true; }
				if ((yPx + hPx) > maxBottom) { maxBottom = yPx + hPx; }
			});

			if (hasShadow) { maxBottom += 30; }

			this.container.style.height = maxBottom + 'px';
		}

		collectParallaxItems() {
			if (this.reduceMotion) { return; }
			this.items.forEach(item => {
				const speed = this.speedMap[item.dataset.parallax || 'none'] || 0;
				if (speed !== 0) { this.parallaxItems.push({ el: item, speed: speed }); }
			});
		}

		updateParallax() {
			if (!this.parallaxItems.length) { return; }

			const viewportH = window.innerHeight;

			this.parallaxItems.forEach(obj => {
				const rect = obj.el.getBoundingClientRect();
				const itemMidY = rect.top + rect.height / 2;
				const offset = (itemMidY - viewportH / 2) * obj.speed;

				obj.el.style.transform = 'translateY(' + offset + 'px)';
			});
		}

		bindEvents() {
			let resizePending = false;
			let scrollPending = false;

			this.onResize = () => {
				if (resizePending) { return; }
				resizePending = true;
				requestAnimationFrame(() => {
					resizePending = false;
					this.positionItems();
					this.updateParallax();
				});
			};

			this.onScroll = () => {
				if (scrollPending) { return; }
				scrollPending = true;
				requestAnimationFrame(() => {
					scrollPending = false;
					this.updateParallax();
				});
			};

			window.addEventListener('resize', this.onResize);
			window.addEventListener('scroll', this.onScroll, { passive: true });
		}

		destroy() {
			window.removeEventListener('resize', this.onResize);
			window.removeEventListener('scroll', this.onScroll);
		}
	}

	window.ICGalerie = {
		instances: [],
		init(selector = '.icGalerie') {
			document.querySelectorAll(selector).forEach(container => {
				if (container.dataset.icInit === 'true') { return; }

				container.dataset.icInit = 'true';
				this.instances.push(new ICGalerie(container));
			});
		}
	};

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', () => { window.ICGalerie.init(); }); }
	else { window.ICGalerie.init(); }
})(window, document);
</script>
<?php endif; ?>
