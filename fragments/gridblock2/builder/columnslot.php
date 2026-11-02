<?php
/*
	Redaxo-Addon Gridblock²
	Fragment: ein einzelner Slot innerhalb eines "Spalten"-Kernelements - trägt eigene, vom Slot
			  unabhängige Settings-Werte (bildet das alte Tab-Verhalten nach, bei dem jede Spalte trotz
			  identischem Options-Schema unabhängige Werte hatte)
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)

	Erwartet im Scope: $index (int), $depth (int), $listHtml (bereits fertiges list.php-Fragment),
	$settingsFormHtml (Slot-Settings-Formular, versteckt bis zum Öffnen im Settings-Modal)
*/

/** @var rex_fragment $this */

$index = (int) $this->index;
$listHtml = (string) $this->listHtml;
$settingsFormHtml = (string) $this->settingsFormHtml;
?>
<div class="gb-builder-column-slot" data-slot-index="<?php echo (int) $index; ?>">
	<?php if ($settingsFormHtml !== ''): ?>
	<span role="button" tabindex="0" class="gb-builder-column-slot-settings" title="<?php echo rex_i18n::msg('a2034_builder_columnslot_settings'); ?>">
		<svg width="16" height="16" viewBox="0 0 640 640" fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
    <g transform="matrix(1,0,0,1,3.44704,0.861759)">
        <g transform="matrix(0.873044,0,0,0.873044,-43.8123,125.064)">
            <path d="M544,423.027L544,480C544,515.3 515.3,544 480,544L160,544C124.7,544 96,515.3 96,480L96,160C96,124.7 124.7,96 160,96L226.528,96C228.335,99.766 230.899,103.228 234.191,106.145L288.062,154.063C287.708,157.34 287.412,160.655 287.191,164L160,164L160,480L288,480L288,356.395C290.652,356.208 293.314,355.689 295.931,354.817L352,336.292L352,480L480,480L480,438.761L510.707,438.761C525.94,438.761 539.155,440.667 542.283,425.636L544,423.027ZM288,199.919L288,199.348C288.02,199.52 288.041,199.692 288.062,199.864L288,199.919Z"/>
        </g>
        <g transform="matrix(0.821664,0,0,0.821664,120.529,-1.1634)">
            <path d="M259.1,73.5C262.1,58.7 275.2,48 290.4,48L350.2,48C365.4,48 378.5,58.7 381.5,73.5L396,143.5C410.1,149.5 423.3,157.2 435.3,166.3L503.1,143.8C517.5,139 533.3,145 540.9,158.2L570.8,210C578.4,223.2 575.7,239.8 564.3,249.9L511,297.3C511.9,304.7 512.3,312.3 512.3,320C512.3,327.7 511.8,335.3 511,342.7L564.4,390.2C575.8,400.3 578.4,417 570.9,430.1L541,481.9C533.4,495 517.6,501.1 503.2,496.3L435.4,473.8C423.3,482.9 410.1,490.5 396.1,496.6L381.7,566.5C378.6,581.4 365.5,592 350.4,592L290.6,592C275.4,592 262.3,581.3 259.3,566.5L244.9,496.6C230.8,490.6 217.7,482.9 205.6,473.8L137.5,496.3C123.1,501.1 107.3,495.1 99.7,481.9L69.8,430.1C62.2,416.9 64.9,400.3 76.3,390.2L129.7,342.7C128.8,335.3 128.4,327.7 128.4,320C128.4,312.3 128.9,304.7 129.7,297.3L76.3,249.8C64.9,239.7 62.3,223 69.8,209.9L99.7,158.1C107.3,144.9 123.1,138.9 137.5,143.7L205.3,166.2C217.4,157.1 230.6,149.5 244.6,143.4L259.1,73.5ZM320.3,400C364.5,399.8 400.2,363.9 400,319.7C399.8,275.5 363.9,239.8 319.7,240C275.5,240.2 239.8,276.1 240,320.3C240.2,364.5 276.1,400.2 320.3,400Z" style="fill-rule:nonzero;"/>
        </g>
    </g>
		</svg>
	</span>
	<div class="gb-builder-column-slot-settingsform" style="display:none;"><?php echo $settingsFormHtml; ?></div>
	<?php endif; ?>
	<?php echo $listHtml; ?>
</div>
