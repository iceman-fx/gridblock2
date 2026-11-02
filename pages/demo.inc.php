<?php
/*
	Redaxo-Addon Gridblock²
	Verwaltung: Demobereich
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var rex_addon $this */

$grid = new rex_gridblock2();
$grid->rexVars['demo'] = true;
$grid->getSliceValues(0);
$grid->getModules();

$demoBlockCount = count($_SESSION['gridAllowedModules'] ?? [])
	+ count(Gridblock2Builder_Tree::discoverCustomElementTypes())
	+ 2;

$demoCards = [
	Gridblock2Builder_Tree::makeRepeaterUid() => ['titel' => 'Persönliche Beratung', 'text' => 'Individuelle Unterstützung, abgestimmt auf Ihre Anforderungen.', 'bild' => '', 'link' => ''],
	Gridblock2Builder_Tree::makeRepeaterUid() => ['titel' => 'Schnelle Umsetzung', 'text' => 'Vom ersten Baustein bis zur fertigen Seite in kurzer Zeit.', 'bild' => '', 'link' => ''],
	Gridblock2Builder_Tree::makeRepeaterUid() => ['titel' => 'Flexibel erweiterbar', 'text' => 'Eigene Elemente lassen sich jederzeit ergänzen.', 'bild' => '', 'link' => ''],
];

$demoStructure = [
	['type' => 'element', 'uid' => rex_article_content_gridblock2::createUID(), 'elementType' => 'zitat', 'status' => 1, 'values' => [
		'text' => 'Der Gridblock²-Builder macht das Zusammenstellen von Seiten spürbar einfacher.',
		'author' => 'Ein zufriedener Redakteur',
		'source' => '',
		'align' => 'links',
	]],
	['type' => 'element', 'uid' => rex_article_content_gridblock2::createUID(), 'elementType' => 'spacer', 'status' => 1, 'values' => [
		'hoehe' => '40',
	]],
	['type' => 'element', 'uid' => rex_article_content_gridblock2::createUID(), 'elementType' => 'cta', 'status' => 1, 'values' => [
		'titel' => 'Jetzt loslegen',
		'link' => '',
		'icon' => 'fas fa-arrow-right',
		'iconposition' => 'rechts',
		'bgcolor' => '',
		'textcolor' => '',
		'align' => 'links',
	]],
	['type' => 'element', 'uid' => rex_article_content_gridblock2::createUID(), 'elementType' => 'spacer', 'status' => 1, 'values' => [
		'hoehe' => '40',
	]],
	['type' => 'element', 'uid' => rex_article_content_gridblock2::createUID(), 'elementType' => 'karten', 'status' => 1, 'values' => [
		'gap' => '24',
		'cards' => json_encode($demoCards),
	]],
];
?>

<script>window.gbBuilderNoSaveMode = true;</script>

<div class="gb-wizard-banner gb-wizard-hero">
	<div class="gb-wizard-hero__bg" aria-hidden="true">
		<span class="gb-wizard-hero__block gb-wizard-hero__block--a"></span>
		<span class="gb-wizard-hero__block gb-wizard-hero__block--b"></span>
		<span class="gb-wizard-hero__block gb-wizard-hero__block--c"></span>
	</div>
	<div class="gb-wizard-hero__content">
		<div class="gb-wizard-hero__logo" aria-hidden="true"></div>
		<div>
			<p class="gb-wizard-hero__kicker"><?php echo $this->i18n('a2034_demo_hero_kicker'); ?></p>
			<h3 class="gb-wizard-hero__title"><?php echo $this->i18n('a2034_demo_hero_title'); ?></h3>
			<p class="gb-wizard-hero__lead"><?php echo $this->i18n('a2034_demo_hero_lead'); ?></p>
			<ul class="gb-demo-hero__facts">
				<li><?php echo $this->i18n('a2034_demo_hero_fact_modules', $demoBlockCount); ?></li>
				<li><?php echo $this->i18n('a2034_demo_hero_fact_nesting'); ?></li>
				<li><?php echo $this->i18n('a2034_demo_hero_fact_preview'); ?></li>
			</ul>
		</div>
	</div>
</div>

<p><?php echo $this->i18n('a2034_demo_intro_1'); ?></p>
<p><?php echo $this->i18n('a2034_demo_intro_2'); ?></p>
<p><?php echo $this->i18n('a2034_demo_intro_3'); ?></p>

<section class="rex-page-section gb-demo-layout">
	<div class="gb-demo-container">
		<?php echo Gridblock2Builder_Tree::renderRoot($demoStructure, [], $grid->rexVars); ?>
	</div>
	<aside class="gb-demo-note">
		<p><?php echo $this->i18n('a2034_demo_intro_4'); ?></p>
		<p><?php echo $this->i18n('a2034_demo_intro_5'); ?></p>
	</aside>
</section>
