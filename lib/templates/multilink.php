<?php
/*
	Redaxo-Addon Gridblock²
	Vendor-Kopie: Multi-Link-Chrome-Fragments (Original: _fm_helper/fragments/
				  fm_helper/multilink.php), siehe Gridblock2Builder_VendorMultilink.php-Kommentar.
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)

	Erwartet im Scope (siehe Gridblock2Builder_VendorMultilink::render()):
	$name, $uid, $allowedTypes, $activeType, $telValue, $emailValue, $urlValue,
	$linkWidgetHtml, $mediaWidgetHtml, $outputValue
*/

/** @var string $name */
/** @var string $uid */
/** @var array<int, string> $allowedTypes */
/** @var string $activeType */
/** @var string $telValue */
/** @var string $emailValue */
/** @var string $urlValue */
/** @var string $linkWidgetHtml */
/** @var string $mediaWidgetHtml */
/** @var string $outputValue */

$activeMeta   = Gridblock2Builder_VendorMultilink::typeMeta($activeType);
$hasMultiType = count($allowedTypes) > 1;
?>
<div class="fmlink" data-multilink-name="<?php echo htmlspecialchars($name); ?>">
	<input type="hidden" name="<?php echo htmlspecialchars($name); ?>" class="fmlink-json" value="<?php echo htmlspecialchars($outputValue); ?>">

	<div class="fmlink-row">
		<div class="fmlink-toggle" data-active-type="<?php echo $activeType; ?>">
			<?php if ($hasMultiType): ?>
			<button type="button" class="fmlink-toggle-btn" aria-haspopup="listbox" aria-expanded="false" aria-controls="<?php echo $uid; ?>_panel" title="Linktyp: <?php echo htmlspecialchars($activeMeta['label']); ?>" aria-label="Linktyp: <?php echo htmlspecialchars($activeMeta['label']); ?>">
				<i class="fas <?php echo $activeMeta['icon']; ?>" aria-hidden="true"></i>
				<i class="fas fa-caret-down fmlink-toggle-caret" aria-hidden="true"></i>
			</button>
			<div class="fmlink-toggle-panel" id="<?php echo $uid; ?>_panel" role="listbox">
				<?php foreach ($allowedTypes as $type): $isActive = $type === $activeType; $meta = Gridblock2Builder_VendorMultilink::typeMeta($type); ?>
				<button type="button" class="fmlink-toggle-option<?php echo $isActive ? ' active' : ''; ?>" data-type="<?php echo $type; ?>" role="option" aria-selected="<?php echo $isActive ? 'true' : 'false'; ?>">
					<i class="fas <?php echo $meta['icon']; ?>" aria-hidden="true"></i>
					<span><?php echo htmlspecialchars($meta['label']); ?></span>
				</button>
				<?php endforeach; ?>
			</div>
			<?php else: ?>
			<span class="fmlink-toggle-static" title="Linktyp: <?php echo htmlspecialchars($activeMeta['label']); ?>" aria-label="Linktyp: <?php echo htmlspecialchars($activeMeta['label']); ?>">
				<i class="fas <?php echo $activeMeta['icon']; ?>" aria-hidden="true"></i>
			</span>
			<?php endif; ?>
		</div>

		<div class="fmlink-field-wrap">
			<?php foreach ($allowedTypes as $type): $isActive = $type === $activeType;
				$valueName = $type === 'link' ? $uid.'_link' : ($type === 'media' ? $uid.'_media' : null);
			?>
			<div class="fmlink-field" data-type="<?php echo $type; ?>"<?php echo $valueName ? ' data-value-name="'.htmlspecialchars($valueName).'"' : ''; ?><?php echo $isActive ? '' : ' hidden'; ?>>
				<?php if ($type === 'tel'): ?>
					<input type="tel" class="fmlink-input form-control" placeholder="+49 89 12345678" value="<?php echo htmlspecialchars($telValue); ?>">
				<?php elseif ($type === 'email'): ?>
					<input type="email" class="fmlink-input form-control" placeholder="name@beispiel.de" value="<?php echo htmlspecialchars($emailValue); ?>">
				<?php elseif ($type === 'url'): ?>
					<input type="url" class="fmlink-input form-control" placeholder="https://beispiel.de" value="<?php echo htmlspecialchars($urlValue); ?>">
				<?php elseif ($type === 'link'): ?>
					<?php echo $linkWidgetHtml; ?>
				<?php elseif ($type === 'media'): ?>
					<?php echo $mediaWidgetHtml; ?>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
