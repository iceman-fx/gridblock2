<?php
/*
	Redaxo-Addon Gridblock²
	Verwaltung: Hilfe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** RexStan: Vars vom Check ausschließen */
/** @var rex_addon $this */
/** @var array $config */
/** @var string $func */
/** @var string $page */
/** @var string $subpage */


//Vorgaben
?>

<style>
/* Bootstrap-3-Spalten haben ohne Flex keine gleiche Hoehe - ohne diese Regel waere die Sidebar-Spalte
   nur so hoch wie ihr eigener Inhalt (Suche+Nav) und "position: sticky" haette schon nach wenigen
   Pixeln Scroll keinen Platz mehr, um weiter zu "kleben" (der Effekt saehe aus wie "funktioniert nicht").
   Nur ab der md-Breakpoint aktiv, ab der col-md-3/col-md-9 ueberhaupt nebeneinander stehen - darunter
   soll die normale Block-Stapelung von Bootstrap unangetastet bleiben. */
@media (min-width: 992px) {
    .gb-help-row { display: flex; flex-wrap: wrap; }
}

.gb-help-sidebar-sticky { position: sticky; top: 20px; max-height: calc(100vh - 40px); overflow-y: auto; }
.gb-help-search { margin-bottom: 0px; }
.gb-help-search-count { display: block; margin-top: 4px; font-size: 12px; color: #888; min-height: 16px; }
.gb-help-nav.nav-pills.nav-stacked > li.gb-help-nav-hidden { display: none; }
.gb-help-nav.nav-pills.nav-stacked > li > a { white-space: normal; }
.gb-help-nav-heading { padding: 10px 15px 4px; margin-top: 20px !important; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #888; }
.gb-help-nav-heading:first-child { margin-top: 0 !important; padding-top: 0; }

.gb-help-topic { margin-bottom: 45px; padding-bottom: 35px; border-bottom: 1px solid #e5e5e5; scroll-margin-top: 20px; }
.gb-help-topic:last-of-type { border-bottom: none; }
.gb-help-topic { line-height: 1.7; }
.gb-help-topic h2 { margin-top: 0; margin-bottom: 24px; }
.gb-help-topic h3 { margin-top: 44px; margin-bottom: 16px; }
.gb-help-topic h3:first-of-type { margin-top: 32px; }
.gb-help-topic h4 { margin-top: 28px; margin-bottom: 12px; }
.gb-help-topic p { margin: 0 0 18px; max-width: 80em; }
.gb-help-topic ul, .gb-help-topic ol { margin: 0 0 22px; padding-left: 24px; max-width: 80em; }
.gb-help-topic li { margin-bottom: 10px; }
.gb-help-topic li > ul, .gb-help-topic li > ol { margin-top: 10px; margin-bottom: 0; }
.gb-help-topic table { margin: 8px 0 26px; }
.gb-help-topic table td, .gb-help-topic table th { padding: 10px 12px; vertical-align: top; }
.gb-help-topic pre { margin: 8px 0 26px; padding: 14px 16px; line-height: 1.5; }
.gb-help-topic code { padding: 2px 5px; }
.gb-help-topic.gb-help-topic-hidden { display: none; }
.gb-help-topic mark { background: #fff3a3; color: inherit; padding: 0 1px; }

/* <pre>-Codebeispiele (JSON/PHP) haben teils lange, unbrechbare Zeilen - ohne diese Regeln sprengen sie
   ihren Container nach rechts statt selbst zu scrollen. table-layout:fixed ist zusaetzlich noetig, weil
   Browser bei "auto"-Layout Spalten sonst am unclipped Inhalt eines <pre> in einer <td> ausrichten,
   selbst wenn das <pre> spaeter per overflow-x scrollt. */
.gb-help-topic pre { max-width: 100%; overflow-x: auto; box-sizing: border-box; white-space: pre; }
.gb-help-topic table { table-layout: fixed; width: 100%; }
.gb-help-topic table td, .gb-help-topic table th { overflow-wrap: break-word; word-break: break-word; }
.gb-help-topic .alert { max-width: 80em; margin: 8px 0 26px; }
.gb-help-topic .alert p:last-child, .gb-help-topic .alert .gb-help-nomargin { margin-bottom: 0; }
.gb-help-noresults { display: none; padding: 20px 0; color: #888; }
.gb-help-noresults.gb-help-visible { display: block; }
</style>

<section class="rex-page-section">

<div class="row gb-help-row">
	<div class="col-md-3">

		<div class="gb-help-sidebar-sticky">
			<div class="panel panel-default">
				<header class="panel-heading"><div class="panel-title">Inhalte</div></header>
			
				<div class="panel-body">

					<div class="gb-help-search">
						<div class="input-group">
							<span class="input-group-addon"><i class="rex-icon fa-search"></i></span>
							<input type="search" class="form-control" id="gbHelpSearch" placeholder="Hilfe durchsuchen..." aria-label="Hilfe durchsuchen">
						</div>
						<span class="gb-help-search-count" id="gbHelpSearchCount" aria-live="polite"></span>
					</div>

					<ul class="nav nav-pills nav-stacked gb-help-nav" id="gbHelpNav">
						<li class="gb-help-nav-heading" data-group="allgemein">Allgemein</li>
						<li data-topic="start" data-group="allgemein"><a href="#start"><i class="rex-icon fa-circle-info"></i> Überblick</a></li>
						<li data-topic="erste-schritte" data-group="allgemein"><a href="#erste-schritte"><i class="rex-icon fa-flag-checkered"></i> Erste Schritte</a></li>
						<li data-topic="builder" data-group="allgemein"><a href="#builder"><i class="rex-icon fa-table-columns"></i> Der Builder</a></li>
						<li data-topic="favoriten" data-group="allgemein"><a href="#favoriten"><i class="rex-icon fa-star"></i> Favoriten</a></li>

						<li class="gb-help-nav-heading" data-group="admin">Admin &amp; Entwickler</li>
						<li data-topic="migration" data-group="admin"><a href="#migration"><i class="rex-icon fa-right-left"></i> Migration von Gridblock 1.x</a></li>
						<li data-topic="templates" data-group="admin"><a href="#templates"><i class="rex-icon fa-swatchbook"></i> Spaltenvorlagen</a></li>
						<li data-topic="contentsettings" data-group="admin"><a href="#contentsettings"><i class="rex-icon fa-sliders"></i> Contentsettings</a></li>
						<li data-topic="vorschau" data-group="admin"><a href="#vorschau"><i class="rex-icon fa-eye"></i> Vorschau-Technik</a></li>
						<li data-topic="projektfarben" data-group="admin"><a href="#projektfarben"><i class="rex-icon fa-palette"></i> Projektfarben</a></li>
						<li data-topic="spaltenabstand" data-group="admin"><a href="#spaltenabstand"><i class="rex-icon fa-arrows-left-right"></i> Spalten-Abstand</a></li>
						<li data-topic="alt-text" data-group="admin"><a href="#alt-text"><i class="rex-icon fa-image"></i> Alt-Text der Bilder</a></li>
						<li data-topic="modul" data-group="admin"><a href="#modul"><i class="rex-icon fa-puzzle-piece"></i> Modul &amp; Platzhalter</a></li>
						<li data-topic="edit-events" data-group="admin"><a href="#edit-events"><i class="rex-icon fa-bolt"></i> Events für Tools</a></li>
						<li data-topic="import-export" data-group="admin"><a href="#import-export"><i class="rex-icon fa-file-import"></i> Import &amp; Export</a></li>
						<li data-topic="synchronizer" data-group="admin"><a href="#synchronizer"><i class="rex-icon fa-rotate"></i> Synchronizer</a></li>
						<li data-topic="einstellungen" data-group="admin"><a href="#einstellungen"><i class="rex-icon fa-wrench"></i> Einstellungen</a></li>
						<li data-topic="api" data-group="admin"><a href="#api"><i class="rex-icon fa-code"></i> API</a></li>
					</ul>

				</div>
			</div>
		</div>
		
	</div>

	<div class="col-md-9">

		<div class="panel panel-default">
			<header class="panel-heading"><div class="panel-title">Onlinehilfe</div></header>
		
			<div class="panel-body">
	
				<div id="gbHelpContent">
					<h1>Addon: <?php echo $this->i18n('a2034_title'); ?></h1>

					<p class="gb-help-noresults" id="gbHelpNoResults">Keine Treffer. Versuch's mal mit einem anderen Begriff.</p>


					<!-- Überblick -->
					<section class="gb-help-topic" id="start">
					<h2>Überblick</h2>

					<p>Gridblock² ist ein Page- bzw. Contentbuilder für Redaxo: Du gestaltest den Aufbau einer Seite direkt im Backend visuell - per Drag&amp;Drop, mit sofortiger Vorschau.</p>

					<h3>Das Grundprinzip</h3>

					<p>Drei Bausteinarten stehen dir dafür zur Verfügung, beliebig kombinierbar und verschachtelbar:</p>

					<ul>
					  <li><strong>System-Elemente</strong> - feste Bestandteile von Gridblock², z.B. <a href="#builder">Sektion und Spalten</a> für den Grundaufbau einer Seite.</li>
					  <li><strong>Redaxo-Module</strong> - deine gewohnten, echten Module (Text, Bild, Slider, eigene Module) - keine Nachbauten, sondern genau dieselben, die auch sonst im Artikel zum Einsatz kommen.</li>
					  <li><strong>Custom-Elemente</strong> - zusätzliche, fertige Bausteine aus eigenen Ordnern. Was ein mitgeliefertes Element kann und wie es bedient wird, steht in dessen <code>README.md</code> im Elementordner; angepasst und erweitert werden sie je Projekt, siehe <a href="#builder">Der Builder</a>.</li>
					</ul>

					<p>Zum Beispiel: Text und Bild nebeneinander - ein paar Klicks im Builder genügen, ganz ohne eigens dafür gebautes Modul.</p>

					<h3>Kommst du von Gridblock 1.x?</h3>

					<p>Alles Wichtige dazu - was automatisch übernommen wird, wie bestehende Inhalte weiterlaufen und worauf du während der Umstellung achten solltest - steht gesammelt unter <a href="#migration">Migration von GB1 nach GB2</a>.</p>
					</section>


					<!-- Erste Schritte -->
					<section class="gb-help-topic" id="erste-schritte">
					<h2>Erste Schritte</h2>

					<p>Direkt nach der Installation führt dich ein Assistent einmal komplett durch die Einrichtung (er erscheint nur für Administratoren) - je nach Ausgangslage in zwei Varianten:</p>

					<ul>
					  <li><strong>Frische Installation:</strong> legt auf Wunsch ein paar fertige Spaltenvorlagen sowie das Eingabemodul für dich an. Fertig, direkt loslegen.</li>
					  <li><strong>Update von Gridblock 1.x:</strong> übernimmt deine bestehenden Spaltenvorlagen, Contentsettings und optional das Modul. Details dazu unter <a href="#migration">Migration von GB1 nach GB2</a>.</li>
					</ul>

					<p>Jeder Schritt lässt sich einzeln an- oder abwählen, und am Ende bekommst du für jeden Punkt eine klare Rückmeldung - egal ob erledigt, übersprungen oder schon vorhanden.</p>
					</section>


					<!-- Der Builder -->
					<section class="gb-help-topic" id="builder">
					<h2>Der Builder: Sektion &amp; Spalten</h2>

					<p>Im Gridblock-Baustein eines Artikels startest du über den <strong>"Hinzufügen"</strong>-Button. Zur Wahl stehen deine freigegebenen Inhaltsmodule, die Custom-Elemente sowie die Kernelemente Sektion und Spalten. Welche davon du siehst, hängt von der globalen Modulauswahl, deiner Rolle und - bei mehreren Domains - von der <a href="#einstellungen-elementauswahl">Elementauswahl je Domain</a> ab.</p>

					<h3>Bedienung</h3>
					<ul>
					  <li>Elemente per Ziehgriff (Drag-Handle) an eine neue Position verschieben - auch in andere Sektionen/Spalten hinein.</li>
					  <li>Über das Kebab-Menü (⋮) jedes Elements: Bearbeiten, Kopieren/Einfügen, Online/Offline schalten, Löschen.</li>
					  <li>Die Live-Vorschau zeigt Inhaltsmodule wahlweise per echtem <code>&lt;iframe&gt;</code> oder per Shadow-DOM an - Details dazu unter <a href="#vorschau">Vorschau-Technik</a>.</li>
					  <li><strong>Touch-Geräte (Tablet):</strong> Ein Tipp wählt ein Element aus und blendet dessen Werkzeugleiste ein, ein Tipp in den freien Bereich hebt die Auswahl wieder auf. Wählst du ein Spalten-Element aus, erscheint in jeder Spalte die Leiste für deren Einstellungen; die zuletzt getippte Spalte ist hervorgehoben.</li>
						  <li>Ein Klick auf "Spalten" ohne aktive Spaltenvorlage zeigt den Menüpunkt bewusst deaktiviert mit Hinweistext - erst eine aktive <a href="#templates">Spaltenvorlage</a> schaltet ihn frei.</li>
					</ul>

					<p>Wie tief sich Sektionen und Spalten ineinander verschachteln lassen, sowie diverse Komfort-Optionen der Toolbar (Drag-Handle-Sichtbarkeit, kompakte Toolbox, Schnellumschalter für Spalten-Aufteilung, Kopieren/Einfügen, Online/Offline ...) stellst du unter <a href="#einstellungen">Einstellungen</a> ein.</p>

					<h3>CSS für die native Sektion-/Spalten-Ausgabe</h3>

					<p>Die mitgelieferten System-Elemente geben ein klassisches <code>.gridblock &gt; .tmpl &gt; .row &gt; .col</code>-Markup aus (geerbt aus Gridblock 1.x). Kommst du von einer Migration, hat deine Website dafür meist schon passendes CSS (Bootstrap o.ä.). Bei einer <strong>komplett neuen Installation ohne eigenes Grid-CSS</strong> würden Spalten sonst untereinander statt nebeneinander stehen.</p>

					<p>Gridblock² generiert dafür automatisch eine kleine CSS-Basis (Zeile/Spalte nebeneinander + eine Regel je konfiguriertem nativen Spalten-Abstand, siehe <a href="#spaltenabstand">Spalten-Abstand</a>) - das ist der aktuell generierte Stand für dieses Projekt:</p>

					<pre><?php echo rex_escape(Gridblock2Builder_Tree::buildNativeGridCss()); ?></pre>

					<ul>
					  <li><strong>Backend-Vorschau:</strong> wird immer automatisch mitgeladen, keine Einstellung nötig.</li>
					  <li><strong>Echtes Frontend:</strong> nur über die Einstellung "Generierte CSS-Basis ... automatisch im Frontend einbinden" (Standard: aus) - z.B. praktisch für einen schnellen ersten Test ohne eigene CSS-Datei. Aktiviert bindet Gridblock² die Datei automatisch per <code>&lt;link&gt;</code> vor <code>&lt;/head&gt;</code> ein.</li>
					</ul>

					<p>Änderst du die native Spalten-Abstands-Konfiguration, wird diese CSS-Basis beim Speichern automatisch neu geschrieben - eine Anpassung hier in der Hilfe ist nie nötig.</p>

					<p><strong>Spaltenbreiten:</strong> Die Aufteilung (z.B. 60% / 40%) stammt aus den Breiten der gewählten Spaltenvorlage (Feld "Spaltenvorschau (JSON)") und wird von der mitgelieferten Standardversion von <code>column.php</code> als CSS-Variable <code>--gb-col-width</code> direkt am Spalten-Element durchgereicht; die generierte CSS-Basis oben setzt darüber automatisch <code>flex</code>/<code>max-width</code>. Bewusst eine Variable statt eines direkten Inline-Stils: eigene CSS-Klassen (z.B. Bootstrap-Spaltenklassen nach einer Migration) können sie jederzeit folgenlos überschreiben, ein direkter Inline-Stil würde eigene Breiten-Vorgaben dagegen aushebeln. Schreibst du <code>column.php</code> komplett neu (eigenes Grid-System), steht dir <code>$_gb-&gt;width</code> weiterhin direkt zur Verfügung, falls du eine andere Umsetzung brauchst.</p>

					<p><strong>Natives Grid oder eigenes Grid - nicht mischen:</strong> Das native Grid-CSS ist für Installationen gedacht, die kein eigenes Spalten-CSS haben. Es setzt die Breiten aus der Spaltenvorlage (siehe oben) und rechnet den Spaltenabstand exakt heraus, sofern dieser im Feld "Spaltenabstand" im <em>nativen</em> Modus gepflegt ist: Die Zeile bekommt dann die Klasse <code>gb-el-columns-gap-&lt;Schlüssel&gt;</code>, das CSS setzt <code>gap</code> und <code>--gb-row-gap</code>, und die Breiten ergeben zusammen mit allen Abständen genau 100&nbsp;%.</p>

					<p>Hast du eigene Spaltenklassen (z.&nbsp;B. aus Gridblock 1.x, Bootstrap oder UIkit), lass das native Grid-CSS ausgeschaltet und nutze den Abstand über deine Klassen: Dann bestimmen allein deine Klassen die Breite. Die Kombination beider Wege ist nicht empfohlen: Die nativen Breiten (<code>flex-basis</code>) setzen sich gegen die <code>width</code> deiner Klassen durch, und ein Abstand aus Contentsettings-Klassen ist dem nativen CSS unbekannt. Als Notbehelf dürfen die Spalten dann ab 768&nbsp;px schrumpfen, ohne umzubrechen; das gleicht den Abstand aus, scheitert aber, sobald dein CSS <code>flex</code>, <code>flex-shrink: 0</code> oder <code>min-width</code> an den Spalten setzt.</p>

					<p><strong>Frontend:</strong> Das native Grid-CSS liegt als Datei <code>gb-native-grid.generated.css</code> im öffentlichen Assets-Ordner und wird bei jedem Speichern der Einstellungen neu geschrieben. Hast du den Addon-Code aktualisiert, speichere die Einstellungen einmal, damit die Datei den neuen Stand bekommt.</p>

					<h3>CSS der Custom-Elemente (style.css)</h3>

					<p>Ein Custom-Element kann eine <code>style.css</code> im eigenen Ordner mitbringen. Sie gestaltet die <strong>Vorschau im Builder</strong> und wird dort immer automatisch geladen. Im <strong>echten Frontend</strong> wird sie nicht geladen: Die Gestaltung deiner Website gehört in dein eigenes CSS, die Klassen des Elements (siehe dessen <code>README.md</code>, falls vorhanden) kannst du dort frei gestalten.</p>

					<p>Für einen schnellen Start ohne eigenes CSS gibt es die Einstellung <strong>"Element-CSS im Frontend"</strong> (Standard: aus, auch im Einrichtungsassistenten wählbar). Aktiviert, sammelt Gridblock² die <code>style.css</code> aller Custom-Elemente in der Datei <code>gb-elements.generated.css</code> im öffentlichen Assets-Ordner und bindet sie per <code>&lt;link&gt;</code> vor <code>&lt;/head&gt;</code> ein. Die Datei wird beim Speichern der Einstellungen neu geschrieben und automatisch erneuert, sobald eine <code>style.css</code> neuer ist. Ohne die Einstellung kannst du sie selbst im Template einbinden:</p>

					<pre>&lt;link rel="stylesheet" href="&lt;?= Gridblock2Builder_Tree::getElementsCssUrl() ?&gt;"&gt;</pre>

					<p><strong>Gut zu wissen:</strong> Enthält eine <code>style.css</code> Dark-Mode-Regeln (<code>@media (prefers-color-scheme: dark)</code>), gelten diese im Frontend ebenfalls - auch wenn deine Website selbst nur hell gestaltet ist. Für eine eigene Gestaltung kopierst du die Regeln besser in dein Projekt-CSS und lässt die Einstellung aus. Weil die Datei zuletzt im <code>&lt;head&gt;</code> steht, setzen sich ihre Regeln bei gleicher Spezifität gegen dein Projekt-CSS durch; spezifischere Regeln in deinem CSS gewinnen weiterhin.</p>

					<h3>Kategorien und Beschreibung im Fenster "Element hinzufügen"</h3>

					<p>Der Button "+" öffnet ein zentriertes Fenster mit Suchfeld. Oben steht, wohin eingefügt wird (z.&nbsp;B. "Einfügen in: Spalten-Layout · Spalte 2 · nach „Text“"). Die Einträge sind nach Kategorien gruppiert: Layout (Sektion, Spalten und Elemente mit <code>group: layout</code>), Module (REDAXO-Module und Elemente mit <code>group: standard</code>), eigene Kategorien und zuletzt "Weitere Elemente" (<code>group: special</code>, Standard).</p>

					<p>In der <code>element.yml</code> eines Custom-Elements legst du mit <code>group</code> eine <strong>eigene Kategorie</strong> an: Jeder andere Wert, z.&nbsp;B. <code>group: Formulare</code>, wird zu einer eigenen Überschrift mit genau diesem Text. Gleich geschriebene Werte (Groß-/Kleinschreibung egal) landen in derselben Kategorie, die Reihenfolge ergibt sich aus dem ersten Auftreten. Mit <code>description: Kurztext</code> steht unter dem Titel eine Beschreibung; sie wird auch von der Suche berücksichtigt.</p>

					<h3>Elemente ein-/ausblenden</h3>

					<p>Sektion ist - anders als Spalten - kein zwingend benötigtes System-Element: Spalten rendert seine eigene Box (Hintergrund, Rahmen, Formtrenner, ...) komplett eigenständig, Sektion ist nur noch ein optionaler, generischer Container drumherum. Brauchst du ihn in einem Projekt nicht, blendest du ihn per <code>status: offline</code> in dessen <code>element.yml</code> (<code>data/elements/system/section/element.yml</code>) aus dem "Hinzufügen"-Menü aus, ohne die Datei zu löschen. Bereits im Artikel platzierte Sektionen bleiben davon unberührt und funktionieren normal weiter.</p>

					<p>Dieselbe <code>status: online</code>/<code>status: offline</code>-Angabe funktioniert auch in der <code>element.yml</code> jedes eigenen Custom-Elements - praktisch, um einen Baustein projektweit vorübergehend zu deaktivieren, ohne ihn zu entfernen. Fehlt der Schlüssel, gilt ein Element wie bisher immer als online (sichtbar). Für Spalten selbst gibt es diese Angabe nicht - eine Aufteilung braucht immer mindestens eine aktive <a href="#templates">Spaltenvorlage</a> (siehe oben, "Ein Klick auf 'Spalten' ...").</p>

					<p><strong>Abhängigkeit von einem Addon:</strong> Setzt ein Custom-Element ein bestimmtes Addon voraus (z.&nbsp;B. eine Karten-Bibliothek), trägst du es in der <code>element.yml</code> unter <code>requires</code> ein, etwa <code>requires: vector_maps</code> (oder eine Liste mehrerer Addons). Das Element erscheint dann nur im "Hinzufügen"-Menü, solange alle genannten Addons installiert und aktiv sind. Bereits gespeicherte Instanzen bleiben erhalten: Der Builder zeigt statt des Formulars einen Hinweis, im Frontend wird nichts ausgegeben - es kommt zu keinem Fehler, wenn das Addon fehlt.</p>

					<p><strong>Rechte pro Rolle:</strong> Mit <code>permission: true</code> in der <code>element.yml</code> wird ein Custom-Element (auch aus einem anderen Addon) oder die Sektion rechtegesteuert. In der Rollenverwaltung erscheint dann direkt unter "Module" das Feld "Gridblock-Elemente": Dort wählst du je Rolle die erlaubten Elemente oder "Alle Gridblock-Elemente". Ohne Recht fehlt das Element im "Hinzufügen"-Menü; bereits platzierte Instanzen bleiben in Vorschau und Frontend sichtbar und lassen sich verschieben, aber nicht bearbeiten, kopieren, ein-/ausblenden oder löschen. Das Rollenrecht gilt für Admins nicht (sie dürfen alle Elemente nutzen; die Auswahl je Domain gilt beim Hinzufügen trotzdem, siehe <a href="#einstellungen-elementauswahl">Elementauswahl je Domain</a>), im Demobereich gilt keine Einschränkung. Elemente ohne diese Angabe stehen allen Rollen zur Verfügung. Für Spalten gibt es keine Rechte-Einstellung. Hinweis: Setzt du <code>permission: true</code> neu, sehen Rollen das Element erst, wenn du es dort freigibst.</p>

						<h3>Elemente aus anderen Addons</h3>

					<p>Auch ein anderes Addon kann eigene Custom-Elemente mitliefern: Es legt dieselbe Ordnerstruktur (<code>element.yml</code>, <code>input.php</code>, <code>output.php</code>, optional <code>style.css</code>) unter <code>redaxo/src/addons/&lt;addon&gt;/gridblock2/elements/&lt;typ&gt;/</code> ab. Solche Elemente erscheinen automatisch im "Hinzufügen"-Menü, solange das Addon aktiv ist. Ihr Schlüssel lautet <code>&lt;addon&gt;.&lt;typ&gt;</code>.</p>

					<p><strong>Gut zu wissen:</strong> Die Dateien eines Addon-Elements laufen als normales PHP im Backend und im Frontend, mit denselben Rechten wie der restliche Code des Addons. Gridblock² prüft ihren Inhalt nicht - aktiviere deshalb nur Addons, denen du vertraust, wie bei jedem anderen REDAXO-Addon.</p>

					<p>Eigene Anpassungen machst du update-sicher im Projekt: Ein Ordner mit demselben vollständigen Schlüssel unter <code>data/addons/gridblock2/elements/custom/</code> hat Vorrang vor dem Element des Addons. Wird das Addon später deaktiviert, bleiben bereits gespeicherte Elemente erhalten - der Builder zeigt dann einen Hinweis statt des Formulars, Verschieben und Löschen bleiben möglich.</p>

					<p id="elemente-anpassen"><strong>Angepasste Elemente bekommen keine Updates:</strong> Ein überschriebenes Element ersetzt die Fassung des Addons vollständig. Spätere Änderungen daran (Fehlerbehebungen, neue Optionen) kommen in deiner Kopie nicht an, du übernimmst sie bei Bedarf selbst. Ändere deshalb nur, was nötig ist, und vergleiche die Kopie nach einem Update mit der Fassung im Addon-Ordner.</p>

					<p>Braucht die Live-Vorschau eines Elements zusätzliches CSS aus einem Addon (z.&nbsp;B. eine Karten-Bibliothek), trägst du es in der <code>element.yml</code> unter <code>preview_css</code> ein, etwa <code>addon:vector_maps/build/vectormaps.css</code>. Die Pfade werden über REDAXO aufgelöst und nur in der Backend-Vorschau geladen.</p>
					</section>


					<!-- Favoriten -->
					<section class="gb-help-topic" id="favoriten">
					<h2>Favoriten</h2>

					<p>Ein Favorit ist eine gespeicherte <strong>Kopie samt Inhalten</strong> eines Elements, einer Sektion, einer Spaltengruppe oder aller Elemente eines Bausteins. Du fügst ihn später jederzeit wieder ein, ohne ihn neu aufzubauen. Texte und Einstellungen werden übernommen, Bilder und Links bleiben so, wie sie gespeichert wurden.</p>

					<h3>Speichern</h3>
					<ul>
					  <li><strong>Einzelnes Element, Sektion oder Spalten:</strong> Kebab-Menü (⋮) des Blocks, dann <em>Als Favorit speichern …</em></li>
					  <li><strong>Alle Elemente des Bausteins:</strong> Stern-Symbol links neben dem Vollbild-Button.</li>
					  <li>Im Dialog vergibst du den Titel. Je nach Einstellung kannst du den Favoriten auf <em>Nur für mich</em> begrenzen. Bei aktivem MultiSite gilt er für die aktuelle Domain; im Demo-Bereich wählst du die Domain selbst.</li>
					  <li>Speichern kannst du nur mit dem Recht <em>Gridblock²: Favoriten speichern und löschen</em> (Administratoren immer). Reine Demo-Elemente lassen sich nicht als Favorit speichern.</li>
					</ul>

					<h3>Einfügen</h3>
					<p>Im Fenster <strong>"Element hinzufügen"</strong> wechselst du auf den Reiter <em>Favoriten</em>, suchst nach dem Titel und klickst den Eintrag an. Der Favorit wird immer <strong>zusätzlich</strong> an der gewählten Stelle eingefügt, mehrere Elemente nacheinander in der gespeicherten Reihenfolge. Es gelten dieselben Prüfungen wie beim Kopieren und Einfügen (Rechte, Verfügbarkeit, Verschachtelungstiefe).</p>

					<h3>Umbenennen und Löschen</h3>
					<p>Im Reiter <em>Favoriten</em> stehen an eigenen Favoriten ein Stift (Titel ändern) und ein Papierkorb (löschen). Administratoren dürfen alle Favoriten ändern. Der Inhalt eines Favoriten lässt sich nicht bearbeiten: einfügen, ändern, als neuen Favoriten speichern und den alten löschen.</p>

					<h3>Einstellungen (Admin)</h3>
					<ul>
					  <li><strong>Projektweit</strong> (Standard): normale Benutzer speichern immer für alle.</li>
					  <li><strong>Benutzerbasiert:</strong> normale Benutzer speichern immer nur für sich.</li>
					  <li><strong>Beim Speichern wählbar:</strong> mit dem Häkchen <em>Nur für mich</em>.</li>
					  <li>Administratoren wählen immer frei. Ein Moduswechsel ändert die Sichtbarkeit bestehender Favoriten nicht, sie lässt sich in der Verwaltung ändern.</li>
					  <li><strong>Domainübergreifende Favoriten erlauben</strong> (nur bei aktivem MultiSite): ohne diese Option gilt jeder Favorit nur für seine Domain. Favoriten aus der Zeit vor MultiSite sind auf allen Domains sichtbar und lassen sich in der Verwaltung einer Domain zuweisen.</li>
					</ul>

					<h3>Verwaltung (nur Administratoren)</h3>
					<p>Der Menüpunkt <em>Favoriten</em> im Addon zeigt alle Favoriten aller Benutzer und Domains mit Suche und Filtern (Domain, Ersteller, Art, Sichtbarkeit) und der Sortierung <em>Nie genutzt zuerst</em> zum Aufräumen. Mehrere Einträge lassen sich gemeinsam löschen, für alle freigeben oder einer Domain zuweisen. Einträge gelöschter Benutzer oder Domains sind markiert.</p>

					<div class="alert alert-info"><p><strong>Hinweis:</strong> Favoriten gehören zum Projekt, es gibt bewusst keinen Import/Export, weil sie auf Medien und Links des Projekts verweisen.</p></div>

					</section>

					<!-- Migration von GB1 nach GB2 -->
					<section class="gb-help-topic" id="migration">
					<h2>Migration von Gridblock 1.x nach Gridblock²</h2>

					<p>Kurzfassung: Nichts geht kaputt, nichts muss sofort umgestellt werden. Bestehende Inhalte laufen unverändert weiter, bis du sie einmal im Builder neu speicherst.</p>

					<h3>Was der Einrichtungsassistent übernimmt</h3>
					<ul>
					  <li>Erkennt eine vorhandene Gridblock-1.x-Installation automatisch und bietet die Migration statt der Ersteinrichtung an.</li>
					  <li>Übernimmt alle Spaltenvorlagen 1:1 in die neue Datenstruktur.</li>
					  <li>Übernimmt die Contentsettings-Dateien (projektweit &amp; pro Vorlage).</li>
					  <li>Kann optional das bestehende Eingabemodul auf die neue Klasse umstellen ("Eingabemodul umstellen") - eigene Anpassungen am Modul bleiben dabei erhalten, der Identifier-Kommentar bleibt entscheidend (siehe <a href="#modul">Modul &amp; Platzhalter</a>).</li>
					  <li>Kann optional den kompletten Modulcode durch die aktuelle Gridblock²-Fassung ersetzen ("Modulcode komplett ersetzen") - eigene Anpassungen am Modul gehen dabei verloren, bestehende Slices bleiben erhalten.</li>
					</ul>

					<h3>Modulcode nach der Migration</h3>

					<div class="alert alert-warning" role="note">
					  <p>Der Code deines vorhandenen Gridblock-Moduls (Eingabe und Ausgabe) liegt in der Datenbank, nicht im Addon. Gridblock² legt sein eigenes Modul nur an, wenn noch <strong>keines</strong> mit dem Identifier-Kommentar existiert, und aktualisiert vorhandene Module <strong>nie automatisch</strong> - auch nicht bei späteren Addon-Updates. Dadurch fehlen in einem migrierten Modul neuere Funktionen, zum Beispiel die <strong>Live-Vorschau in der Slice-Übersicht</strong> (Einstellung "Live-Vorschau in der Slice-Übersicht": ohne neuen Ausgabecode bleibt sie wirkungslos).</p>
					  <p>Lösung: im Einrichtungsassistenten die Option <em>"Modulcode komplett durch die aktuelle Gridblock²-Fassung ersetzen"</em> wählen, oder den Inhalt von <code>install/input.php</code> und <code>install/output.php</code> des Addons von Hand in das Modul kopieren. Eigene Anpassungen am Modul vorher sichern - sie werden überschrieben.</p>
					</div>

					<h3>Wie bestehende Inhalte weiterlaufen</h3>

					<p>Alte, noch nicht migrierte Gridblock-Inhalte werden weiterhin über die alte Ausgabelogik gerendert - komplett unabhängig vom neuen Builder. Erst wenn ein solcher Block im Artikel einmal neu gespeichert wird, wandelt Gridblock² ihn automatisch ins neue, native Sektion/Spalten-Format um. Bis dahin liefern aktive Spaltenvorlagen weiterhin nur die Aufteilungen fürs native Spalten-Element - eine eigenständige Verwendung als fertiger Baustein wie früher gibt es nicht mehr.</p>

					<h3>Der laufende Contentsettings-Abgleich (Koexistenz-Phase)</h3>

					<p>Solange die alte Gridblock-1.x-Installation noch installiert <strong>und aktiv</strong> ist, kopiert Gridblock² die Contentsettings-Datei bei jedem Aufruf automatisch aus den GB1-Daten in die eigene Datenstruktur. So läuft das Frontend nicht ins Leere, nur weil GB2 schon aktiv, die Migration aber noch nicht final abgeschlossen ist. Vorhandene Dateien werden dabei nur überschrieben, nie vorher geleert. Der Abgleich hört automatisch auf, sobald GB1 deinstalliert oder deaktiviert wird.</p>

					<h3>Zusammenspiel mit dem Synchronizer</h3>

					<p>Ist der <a href="#synchronizer">Synchronizer</a> zusätzlich aktiviert, sieht er diesen laufenden Koexistenz-Abgleich bei jedem Seitenaufruf als Änderung an und schreibt entsprechend häufig in seine eigene Dateikopie zurück. Das ist unschädlich, aber während der Übergangszeit normal - es hört auf, sobald GB1 deinstalliert oder deaktiviert ist.</p>

					<h3>Checkliste für eine saubere Migration</h3>
					<ol>
					  <li>Einrichtungsassistenten durchlaufen und die Migration bestätigen - dabei entscheiden, ob der Modulcode ersetzt werden soll (siehe oben).</li>
					  <li>Ein paar bestehende Artikel mit Gridblock-Inhalten stichprobenartig im Frontend prüfen.</li>
					  <li>Nach und nach betroffene Artikel im Builder öffnen und kurz speichern - dabei wandeln sie automatisch ins neue Format.</li>
					  <li>Erst wenn alle relevanten Inhalte umgestellt sind: Gridblock 1.x deaktivieren oder deinstallieren.</li>
					</ol>
					</section>


					<!-- Spaltenvorlagen -->
					<section class="gb-help-topic" id="templates">
					<h2>Spaltenvorlagen (Templates)</h2>

					<p>Spaltenvorlagen legen fest, welche Aufteilungen dem Redakteur beim nativen Spalten-Element zur Auswahl stehen - z.B. "50% / 50%" oder "70% / 30%". Ein paar Beispielvorlagen kannst du dir jederzeit über die Schaltfläche in der Übersicht installieren lassen, eigene Vorlagen legst du direkt dort an oder holst sie dir per <a href="#import-export">Import</a> von einer anderen Installation.</p>

					<h3>Felder einer Spaltenvorlage</h3>

					<table width="100%" border="0" cellspacing="0" cellpadding="0">
					  <tr>
						<th width="200" scope="col">Feld</th>
						<th scope="col">Bedeutung</th>
					  </tr>
					  <tr>
						<td valign="top"><strong>Titel</strong> (Pflicht)</td>
						<td valign="top">Name der Vorlage in der Auswahl. Ohne eigene Spaltenvorschau (siehe unten) dient er zusätzlich als deren Beschriftung.</td>
					  </tr>
					  <tr>
						<td valign="top"><strong>Kurzbeschreibung</strong></td>
						<td valign="top">Erscheint als Hover-Text bei der Spaltenvorschau.</td>
					  </tr>
					  <tr>
						<td valign="top"><strong>Priorität</strong></td>
						<td valign="top">Steuert die Sortierung in der Auswahlliste - kleinere Werte zuerst.</td>
					  </tr>
					  <tr>
						<td valign="top"><strong>Anzahl Spalten</strong> (Pflicht)</td>
						<td valign="top">1 bis 12 Spalten. Muss zur Anzahl der Einträge in der Spaltenvorschau passen.</td>
					  </tr>
					  <tr>
						<td valign="top"><strong>CSS-Klasse</strong></td>
						<td valign="top">Wird der Spalten-Zeile im Frontend als zusätzliche Klasse mitgegeben - praktisch für dein eigenes CSS-Grid-System (Bootstrap, UIKit, eigenes CSS-Grid, ...).</td>
					  </tr>
					  <tr>
						<td valign="top"><strong>Spaltenvorschau</strong> (JSON)</td>
						<td valign="top">
						  Definiert die Breiten (in %) und Beschriftungen der einzelnen Spalten - erscheint als visuelle Vorschau in der Redakteursauswahl. <strong>Die Breiten wirken nicht nur dort:</strong> Sie bestimmen auch den Wert <code>--gb-col-width</code> am Spalten-Element (siehe <a href="#builder">Der Builder</a>, "Spaltenbreiten") und damit - bei nativer Breiten-Einstellung - die Darstellung in Builder-Vorschau und Frontend. Setzt du die Breiten stattdessen über die CSS-Klasse deines eigenen CSS um, dient das JSON nur der Anzeige im Builder (Mini-Balken und Vorschau), sollte aber trotzdem zu deinem CSS passen. Über den Link "Beispiel verwenden" direkt im Formular bekommst du eine passende Vorlage vorausgefüllt.<br><br>
						  <strong>Beispiel:</strong>
						  <pre>{
	"totalcolumns": "3",
	"columns": [
		{ "width": "50", "title": "50%" },
		{ "width": "25", "title": "25%" },
		{ "width": "25", "title": "25%" }
	]
}</pre>
						</td>
					  </tr>
					  <tr>
						<td valign="top"><strong>Status</strong></td>
						<td valign="top">Nur aktive Vorlagen stehen im Builder zur Auswahl.</td>
					  </tr>
					</table>

					<h3>Bestehende Elemente bleiben an die Vorlage gebunden</h3>

					<p>Ein Spalten-Element merkt sich die gewählte Vorlage, nicht nur deren Breiten. Änderungen an der Vorlage wirken deshalb auf alle bereits angelegten Elemente - jeweils getrennt voneinander:</p>
					<ul>
					  <li><strong>CSS-Klasse</strong> geändert (z.&nbsp;B. beim Wechsel des CSS-Frameworks): alle Elemente nutzen die neue Klasse.</li>
					  <li><strong>Spaltenvorschau (JSON)</strong> geändert (z.&nbsp;B. weil du die Breiten schon im Frontend-CSS angepasst hast): die Darstellung im Builder zieht nach, die Klasse bleibt.</li>
					  <li><strong>Anzahl Spalten</strong> geändert: sie ist maßgebend. Fehlende Spalten werden leer ergänzt, überzählige Spalten werden zusammengeführt (ihre Inhalte wandern in die letzte verbleibende Spalte, es geht nichts verloren).</li>
					</ul>
					<p>Nur die Anzahl Spalten ist Pflicht. Ohne Spaltenvorschau werden die Breiten gleichmäßig verteilt, ohne CSS-Klasse wird keine Klasse ausgegeben. Elemente aus älteren Versionen werden beim ersten Aufruf automatisch der Vorlage zugeordnet, deren Aufteilung zu ihren gespeicherten Breiten passt. Gibt es keine passende Vorlage mehr, bleiben die gespeicherten Breiten bestehen, es wird aber keine Klasse ausgegeben - wähle dann einfach die Aufteilung einmal neu.</p>

					<h3>Und der alte Template-Ausgabecode?</h3>

					<p>Neue Vorlagen brauchen keinen eigenen PHP-Ausgabecode mehr - die Ausgabe übernimmt Gridblock² selbst. Das Feld taucht im Formular nur noch aufklappbar auf, wenn eine Vorlage aus einer Gridblock-1.x-Migration stammt und noch echten Alt-Code enthält.</p>
					</section>


					<!-- Contentsettings -->
					<section class="gb-help-topic" id="contentsettings">
					<h2>Contentsettings</h2>

					<p>Mit den Contentsettings definierst du wiederkehrende Gestaltungsoptionen (Abstände, Hintergrundfarbe, Breite, ...), die der Redakteur direkt am Element einstellen kann - ganz ohne dass du dafür eigene Modul-Felder bauen musst. Redaxo generiert das passende Formular automatisch aus deiner Definition.</p>

					<h3>Ebenen (Scopes)</h3>
					<ul>
					  <li><strong>section</strong> - Einstellungen für die Sektion (optional, unabhängig vom Inhalt - z.B. Hintergrund/Formtrenner um beliebige Inhalte herum).</li>
					  <li><strong>template</strong> - Einstellungen für die Spalten-Zeile (Box + Zeilen-Einstellungen wie der Abstand zwischen den Spalten).</li>
					  <li><strong>columns</strong> - Einstellungen für eine einzelne Spalte.</li>
					  <li><strong>modules</strong> - optional: Blockeinstellungen, die für <em>jeden</em> Block gelten (Inhaltsmodul, Custom-Element, Sektion, Spalten-Zeile) - siehe <a href="#contentsettings-modules">Blockeinstellungen</a>.</li>
					</ul>

					<h3>Zwei Dateiebenen</h3>
					<ul>
					  <li><strong>Projektweit:</strong> <code>redaxo/data/addons/gridblock2/contentsettings/contentsettings.json</code> - gilt für alle Vorlagen.</li>
					  <li><strong>Pro Vorlage:</strong> <code>redaxo/data/addons/gridblock2/contentsettings/templates/template_$ID/contentsettings.json</code> - überschreibt gezielt einzelne Optionen nur für diese eine Vorlage (z.B. "im Projekt mb-5, bei diesem Template aber mb-3").</li>
					</ul>

					<p>Zusätzlich kann eine <code>contentsettings.global.json</code> neben der contentsettings.json liegen - sie wird vorrangig eingelesen und eignet sich für Definitionen, die zur Laufzeit übersteuert werden dürfen.</p>

					<h3>Beispieldefinition</h3>

					<p>Format, an dem du dich orientieren kannst:</p>
					<pre style="height: 400px;">
<?php
$csExamplePath = rex_addon::get('gridblock2')->getPath('data/contentsettings/contentsettings.json.example');
echo (file_exists($csExamplePath)) ? htmlspecialchars(rex_file::get($csExamplePath)) : '';
?></pre>

					<h3>Zugriff im Code</h3>

					<p>Für die nativen Sektion-/Spalten-/Spalten-Zeile-Elemente verarbeitet Gridblock² die Contentsettings automatisch selbst (z.B. als CSS-Klassen am Wrapper) - ein normales Inhaltsmodul muss dafür nichts extra tun. Läuft ein Inhalt noch über die klassische Ausgabelogik (echter Template-Ausgabecode, siehe <a href="#migration">Migration von GB1 nach GB2</a>), stehen die Werte dort direkt als Objekt zur Verfügung:</p>
					<pre>&lt;?php
echo $contentsettings->template->marginBottom;   // Spalten-Zeile (GB1-Alt-Vorlage)
echo $contentsettings->column_1->bgColor;        // Spalte 1
?&gt;</pre>

					<p>Und komplett als Array über <code>rex_gridblock2::getSettings()</code> - beide sind ausschließlich im klassischen Ausgabeweg gefüllt, nicht innerhalb nativ im Builder erstellter Sektion/Spalten-Inhalte.</p>

					<h3 id="contentsettings-modules">Blockeinstellungen (Zweig "modules")</h3>

					<p>Mit dem Zweig <code>modules</code> definierst du Einstellungen, die in jedem Block gleich sein sollen - z.B. Animation (AOS), Sichtbarkeit oder eine HTML-ID - statt sie in jedem Modul einzeln nachzubauen. Gridblock² liefert dafür keine Felder mit: Du definierst sie selbst (wie alle anderen Contentsettings) und baust die Werte in deine Ausgabe ein.</p>
					<ul>
					  <li>Am Block erscheint ein Zahnrad-Symbol (nur, wenn für diesen Block Felder gelten). Bei Sektion und Spalten-Zeile erscheinen die Felder im vorhandenen Einstellungen-Dialog unter "Blockeinstellungen".</li>
					  <li>Der Zweig gilt für <strong>alle</strong> Blockarten: REDAXO-Module, Custom-Elemente und die System-Elemente Sektion (<code>section</code>) und Spalten-Zeile (<code>column</code>) - auch für künftige.</li>
					  <li>Sektion und Spalten-Zeile haben bereits eigene Einstellungen. Möchtest du die Blockeinstellungen dort nicht haben, setze im Zweig <code>"includeSystem": false</code> - sie gelten dann nur für REDAXO-Module und Custom-Elemente (Standard: <code>true</code>). Das gilt für alle Felder des Zweigs gleichzeitig; einzelne Felder steuerst du weiterhin per <code>restrict</code>.</li>
					  <li>Ist der Zweig nicht definiert, ändert sich nichts.</li>
					</ul>

					<p>Beispiel (in <code>contentsettings.json</code>):</p>
					<pre>{
  "options": [
    { "key": "aos", "category": "other", "label": "Animation", "type": "select",
      "data": { "": "- keine -", "fade-up": "Von unten einblenden", "zoom-in": "Zoom" }, "default": "" },
    { "key": "visibility", "category": "other", "label": "Sichtbarkeit", "type": "radio",
      "data": { "": "Immer", "d-none d-md-block": "Nur Desktop", "d-md-none": "Nur mobil" }, "default": "" },
    { "key": "htmlId", "category": "other", "label": "HTML-ID", "type": "text" }
  ],
  "modules": {
    "showOptions": ["aos", "visibility", "htmlId"]
  }
}</pre>

					<h3>Felder auf bestimmte Blöcke beschränken (restrict)</h3>

					<p>Mit <code>restrict</code> legst du je Feld (oder Gruppe) fest, für welche Blöcke es gilt. <code>allow</code> = nur diese Blöcke, <code>deny</code> = alle außer diesen (<code>deny</code> gewinnt). Ohne Eintrag gilt ein Feld für alle Blöcke. Die Blöcke werden nach Art benannt: <code>redaxo</code> (Modul-ID oder Modul-Schlüssel), <code>custom</code> (Element-Schlüssel, also der Ordnername) und <code>system</code> (<code>section</code>, <code>column</code>). <code>"*"</code> steht für alle Blöcke dieser Art.</p>
					<pre>"modules": {
  "showOptions": ["aos", "visibility", "htmlId"],
  "restrict": {
    "aos":        { "deny":  { "system": ["column"] } },
    "visibility": { "allow": { "redaxo": [12, "text_bild"], "custom": ["mein_element"] } },
    "htmlId":     { "allow": { "redaxo": ["*"] } }
  }
}</pre>

					<h3>Werte in der Ausgabe nutzen</h3>

					<p>Die Werte stehen in allen Ausgaben von Gridblock² als <code>$_gb-&gt;cs-&gt;modules</code> bereit - im Output von REDAXO-Modulen, in <code>output.php</code> von Custom-Elementen sowie in den Ausgaben der Sektion und der Spalten-Zeile. Fehlt ein Feld (z.B. weil es für diesen Block nicht gilt) oder läuft das Modul außerhalb von Gridblock², verhindert <code>??</code> eine Warnung:</p>
					<pre>&lt;?php
$aos = $_gb-&gt;cs-&gt;modules-&gt;aos ?? '';
$id  = $_gb-&gt;cs-&gt;modules-&gt;htmlId ?? '';
?&gt;
&lt;div&lt;?= $id ? ' id="'.rex_escape($id).'"' : '' ?&gt;&lt;?= $aos ? ' data-aos="'.rex_escape($aos).'"' : '' ?&gt;&gt;
  ...
&lt;/div&gt;</pre>
					<ul>
					  <li>Gridblock² schreibt nichts vor: Wie (und ob) die Werte ausgegeben werden, entscheidest du in deinem Output. Gibt ein Modul bereits eigene Einstellungen aus, achtest du selbst auf Doppelungen.</li>
					  <li><code>$_gb</code> ist ein reservierter Name. Beginnt der Modul-Output mit <code>&lt;?php declare(...)</code> oder <code>namespace</code>, steht <code>$_gb</code> dort nicht zur Verfügung.</li>
					  <li>Die Werte werden am Block gespeichert und gelten in der Live-Vorschau und im Frontend.</li>
					</ul>
					</section>


					<!-- Vorschau im Builder (iframe & Shadow-DOM) -->
					<section class="gb-help-topic" id="vorschau">
					<h2>Vorschau im Builder: iframe &amp; Shadow-DOM</h2>

					<p>Für die Live-Vorschau im Builder kommen je nach Elementtyp zwei Techniken zum Einsatz:</p>
					<ul>
					  <li><strong>Inhaltsmodule</strong> (Elemente ohne eigene Kind-Elemente) rendern bei einer Neuinstallation standardmäßig per <strong>Shadow-DOM</strong> (schnell, nur CSS). Wer echtes JavaScript in der Vorschau braucht (Bildslider, Einblendeffekte & Co.), stellt in den Einstellungen auf <code>&lt;iframe&gt;</code> um - dann läuft die Vorschau mit deinem echten Website-CSS <em>und</em> -JavaScript (siehe Einstellungen "Frontend-CSS/-JS für Backend-Vorschau"). Bestehende Installationen behalten ihre bisherige Einstellung.</li>
					  <li><strong>Sektion</strong>, <strong>Spalten</strong> und <strong>Spalten-Slot</strong> (Container mit weiterhin direkt bedienbaren Kind-Elementen) rendern per <strong>Shadow-DOM</strong> - bewusst nur mit CSS, ohne JavaScript.</li>
					  <li><strong>Custom-Elemente</strong> rendern standardmäßig ebenfalls per Shadow-DOM, obwohl sie - wie Inhaltsmodule - keine eigenen Kind-Elemente haben: eigenes JavaScript ist hier normalerweise nicht nötig, da es sich um projekteigenes, vertrauenswürdiges Markup handelt. Bei Bedarf (z.B. eigenes JS innerhalb eines Custom-Elements) schaltet die Einstellung "Custom-Elemente ebenfalls per iframe anzeigen" (nur bei iframe-Vorschau-Modus sichtbar) auf echtes iframe mit echtem JavaScript um.</li>
					</ul>

					<h3>Warum der Unterschied?</h3>

					<p>Kind-Elemente in Sektion/Spalten/Slot müssen im Builder weiter ganz normal bedienbar bleiben (verschieben, bearbeiten, löschen). Ein <code>&lt;slot&gt;</code> im Shadow-DOM reicht diese echten Elemente unverändert durch die Vorschau-Hülle durch. Ein <code>&lt;iframe&gt;</code> kann das nicht - es ist ein komplett eigenständiges Dokument. Deshalb bleiben Sektion/Spalten/Slot bei Shadow-DOM, nur "blattlose" Inhaltsmodule wechseln gefahrlos ins iframe.</p>

					<h3>Was das für die Shadow-DOM-Vorschau bedeutet (Inhaltsmodule ausgenommen)</h3>
					<ul>
					  <li><strong>Kein JavaScript läuft.</strong> Effekte, die JS brauchen (Einblendeffekte, Parallax), werden in der Vorschau nicht berechnet - auf der echten Seite funktionieren sie ganz normal, es ist reine Vorschau-Kosmetik. Bibliotheken wie AOS, die Inhalte per CSS erst unsichtbar machen und dann per JS einblenden, würden sie in der Vorschau sonst <strong>dauerhaft verstecken</strong> - dagegen gibt's das Feld "Sichtbarkeits-Korrekturen für die Vorschau" (Standardwert deckt AOS schon ab).</li>
					  <li><strong>Globale body/html-Regeln</strong> (z.B. Schriftart, Textfarbe, Zeilenhöhe, oder auch der Website-Hintergrund) wirken nicht, weil es im Shadow-DOM kein echtes <code>body</code> gibt. Dafür zwei feste, für dich gedachte Hook-Klassen, die du in deiner eigenen CSS-Datei (Einstellung "Frontend-CSS für Backend-Vorschau") mit den gewünschten Werten befüllst - ohne Eintrag passiert nichts, keine eigene Einstellung nötig:
						<ul>
						  <li><code>.gb2-preview-content</code> umschließt in JEDER Shadow-DOM-Vorschau (Inhaltsmodul im Shadow-DOM-Modus, Custom-Element, Sektion, Spalten) den eigentlichen Inhalt, direkt innerhalb desselben Shadow-Roots - eine ganz normale CSS-Klasse, deine Regel greift wie gewohnt. In der iframe-Vorschau (siehe oben) landet dieselbe Klasse direkt auf dem dortigen echten <code>&lt;body&gt;</code> - dort zwar meist redundant zu eigenen <code>body</code>-Regeln, aber praktisch für Regeln, die gezielt beide Vorschau-Arten gleich behandeln sollen (siehe nächster Punkt).</li>
						  <li><code>.gb2-preview-body</code> gilt EINMALIG für den gesamten Builder-Bereich (<code>.gb-builder-body</code>), der im Backend selbst liegt, nicht in einem Shadow-Root - typischerweise für den Website-Hintergrund gedacht. Hat die echte Website einen breitenbegrenzten Content-Container (z.B. <code>max-width: 1700px</code>), hier ebenfalls eintragen, damit die Editier-Ansicht (v.a. im Vollbildmodus) mit der echten Ausgabe übereinstimmt. Regeln für <code>.gb2-preview-body</code> werden auch aus dem CSS des Extension-Points <code>GRIDBLOCK2_PREVIEW_CSS</code> übernommen, ein Addon kann sie also selbst mitliefern (z.&nbsp;B. den Seitenhintergrund).</li>
						</ul>
						<pre>.gb2-preview-content {
	font-family: var(--font-text);
	font-size: var(--fontsize-text);
	line-height: var(--lineheight-text);
	hyphens: auto;
}

.gb2-preview-body {
	background: #000;
}</pre>
					  </li>
					  <li><strong><code>rem</code>-Einheiten rechnen gegen den FALSCHEN Basiswert.</strong> Anders als eigene CSS-Variablen lässt sich <code>rem</code> nicht per <code>:host</code> umdefinieren - der Browser bezieht <code>rem</code> laut Spezifikation IMMER auf die Schriftgröße des echten <code>&lt;html&gt;</code>-Elements des aktuellen Dokuments, egal wie tief verschachtelt in Shadow-DOM oder iframe. Setzt eure Website per eigenem CSS-Reset eine abweichende Basis (z.B. <code>html { font-size: 19px; }</code> statt des Browser-Standards), greift dieser Reset nur INNERHALB der Vorschau-Hülle selbst - das echte <code>&lt;html&gt;</code> der REDAXO-Backend-Seite bleibt bei seiner eigenen, meist kleineren Basis (z.B. 15px). Jeder <code>rem</code>-Wert berechnet sich dadurch in der Vorschau gegen eine andere Basis als im echten Frontend:
						<pre>/* Website-Basis 19px, Backend-Basis 15px */
font-size: clamp(24px, 4vw, 2.2rem);
/* Frontend: 2.2rem = 41,8px (korrekte Obergrenze) */
/* Vorschau: 2.2rem = 33px (zu niedrige Obergrenze -
   deckelt clamp() u.U. schon unterhalb des Minimums) */</pre>
						Lösung: für Vorschau-kritische Werte <code>rem</code> durch <code>px</code> ersetzen (z.B. <code>clamp(24px, 4vw, 42px)</code>) - dann stimmen Vorschau und Frontend exakt überein. Bewusste <code>rem</code>-Nutzung (z.B. für globale Skalierbarkeit per Root-Font-Size) bleibt dagegen ein strukturelles Vorschau-Limit, das sich nicht umgehen lässt.
						<br><br><strong>Tipp - barrierefrei und trotzdem identische Vorschau:</strong> Statt <code>rem</code> überall direkt zu schreiben, hinterlegst du die Basisgröße EINMAL als CSS-Variable und leitest alle anderen Größen per <code>calc()</code> davon ab. Im Frontend bleibt die Variable in <code>rem</code> (wächst mit der Schrifteinstellung des Browsers), nur die Vorschau überschreibt sie mit einem festen Pixelwert:
						<pre>/* Frontend-CSS (Website) */
:root {
	--fontsize-text: 1.1875rem;   /* = 19px bei Browser-Standard */
	--fontsize-header: clamp(24px, 4vw, calc(var(--fontsize-text) * 2.2));
}

/* Frontend-CSS für Backend-Vorschau (nur im Shadow-DOM geladen) */
:host {
	--fontsize-text: 19px !important;   /* feste Basis, damit calc() in der Vorschau exakt aufgeht */
}</pre>
						Im Frontend rechnet <code>--fontsize-header</code> weiter mit dem Browser-Wert (stellt ein Besucher die Schrift auf "sehr groß", wächst die Überschrift proportional mit), in der Vorschau mit 19px - dort sieht sie damit so aus wie beim Besucher mit Standard-Einstellung.
					  </li>
					  <li><strong>Farbschema/Theme aus einer Spalte an verschachtelte Inhaltselemente durchreichen:</strong> jedes verschachtelte Inhaltselement hat seine eigene, komplett getrennte Vorschau (eigener Shadow-Root ODER eigenes iframe-Dokument) - weder CSS-Vererbung noch eine CSS-Variable von der umschließenden Spalte aus erreicht es dort hinein. Setzt <code>column.php</code> (siehe <a href="#builder">Der Builder</a>) auf einem eigenen Element <code>data-gb2-theme-class="..."</code> und/oder <code>data-gb2-theme-style="..."</code>, hängt der Builder diese Werte automatisch an die <code>.gb2-preview-content</code>-Hülle JEDES darin liegenden Inhaltselements an - unabhängig vom jeweiligen Vorschau-Modus (Shadow-DOM-Hülle oder iframe-<code>body</code>, siehe oben), auch verschachtelt, auch in eigene Custom-Elemente hinein. Was genau durchgereicht wird, entscheidest ausschließlich du in <code>column.php</code>.</li>
					  <li><strong>Viewport-Umschalter (Smartphone/Tablet)</strong> verengt nur sichtbar den Vorschau-Bereich - normale <code>@media (max-width: ...)</code>-Regeln werten aber immer die echte Fensterbreite aus, nicht diesen Bereich. Container-Queries lösen das, weil sie sich auf die tatsächliche Element-Breite beziehen:
						<pre>:host { container-type: inline-size; }

/* statt @media (max-width: 600px) */
@container (max-width: 600px) {
	.card { display: block; }
}</pre>
						<strong>Nebeneffekt:</strong> <code>container-type</code> macht das Element zum Containing Block für <code>position: fixed</code>-Nachfahren. Sitzt <code>container-type: inline-size</code> direkt auf <code>body</code> und irgendwo im Markup ein fixiertes Element (Navigationsleiste, Cookie-Banner), rutscht dessen Positionierung fälschlich mit um. Setze <code>container-type</code> deshalb lieber auf einen Wrapper-<code>&lt;div&gt;</code> innerhalb von <code>body</code>, der die fixierten Elemente ausschließt.
					  </li>
					</ul>

					<h3 id="vorschau-extension-css">Dynamisches CSS aus anderen Addons (Extension-Point <code>GRIDBLOCK2_PREVIEW_CSS</code>)</h3>

					<p>Die Vorschau lädt nur die Dateien aus den Einstellungen (<code>.css</code>/<code>.js</code> im Webroot). Erzeugt dein Projekt CSS dynamisch - etwa Projektfarben je Domain, die ein anderes Addon per PHP in die Seite schreibt - kommt das in der Vorschau sonst nicht an. Dafür gibt es den Extension-Point <code>GRIDBLOCK2_PREVIEW_CSS</code>: Dein Addon liefert CSS-Text, Gridblock² bettet ihn in <strong>beide</strong> Vorschau-Arten ein (iframe und Shadow-DOM), nach den Dateien aus den Einstellungen.</p>

					<pre>rex_extension::register('GRIDBLOCK2_PREVIEW_CSS', function (rex_extension_point $ep) {
	// Subject = bisheriges CSS (Text), Rückgabe = erweitertes CSS
	$domainId = $ep->getParam('domainId');
	return $ep->getSubject() . ':root { --farbe-primary: ' . MeinAddon::farbe($domainId) . '; }';
});</pre>

					<p><strong>Parameter</strong> (<code>$ep->getParam('...')</code>): <code>articleId</code>, <code>clangId</code>, <code>sliceId</code> (Artikel, Sprache und Slice des bearbeiteten Inhalts), <code>domainId</code> (YRewrite-ID, 0 ohne YRewrite), <code>domainName</code>, <code>domainUrl</code> und <code>yrewriteDomain</code> (das Domain-Objekt oder <code>null</code>).</p>
					<ul>
					  <li><strong><code>:root</code> funktioniert auch im Shadow-DOM:</strong> Dort gibt es kein <code>:root</code>, deshalb setzt Gridblock² es für die Shadow-DOM-Vorschau automatisch zu <code>:host</code> um. CSS-Variablen greifen dadurch in beiden Arten gleich. Dasselbe gilt für <code>html:root</code> sowie für Regeln, die nur auf <code>html</code> oder <code>body</code> zielen (z.&nbsp;B. <code>body { font-size: ...; }</code>): Sie wirken im Shadow-DOM auf den Vorschau-Inhalt. Damit der Vorschau-Container transparent und ohne eigenen Rahmen bleibt, werden aus diesen <code>html</code>-/<code>body</code>-Regeln die Eigenschaften <code>background</code>, <code>padding</code>, <code>margin</code>, <code>scroll-padding</code>, <code>overflow</code>, <code>height</code>, <code>min-height</code> und <code>max-height</code> entfernt (jeweils samt Varianten wie <code>background-color</code>; CSS-Variablen bleiben). Die Liste lässt sich über den Extension-Point <code>GRIDBLOCK2_PREVIEW_CSS_HOST_IGNORE</code> ändern: <code>rex_extension::register('GRIDBLOCK2_PREVIEW_CSS_HOST_IGNORE', fn (rex_extension_point $ep) => array_diff($ep->getSubject(), ['padding']));</code>. Steht <code>body</code> in einer Regel zusammen mit anderen Selektoren (<code>body, .card { ... }</code>), gilt der Filter für die ganze Regel. Deine eigenen Frontend-CSS-Dateien aus den Einstellungen sind davon nicht betroffen.</li>
						  <li><strong>Schriften (<code>@font-face</code>):</strong> <code>@font-face</code>-Regeln in diesem CSS werden für die Shadow-DOM-Vorschau automatisch in die Seite übernommen, weil Schriftregeln im Shadow-DOM selbst nicht wirken.</li>
						  <li><strong><code>rem</code> im Backend:</strong> Das Backend hat eine eigene Basisgröße (10&nbsp;px), das Frontend meist 16&nbsp;px. Liefert dein Addon Größen in <code>rem</code>, rechne sie in diesem CSS in <code>px</code> um, sonst erscheint die Vorschau kleiner als im Frontend.</li>
					  <li><strong>Nur CSS, kein JavaScript:</strong> Der Extension-Point nimmt ausschließlich Text für ein <code>&lt;style&gt;</code> entgegen. Ein <code>&lt;</code> im Text wird maskiert, es kann also kein Markup oder Skript eingeschleust werden.</li>
					  <li><strong>Ohne registrierte Funktion ändert sich nichts.</strong> Platzhalter, die ein Addon sonst per Output-Filter im Frontend ersetzt, laufen in der Vorschau nicht mit - rufe in deiner Funktion stattdessen dieselbe PHP-Funktion auf, die den Platzhalter füllt.</li>
					  <li>Die Funktion läuft bei jedem Vorschau-Aufruf: kurz halten und bei Bedarf selbst zwischenspeichern (z.&nbsp;B. <code>rex_cache</code>).</li>
					</ul>

					<h3>Ohne eigene CSS-Änderung</h3>

					<p>Die Einstellung "Media-Queries für die Vorschau als Container-Queries übersetzen" übernimmt genau diese Übersetzung automatisch, nur für die Builder-Vorschau - dein echtes Website-CSS bleibt unangetastet. Übersetzt werden reine Breiten-Bedingungen (<code>min-width</code>/<code>max-width</code> in px/em/rem, nur mit <code>and</code> kombiniert); alles andere (<code>print</code>, <code>prefers-color-scheme</code>, <code>hover</code>, ...) bleibt normales <code>@media</code> und reagiert weiterhin auf die echte Fensterbreite. Die übersetzten Regeln messen dabei gegen die Breite des Builder-Bereichs (inkl. Viewport-Umschalter), nicht gegen die Breite des einzelnen Moduls bzw. der Spalte. Einheiten wie <code>vw</code>/<code>vh</code> werden nicht umgerechnet und beziehen sich weiterhin auf das echte Fenster.</p>
					</section>


					<!-- Projektfarben -->
					<section class="gb-help-topic" id="projektfarben">
					<h2>Projektfarben (Palette)</h2>

					<p>Mit den Projektfarben bekommen Redakteure statt freier Farbfelder eine Auswahl der Farben, die zu deinem Projekt gehören. Die Palette ist ein JSON-Text in den Einstellungen ("Projektfarben (Palette)"). Ohne Eintrag bleibt alles wie bisher. Welche Elemente die Palette nutzen und wie, steht in deren <code>README.md</code> im Elementordner.</p>

					<h3>Format</h3>

					<p>Je Farbe ein Eintrag. Der Name des Eintrags ist der feste <strong>Key</strong>, darunter stehen die Felder:</p>
<pre>{
  "primary": {
    "label": "Primärfarbe",
    "class": ".bgCol-primary",
    "color": "var(--col-primary)",
    "text_color": "var(--col-primary-text)",
    "hover_color": "var(--col-primary-opa)",
    "hover_text_color": "#ffffff"
  },
  "white": {
    "label": "Weiß",
    "color": "var(--col-white)",
    "text_color": "var(--col-white-text)"
  }
}</pre>
<table class="table table-striped">
<thead><tr><th>Feld</th><th>Pflicht</th><th>Bedeutung</th></tr></thead>
<tbody>
<tr><td><code>label</code></td><td>ja</td><td>Bezeichnung im Auswahlfeld</td></tr>
<tr><td><code>class</code></td><td>nein</td><td>CSS-Klasse (mit oder ohne Punkt, mehrere durch Leerzeichen). Wird nur ausgegeben und ist nie der Key</td></tr>
<tr><td><code>color</code></td><td>ja, wenn keine <code>class</code> angegeben ist</td><td>Hauptfarbe: Hintergrund bei gefüllten Flächen, Schrift- und Rahmenfarbe bei umrandeten Buttons, Textlinks und Farbfeldern</td></tr>
<tr><td><code>text_color</code></td><td>nein</td><td>Schriftfarbe auf der Hauptfarbe (z.&nbsp;B. Weiß auf dem Primärton)</td></tr>
<tr><td><code>hover_color</code></td><td>nein</td><td>Hauptfarbe beim Überfahren</td></tr>
<tr><td><code>hover_text_color</code></td><td>nein</td><td>Schriftfarbe beim Überfahren</td></tr>
</tbody></table>
<ul>
  <li><strong>Der Key</strong> (<code>primary</code>, <code>white</code>, ...) erlaubt Kleinbuchstaben, Ziffern, <code>-</code> und <code>_</code> (höchstens 40 Zeichen). Er wird in den Inhalten gespeichert und darf sich später <strong>nicht mehr ändern</strong>. Bezeichnung und Klasse dürfen sich jederzeit ändern, schon gewählte Farben bleiben erhalten.</li>
  <li><strong>Jeder Farbwert</strong> ist eine CSS-Variable (<code>var(--name)</code> oder <code>--name</code>), ein Farbwert (Hex, <code>rgb()</code>, <code>rgba()</code>, <code>hsl()</code>, <code>hsla()</code>) oder <code>transparent</code>. Farbnamen wie <code>red</code> sind nicht erlaubt. Variablen bleiben beim Speichern als <code>var(--name)</code> erhalten, ändert dein Projekt eine Variable, ziehen alle Inhalte mit.</li>
  <li>Beim Speichern prüft Gridblock² jeden Eintrag und nennt bei einem Fehler den Key (auch unbekannte Felder und doppelte Keys). Dann wird nichts gespeichert. Ungültiges JSON meldet Gridblock² als Ganzes.</li>
  <li><strong>Mehrere Klassen:</strong> Mehrere Klassen stehen im Feld <code>class</code> durch Leerzeichen getrennt (<code>.uk-background-primary .uk-light</code>).</li>
</ul>

					<h3>Rückgabewert und Klassen (UIkit und andere Frameworks)</h3>

					<ul>
					  <li><strong>Mit Klasse:</strong> Die Klasse ist der gespeicherte und ausgegebene Wert. Die Hauptfarbe (<code>color</code>) ist dann nur die Anzeigefarbe im Auswahlfeld (und die Farbe für die Hover-Effekte), sie wird nicht ausgegeben.</li>
					  <li><strong>Ohne Klasse:</strong> Die Hauptfarbe (<code>color</code>) ist der Rückgabewert, genau so wie sie im Eintrag steht (<code>#1e87f0</code>, <code>rgba(...)</code> oder <code>var(--col-primary)</code>).</li>
					  <li>Dadurch funktioniert die Palette mit jedem CSS-Framework: Wer Klassen nutzt, trägt sie ein, wer Variablen oder Werte nutzt, die Farbe. Gemeint sind <strong>Farbklassen</strong> mit einer Aufgabe (Hintergrund oder Schrift), keine kompletten Button-Klassen wie <code>.uk-button-primary</code>: Die würden Rundung und die übrigen Einstellungen des Elements aushebeln.</li>
					  <li><strong>Die Felder gehören zusammen:</strong> <code>color</code> ist die Farbe selbst, <code>text_color</code> ist die Schrift <em>auf</em> dieser Farbe (die Gegenfarbe, z.&nbsp;B. Weiß). Wie ein Element die Farben verwendet (z.&nbsp;B. Stile oder Hover-Effekte), steht in dessen <code>README.md</code>.</li>
					  					  <li>Besonderheiten deines Frameworks (z.&nbsp;B. Button-Klassen oder eigene Klassen je Stil) löst du in einer angepassten Kopie des Elements, siehe <a href="#elemente-anpassen">Angepasste Elemente</a>. Die Palette selbst bleibt dafür bewusst allgemein.</li>
					  					  <li><strong>Was eine Klasse bewirkt, bestimmt das Framework</strong>, Gridblock² nimmt nichts davon an. Bei UIkit setzt <code>.uk-background-primary</code> nur <code>background-color</code> (für helle Schrift <code>.uk-light</code> in dieselbe Zelle: <code>.uk-background-primary .uk-light</code>), <code>.uk-text-primary</code> setzt <code>color</code> mit <code>!important</code> und Button-Klassen wie <code>.uk-button-primary</code> setzen Hintergrund, Farbe, Rahmen und Hover. Eine Text-Zelle (Inline-Farbe) kann an <code>!important</code> einer Klasse scheitern.</li>
					</ul>

					<h3>Contentsettings</h3>

					<p>In der <code>contentsettings.json</code> bekommt ein Feld vom Typ <code>colorpicker</code> die Option <code>"palette": true</code>, zum Beispiel <code>{"key": "bgColorHEX", "type": "colorpicker", "palette": true, "mode": "both"}</code>. Der Redakteur sieht dann ein Auswahlfeld mit Suche und Farbfeldern: "- keine Farbe -", die Projektfarben und "Individuell". "Individuell" öffnet den normalen Colorpicker. Mit <code>"mode": "both"</code> kann der Picker auch Verläufe (Standard ist nur eine Farbe). Ohne die Option oder ohne Palette bleibt es beim normalen Picker.</p>
					<ul>
					  <li><strong>Gespeichert</strong> wird der Rückgabewert des Eintrags (Klasse oder <code>color</code>), nicht der Key. Ändert sich eine Klasse später, behalten schon gespeicherte Inhalte die alte Klasse. Ältere Werte werden wiedererkannt, wenn sie einem Rückgabewert entsprechen (z.&nbsp;B. <code>bgCol-primary</code> zu einer Zeile mit dieser Klasse). Ein unbekannter Klassenwert erscheint oben als "Alt: ...", ein anderer Wert (Hex, Verlauf) als "Individuell". Es geht nichts verloren, solange der Redakteur nichts ändert.</li>
					  <li><strong>Immer die Farbe statt der Klasse:</strong> Mit <code>"palette": "colors"</code> statt <code>true</code> liefert jeder Eintrag seine <code>color</code> (z.&nbsp;B. <code>var(--col-primary)</code>) und nie die Klasse. Das ist für Felder gedacht, deren Wert als echte CSS-Farbe ausgegeben wird, etwa Rahmenfarbe oder Trennerfarbe (<code>borderColorHEX</code>, <code>formDivTopColorHEX</code>): Eine Hintergrundklasse wie <code>bgCol-primary</code> wäre dort wirkungslos. Einträge ohne <code>color</code> erscheinen in solchen Feldern nicht. Bereits gespeicherte Klassenwerte, die zu einem Eintrag gehören, werden in der Auswahl wiedererkannt.</li>
					  <li><strong>Zentral ändern:</strong> Klassen und Variablen (<code>var(--col-primary)</code>) lassen sich im CSS zentral anpassen. Ein reiner Hex-Wert wird dagegen als Kopie im Inhalt gespeichert: Ändert der Admin ihn später in der Palette, behalten schon gespeicherte Inhalte den alten Wert. Für zentrale Pflege daher Klassen oder Variablen nutzen.</li>
					  <li><strong>Ausgabe:</strong> Damit die Systemelemente (Sektion, Spalten-Zeile, Spalte) den Wert richtig ausgeben, nutzen sie <code>rex_gridblock2_helper::getSectionStyles($cs, array('background-auto' => 'bgColorHEX'))</code> und für die Klassen <code>'bgColorHEX|class'</code> in der Klassenliste. Dabei geht eine Klasse ins <code>class</code>-Attribut, ein Verlauf als <code>background-image</code> und jede andere Farbe als <code>background-color</code> in den Style. Eigene Templates oder Module, die das Feld selbst ausgeben, brauchen dieselbe Unterscheidung.</li>
					  <li>Module, die den Farbwert in PHP weiterverarbeiten (z.&nbsp;B. Hex in RGB), kommen mit Klassen und <code>var(...)</code> nicht klar. Die Palette deshalb nur bei Feldern einschalten, deren Wert direkt in CSS oder als Klasse landet.</li>
					</ul>

					<h3>Farbfelder im Backend</h3>

					<p>Das Backend kennt die Variablen deines Projekts nicht. Gridblock² löst sie deshalb mit den <code>:root</code>-Variablen aus deinem "Frontend-CSS für Backend-Vorschau" (und dem Extension-Point <code>GRIDBLOCK2_PREVIEW_CSS</code>) auf und färbt damit die Auswahl. Steht eine Variable dort nicht unter <code>:root</code>, zeigt die Auswahl ein graues Feld mit "?" - im Frontend funktioniert sie trotzdem. Feste Farbwerte brauchen das nicht.</p>

					<h3>Mehrere Domains</h3>

					<p>Ist die Multi-Site-Einstellung für die Vorschau aktiv, kann jede Domain unter "Individuell" eine eigene Palette bekommen. "Zusammenführen" ergänzt den Standard um die Einträge der Domain (gleicher Key: die Domain gewinnt), "Ersetzen" nimmt nur die Einträge der Domain. Die Domain ergibt sich aus dem bearbeiteten Artikel.</p>

					<h3>Palette aus einem anderen Addon (Extension-Point <code>GRIDBLOCK2_PALETTE</code>)</h3>

					<p>Pflegt dein Projekt die Farben in einem eigenen Addon, kann es die Palette dynamisch liefern, ohne dass du sie in Gridblock² einträgst. Der Extension-Point bekommt den fertigen JSON-Text der Palette (Einstellungen, ggf. mit Domain-Palette) und gibt die erweiterte Palette zurück, entweder als JSON-Text oder als PHP-Array im selben Aufbau:</p>

					<pre>rex_extension::register('GRIDBLOCK2_PALETTE', function (rex_extension_point $ep) {
	$palette = json_decode($ep->getSubject() ?: '{}', true) ?: [];
	$c = MeinAddon::farben($ep->getParam('domainId'));
	$palette['primary'] = ['label' => 'Primärfarbe', 'color' => $c['primary'], 'text_color' => $c['primary_text']];
	return $palette;
});</pre>

					<ul>
					  <li><strong>Parameter</strong> (<code>$ep->getParam('...')</code>): <code>articleId</code>, <code>clangId</code>, <code>sliceId</code>, <code>domainId</code> (YRewrite-Domain des Artikels, 0 ohne YRewrite), <code>domainName</code>, <code>domainUrl</code>, <code>yrewriteDomain</code>. Wie beim Extension-Point <code>GRIDBLOCK2_PREVIEW_CSS</code>.</li>
					  <li><strong>Im Backend und im Frontend:</strong> Der Aufruf läuft beim Bearbeiten (Auswahlfeld) und beim Ausgeben (Zuordnung der Farben). Im Frontend wird der aktuelle Artikel zugrunde gelegt.</li>
					  <li>Einträge mit Fehlern werden ignoriert und ins REDAXO-Log geschrieben (Warnung "Gridblock2 Projektfarben"), ungültiges JSON ergibt eine leere Palette. Liefert die Funktion weder Text noch ein Array, gilt die Palette der Einstellungen.</li>
					  <li>Das Ergebnis wird pro Artikel, Sprache und Domain nur einmal je Seitenaufruf berechnet. Die Funktion sollte trotzdem kurz sein oder selbst zwischenspeichern.</li>
					  <li><strong>Variablen und Palette zusammen:</strong> Verweist die Palette auf CSS-Variablen (<code>--col-primary</code>), liefert dein Addon deren Werte im Backend über <code>GRIDBLOCK2_PREVIEW_CSS</code> (<code>:root { --col-primary: ...; }</code>). Dann sind die Einträge für alle Projekte gleich und der Extension-Point <code>GRIDBLOCK2_PALETTE</code> ist nur nötig, wenn sich die Liste selbst je Projekt unterscheidet.</li>
					</ul>
					</section>


					<!-- Spalten-Abstand -->
					<section class="gb-help-topic" id="spaltenabstand">
					<h2>Spalten-Abstand: Contentsettings-Zuordnung &amp; eigene Werte</h2>

					<p>Das native Spalten-Element bringt ein eigenes Abstand-Feld mit ("Abstand zwischen den Spalten"). Kommst du von Gridblock 1.x, gibt es dafür meist schon ein eigenes Contentsettings-Feld (z.B. "Spaltenabstand (Gap)" / <code>colsGap</code>). Ohne weitere Konfiguration laufen beide unabhängig nebeneinander her - das native Feld hat dann keine Wirkung auf die Ausgabe.</p>

					<h3>Zuordnung einrichten (Einstellung "Contentsettings-Feld für den Spalten-Abstand")</h3>

					<p>Ein durchsuchbares Auswahlfeld listet alle vorhandenen Contentsettings-Felder auf, gruppiert nach Template-/Spalten-/Spalten-Zeile-Einstellungen. Feld auswählen, speichern, fertig - keine manuelle Eingabe interner Feldnamen nötig. Ab dann:</p>
					<ul>
					  <li>Das native Dropdown übernimmt automatisch die im Contentsettings-Feld hinterlegten Werte - beide Felder nutzen dieselbe Werte-Domäne.</li>
					  <li>Beim Speichern schreibt Gridblock² den nativen Wert zusätzlich 1:1 ins zugeordnete Contentsettings-Feld zurück - beide bleiben konsistent, gepflegt wird aber nur noch über das native Feld.</li>
					  <li>Das zugeordnete Contentsettings-Feld verschwindet automatisch aus dem Bearbeiten-Formular (kein doppeltes Feld mehr).</li>
					  <li>Bestandsinhalte, die noch nicht über den Builder neu gespeichert wurden, zeigen weiterhin ihren ursprünglichen Contentsettings-Wert - die Zuordnung greift erst ab dem nächsten Speichern.</li>
					</ul>

					<h3>Eigene Abstufungen ohne Zuordnung (Einstellung "Eigene Werte für den nativen Spalten-Abstand")</h3>

					<p>Nur relevant ohne obige Zuordnung. Ersetzt die mitgelieferten Standardabstufungen durch eigene, als JSON-Objekt mit einem Eintrag je Abstufung:</p>
					<pre>{
  "xs": {"label": "Sehr klein", "value": "8px"},
  "sm": {"label": "Klein", "value": "16px"},
  "md": {"label": "Mittel", "value": "24px"},
  "lg": {"label": "Groß", "value": "40px"},
  "xl": {"label": "Sehr groß", "value": "64px"}
}</pre>
					<p>Das ältere Zeilenformat (<code>md = Mittel = 24px</code>, eine Zeile je Abstufung) wird weiterhin gelesen. Beim JSON-Format prüft das Speichern die Eingabe: Ungültiges JSON, ein ungültiger Schlüssel oder ein fehlendes <code>label</code> bzw. <code>value</code> werden gemeldet und nichts wird gespeichert.</p>
					<p>Was von den Angaben wo landet:</p>
					<ul>
					  <li><strong>Schlüssel</strong> (der Name des Eintrags, z.&nbsp;B. <code>md</code>; erlaubt: Buchstaben, Ziffern, <code>-</code> und <code>_</code>) - das ist der Teil, der tatsächlich im Frontend ankommt: Er wird 1:1 zur CSS-Klasse <code>gb-el-columns-gap-&lt;schlüssel&gt;</code> am Element. Dein eigenes Website-CSS muss auf genau diese Klasse reagieren, damit sich am Abstand wirklich etwas ändert.</li>
					  <li><strong>Bezeichnung</strong> (<code>label</code>) - nur die sichtbare Beschriftung im Auswahlfeld. Statt festem Text kann sie auch ein <code>rex_i18n</code>-Sprachschlüssel aus einem eigenen Sprachpaket sein - dann erscheint automatisch die Übersetzung in der jeweiligen Backend-Sprache; ohne passenden Schlüssel wird der Text unverändert angezeigt.</li>
					  <li><strong>Wert</strong> (<code>value</code>) - eine gültige CSS-<code>gap</code>-Einheit (<code>px</code>, <code>%</code>, <code>vw</code>, ...), aber <strong>nur für die Editor-Vorschau</strong> (Shadow-DOM-Annäherung im Backend). Die echte Ausgabe im Frontend hängt allein von deinem eigenen CSS ab, das auf die Schlüssel-Klasse reagiert - trägst du hier z.B. 24px ein, dein CSS für <code>.gb-el-columns-gap-md</code> setzt aber 32px, zeigt die Website 32px, nur die Vorschau im Backend würde mit 24px leicht abweichen.</li>
					</ul>
					</section>


					<!-- Alt-Text -->
					<section class="gb-help-topic" id="alt-text">
					<h2>Alt-Text der Bilder</h2>

					<p>Die Custom-Elemente von Gridblock² (Hero, Karten, Bildercollage) ermitteln den Alt-Text eines Medienpool-Bildes nach dieser Reihenfolge:</p>
					<ol>
					  <li>Ist am Medium der Haken <code>med_decorative</code> gesetzt, bleibt der Alt-Text leer (<code>alt=""</code>, dekoratives Bild).</li>
					  <li>Sonst gilt das Feld <code>med_alt</code>, sofern es gepflegt ist.</li>
					  <li>Sonst gilt der Titel des Mediums.</li>
					</ol>
					<p>Die beiden Felder gehören zum Addon "metainfo" (Medienpool) und können vom <a href="index.php?page=<?php echo rex_escape($mypage); ?>/default">Einrichtungs-Assistenten</a> angelegt werden, wenn sie fehlen. Fehlen sie, arbeitet alles wie bisher mit dem Titel, es entstehen keine Fehler. Die Einstellung gilt für das Medium, also an jeder Stelle gleich.</p>

					<p><strong>Eigene Module:</strong> Diese drei Zeilen lassen sich in ein Modul kopieren (<code>$file</code> ist der Dateiname aus dem Medienpool) und liefern dasselbe Ergebnis. Sie brauchen weder Gridblock² noch eines der beiden Felder:</p>
					<pre>$media = rex_media::get($file);
$deko = $media ? trim((string) $media->getValue('med_decorative'), '|0 ') : '';
$alt  = ($media && $deko === '') ? (trim((string) $media->getValue('med_alt')) ?: trim((string) $media->getTitle())) : '';</pre>
					<p>Das Ergebnis steht in <code>$alt</code> und gehört escaped ins Attribut: <code>alt="&lt;?php echo rex_escape($alt); ?&gt;"</code>.</p>
					</section>


					<!-- Modul & Platzhalter -->
					<section class="gb-help-topic" id="modul">
					<h2>Modul &amp; Platzhalter</h2>

					<p>Das Gridblock-Modul wird i.d.R. schon während der Einrichtung automatisch angelegt. Willst du ein eigenes Modul bauen oder ein bestehendes anpassen: Der Kommentar <strong>/* GRID_MODULE_IDENTIFIER | DONT REMOVE */</strong> markiert das Modul für Gridblock² - er darf nicht entfernt werden. Findet die Installation bzw. der Assistent ein bestehendes Modul mit diesem Kommentar (z.B. bei einer Migration von Gridblock 1.x), bleibt es unangetastet - eine Umstellung auf die neue Klasse erfolgt nur bewusst über den entsprechenden Button im Assistenten, individuelle Anpassungen gehen dabei nicht verloren.</p>

					<table width="100%" border="0" cellspacing="0" cellpadding="0">
					  <tr>
						<th width="200" scope="col">Modultyp</th>
						<th scope="col">Beispiel</th>
					  </tr>
					  <tr>
						<td valign="top"><strong>Moduleingabe (INPUT)</strong></td>
						<td valign="top"><pre>&lt;?php
/* GRID_MODULE_IDENTIFIER | DONT REMOVE */

$grid = new rex_gridblock2();
$grid-&gt;getSliceValues("REX_SLICE_ID");
$grid-&gt;getModules(); // befüllt $_SESSION['gridAllowedModules'] für das Add-Menü

// Optional vor getModules(), um die Modulauswahl einzuschränken:
// $grid-&gt;allowModule( array(1,2,3) );
// $grid-&gt;ignoreModule( array(2) );

$sliceID = (int) ($grid-&gt;rexVars['sliceID'] ?? 0);
$sliceValues = Gridblock2Builder_Tree::loadSliceValues($sliceID);

$structure = Gridblock2Builder_Tree::loadStructure($sliceValues);
$moduleValues = Gridblock2Builder_Tree::loadModuleValues($sliceValues);

echo Gridblock2Builder_Tree::renderRoot($structure, $moduleValues, $grid-&gt;rexVars);
?&gt;</pre></td>
					  </tr>
					  <tr>
						<td valign="top"><strong>Modulausgabe (OUTPUT)</strong></td>
						<td valign="top"><pre>&lt;?php
/* GRID_MODULE_IDENTIFIER | DONT REMOVE */

$grid = new rex_gridblock2();
$grid-&gt;getSliceValues("REX_SLICE_ID");

$sliceID = (int) ($grid-&gt;rexVars['sliceID'] ?? 0);
$sliceValues = Gridblock2Builder_Tree::loadSliceValues($sliceID);

if (Gridblock2Builder_Tree::hasNewFormat($sliceValues)):
	$structure = Gridblock2Builder_Tree::loadStructure($sliceValues);
	$moduleValues = Gridblock2Builder_Tree::loadModuleValues($sliceValues);
	$useSlicePreview = rex::isBackend() &amp;&amp; rex_gridblock2::getConfig('gb_builder_slicelist_preview') === 'checked';
	if ($useSlicePreview) { rex_gridblock2::isBuilderPreview(true); }

	$html = Gridblock2Builder_Tree::renderOutput($structure, $moduleValues, $grid-&gt;rexVars, $useSlicePreview);
	if ($useSlicePreview):
		echo '&lt;div class="gb-slicepreview-host" data-slice-id="'.$sliceID.'"&gt;'.Gridblock2Builder_Preview::renderIframeEmbedPayload($html, 0, '').'&lt;/div&gt;';
	else:
		echo $html;
	endif;
else:
	echo $grid-&gt;getModuleOutput();
endif;
?&gt;</pre>
							Kürzere Variante: <code>echo $grid-&gt;getModuleOutput();</code> rendert ebenfalls beide Formate, hat aber keine Live-Vorschau in der Slice-Übersicht. Maßgeblich ist die mitgelieferte <code>install/output.php</code>.</td>
					  </tr>
					</table>

					<h3>Platzhalter &amp; Abfragen in Inhaltsmodulen</h3>
					<p>
					<strong>REX_GRID_TEMPLATE_ID</strong> = ID der gewählten Spaltenvorlage<br>
					<strong>REX_GRID_TEMPLATE_PREVIEW</strong> = Spaltenvorschau (JSON) der gewählten Vorlage, unescaped<br>
					<strong>REX_GRID_TEMPLATE_COLUMNS</strong> = Anzahl Spalten der gewählten Vorlage<br>
					<strong>REX_GRID_COLUMN_NUMBER</strong> = Nummer der aktuellen Spalte (1...12)<br>
					<strong>REX_SLICE_ID</strong> / <strong>REX_MODULE_ID</strong> / <strong>REX_MODULE_KEY</strong> / <strong>REX_CLANG_ID</strong> / <strong>REX_ARTICLE_ID</strong> / <strong>REX_TEMPLATE_ID</strong> = die üblichen Redaxo-Platzhalter
					</p>

					<h3>MForm-Repeater in Modulen</h3>

					<div class="alert alert-warning" role="note">
					  <p><strong>Wichtig bei der Ausgabe:</strong> <code>MFormRepeaterHelper::decode(2)</code> mit Slot-Nummer liefert in Gridblock² <strong>keine Daten</strong> (Vorschau und Frontend bleiben leer). Die Werte eines Moduls liegen hier nicht in einer eigenen Slice-Zeile, sondern im Gridblock-Baum - eine Abfrage per Slot-Nummer findet sie nicht.</p>
					  <p>Nutze stattdessen die <code>REX_VALUE</code>-Schreibweise im Ausgabecode des Moduls, die Gridblock² ersetzt:</p>
<pre>use FriendsOfRedaxo\MForm\Repeater\MFormRepeaterHelper;

$raw = json_decode(html_entity_decode('REX_VALUE[id=2]', ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
$raw = is_array($raw) ? (isset($raw['items']) ? $raw['items'] : $raw) : [];
$items = MFormRepeaterHelper::prepareItemsForOutput($raw);</pre>
					  <p class="gb-help-nomargin">Die Eingabe (<code>addRepeaterElement()</code>) bleibt unverändert. Dasselbe gilt für jede Hilfsfunktion, die Werte über die Slot-Nummer aus der Datenbank liest: in Gridblock²-Modulen immer die <code>REX_VALUE[...]</code>-Variante verwenden.</p>
					</div>

					<h3>Zusätzlich verfügbar</h3>
					<ul>
					  <li><code>rex_gridblock2::isBackend()</code> - true, wenn du dich gerade im Editiermodus (Backend-Vorschau) befindest. Funktioniert in jedem Inhaltsmodul, egal ob nativ oder klassisch gerendert.</li>
					  <li><code>rex_gridblock2::isBuilderEdit()</code> - true, während dieses Element/Modul als Eingabeformular im nativen Builder gerendert wird (Karten-Ansicht). Funktioniert in jedem Inhaltsmodul (System-Element, Custom-Element oder klassisches Redaxo-Modul).</li>
					  <li><code>rex_gridblock2::isBuilderPreview()</code> - true, während dieses Element/Modul für eine Builder-Live-Vorschau gerendert wird (Shadow-DOM/iframe während des Bearbeitens, oder die optionale Slice-Übersicht-Vorschau) - NICHT true bei der echten Frontend-Ausgabe. Funktioniert ebenfalls in jedem Inhaltsmodul.</li>
					  <li><code>rex_gridblock2::getSettings()</code> - Contentsettings &amp; Template-Einstellungen als Array, aber nur innerhalb der klassischen Ausgabelogik gefüllt (siehe <a href="#contentsettings">Contentsettings</a>).</li>
					</ul>
					</section>


					<!-- Events für Tools -->
					<section class="gb-help-topic" id="edit-events">
					<h2>Events für Tools im Bearbeiten-Formular</h2>

					<p>Setzt ein Inhaltselement oder Modul ein JavaScript-Tool ein (Kartenwähler, Editor, Datepicker, ...), muss dieses Tool beim Öffnen, Übernehmen und Abbrechen manchmal mitspielen. Gridblock² meldet dafür sieben Zeitpunkte als Browser-Events. Du hörst sie in deinem eigenen Script ab - im Modul-Input, im Custom-Element oder in einer eigenen Backend-JS-Datei.</p>

					<table class="table table-striped">
					  <tr><th>Event</th><th>Zeitpunkt</th><th>Typischer Einsatz</th></tr>
					  <tr><td><code>gb2:beforeEdit</code></td><td>bevor das Formular geöffnet wird</td><td>Zustand aufräumen, bevor Gridblock² den Stand für "Abbrechen" sichert</td></tr>
					  <tr><td><code>gb2:afterOpen</code></td><td>Formular sichtbar (inline oder im Modal)</td><td>Tools, die einen sichtbaren Container brauchen (Karten, Editoren, Größenmessung)</td></tr>
					  <tr><td><code>gb2:beforeApply</code></td><td>bei "Übernehmen", bevor die Feldwerte ausgelesen werden</td><td>Tools, die ihren Wert erst in ein verstecktes Feld schreiben müssen</td></tr>
					  <tr><td><code>gb2:afterApply</code></td><td>nach "Übernehmen", Formular ist zu</td><td>Popups, Overlays und Instanzen abbauen</td></tr>
					  <tr><td><code>gb2:beforeCancel</code></td><td>bei "Abbrechen", vor dem Zurückschreiben</td><td>Instanzen und Popups abbauen, solange ihr DOM noch existiert</td></tr>
					  <tr><td><code>gb2:afterRestore</code></td><td>bei "Abbrechen": alter Stand ist zurückgeschrieben, die Tools sind noch nicht neu gestartet</td><td>Tool-spezifisches Zurechtrücken des wiederhergestellten HTML, bevor es initialisiert wird</td></tr>
					  <tr><td><code>gb2:afterCancel</code></td><td>nach dem Zurückschreiben des alten Stands</td><td>Tools neu starten (das zurückgeschriebene Formular hat keine Listener mehr)</td></tr>
					</table>

					<h3>Gut zu wissen</h3>
					<ul>
					  <li>Alle Events feuern am Formular-Container und steigen im DOM nach oben - ein Listener auf <code>document</code> genügt. In <code>event.detail.block</code> steht der Block, in <code>event.detail.body</code> das Formular, damit du gezielt nur dein Element behandeln kannst.</li>
					  <li>Bricht der Redakteur ein <strong>neu eingefügtes</strong> Element ab, wird der Block einfach entfernt: dann kommt nur <code>gb2:beforeCancel</code>, kein <code>gb2:afterCancel</code>.</li>
					  <li>Bei <code>gb2:beforeEdit</code> sitzt das Formular noch an seiner ursprünglichen Stelle im Block, erst danach wird es ggf. ins Modal verschoben.</li>
					  <li>Das Script, das dein Tool initialisiert, läuft nur einmal beim Laden des Formulars. Nach "Abbrechen" wird das Formular als reiner HTML-Text zurückgeschrieben - Inline-Scripte laufen dabei nicht erneut. Hänge dich deshalb an <code>gb2:afterCancel</code> oder an <code>rex:ready</code>.</li>
					</ul>

					<h3>Beispiel</h3>
					<pre>document.addEventListener('gb2:afterCancel', function (e) {
    e.detail.body.querySelectorAll('[data-mein-tool]').forEach(function (el) {
        MeinTool.init(el);
    });
});

document.addEventListener('gb2:beforeCancel', function (e) {
    e.detail.body.querySelectorAll('[data-mein-tool]').forEach(function (el) {
        if (el._meinTool) { el._meinTool.destroy(); }
    });
});</pre>
					</section>


					<!-- Import & Export -->
					<section class="gb-help-topic" id="import-export">
					<h2>Import &amp; Export</h2>

					<p>Spaltenvorlagen (inkl. ihrer Contentsettings) lassen sich als ZIP-Archiv exportieren und auf einer anderen Installation wieder importieren - praktisch, um ein einmal gebautes Vorlagen-Set mehrfach zu nutzen. Die Funktion ist standardmäßig <strong>deaktiviert</strong> und muss zuerst unter <a href="#einstellungen">Einstellungen</a> eingeschaltet werden.</p>

					<h3>Aufbau eines Archivs</h3>
					<ul>
					  <li>Ein Ordner pro Vorlage (z.B. <code>template_1/</code>), darin:
						<ul>
						  <li><code>template.json</code> - Titel, Kurzbeschreibung, Priorität, Status</li>
						  <li><code>definition.json</code> - Spaltenanzahl, Spaltenvorschau und CSS-Klasse der Vorlage</li>
						  <li><em>optional:</em> <code>contentsettings.json</code> - individuelle Einstellungen nur für diese Vorlage</li>
						</ul>
					  </li>
					  <li>Optional im Archiv-Root: projektweite <code>contentsettings.json</code> bzw. <code>contentsettings.global.json</code></li>
					</ul>

					<p>Ein Beispielarchiv zum Reinschauen gibt's direkt im Import-Dialog per Klick zum Download.</p>

					<h3>Gut zu wissen</h3>
					<ul>
					  <li>Vorhandene Vorlagen werden beim Import <strong>nie überschrieben</strong> - importierte Vorlagen kommen immer als neue Datensätze dazu.</li>
					  <li>Vorhandene Contentsettings-Dateien werden ebenfalls nicht überschrieben, sondern mit Zeitstempel daneben abgelegt (<code>contentsettings_imported_...json</code>) - schau danach kurz rein und übernimm von Hand, was du brauchst.</li>
					  <li>Der Export enthält nur, was auf deiner Installation tatsächlich existiert - keine Contentsettings-Dateien vorhanden, dann sind auch keine im Archiv.</li>
					  <li><strong>Grenzen beim Import:</strong> höchstens 20&nbsp;MB (entpackt) und 200 Dateien pro Archiv. Entpackt werden nur <code>template.json</code>, <code>definition.json</code>, <code>contentsettings.json</code> und <code>contentsettings.global.json</code> - alles andere im Archiv (z.&nbsp;B. eine <code>template.php</code> aus Gridblock-1.x-Exporten) wird ignoriert. Archive mit Pfaden wie <code>../</code> oder absoluten Pfaden werden abgelehnt.</li>
					</ul>
					</section>


					<!-- Synchronizer -->
					<section class="gb-help-topic" id="synchronizer">
					<h2>Synchronizer</h2>

					<p>Der Synchronizer spiegelt deine Spaltenvorlagen und Contentsettings zusätzlich als Dateien ins Dateisystem (<code>redaxo/data/addons/gridblock2/synchronizer/templates/</code>) - praktisch für Versionsverwaltung (Git) oder den Abgleich zwischen Entwicklungs- und Produktivsystem, ähnlich wie man es vom Addon "Developer" kennt. Auch er ist standardmäßig <strong>deaktiviert</strong> und wird über die entsprechende Checkbox unter <a href="#einstellungen">Einstellungen</a> aktiv.</p>

					<p>Einmal aktiviert, läuft der Abgleich automatisch bei jedem Backend-Aufruf eines eingeloggten Administrators - manuell anstoßen musst du nichts.</p>

					<h3>Läuft parallel noch eine Gridblock-1.x-Installation?</h3>

					<p>Dann kann es sein, dass der Synchronizer auffällig oft in seine eigene Dateikopie schreibt - unschädlich, aber erklärungsbedürftig. Details dazu unter <a href="#migration">Migration von GB1 nach GB2</a>.</p>
					</section>


					<!-- Einstellungen -->
					<section class="gb-help-topic" id="einstellungen">
					<h2>Einstellungen</h2>

					<p>Die Kurzbeschreibung an jedem Feld im Einstellungsformular reicht meist schon aus - hier die ausführliche Referenz zu jedem Panel und jeder Option. Die Seite "Einstellungen" sehen nur Benutzer mit dem Recht "gridblock2[config]".</p>

					<h3>Inhaltsmodule</h3>
					<ul>
					  <li><strong>Modus für Inhaltsmodule:</strong> "Zulässige Inhaltsmodule" - nur die ausgewählten Module dürfen verwendet werden. "Zu ignorierende Inhaltsmodule" - alle Module sind erlaubt außer den ausgewählten.</li>
					  <li><strong>Auswahl Inhaltsmodule:</strong> die Modulliste, auf die sich der obige Modus bezieht.</li>
					</ul>

					<h3>Weitere Funktionen</h3>
					<ul>
					  <li><strong>Import/Export für Spaltenvorlagen &amp; Contentsettings:</strong> standardmäßig aus. Blendet die <a href="#import-export">Import/Export-Buttons</a> in der Spaltenvorlagen-Übersicht ein.</li>
					  <li><strong>Synchronizer aktivieren:</strong> standardmäßig aus. Startet den <a href="#synchronizer">Synchronizer</a>-Dateiabgleich.</li>
					  <li><strong>Multi-Site-Unterstützung für Live-Vorschau aktivieren:</strong> standardmäßig aus. Für Installationen mit mehreren YRewrite-Domains: Unter "Builder-Vorschau" erscheint dann je Domain ein Reiter mit eigenen Vorschau-Werten (Frontend-CSS, Frontend-JavaScript, CSS-Override für Inhaltsmodule, Sichtbarkeits-Korrekturen), siehe den Abschnitt "Multi-Site: Vorschau je Domain" weiter unten. Benötigt YRewrite mit mindestens einer eigenen Domain.</li>
					  <li><strong>Multi-Site-Unterstützung für Inhaltselemente aktivieren:</strong> standardmäßig aus. Legt je YRewrite-Domain fest, welche Module und Elemente im Builder verfügbar sind, siehe den Abschnitt "Elementauswahl je Domain" weiter unten.</li>
					  <li><strong>Einleitungstexte:</strong> blendet Einleitungs- &amp; Informationstexte im Modul aus, für Redakteure, die die Bedienung schon kennen.</li>
					</ul>

					<h3>Builder - Allgemein</h3>
					<ul>
					  <li><strong>Verschachtelungstiefe:</strong> wie tief sich Sektion/Spalten-Elemente ineinander schachteln lassen (Standard: 3).</li>
					  <li><strong>Vorschau-Modus (Inhaltsmodule):</strong> Shadow-DOM (Standard bei Neuinstallation, schneller, nur CSS) oder iframe (mit echtem JavaScript) - Details unter <a href="#vorschau">Vorschau-Technik</a>.</li>
					  <li><strong>Vorschau-Modus: Module ohne iFrame (Ausnahmen):</strong> nur bei iframe-Vorschau-Modus sichtbar - die hier gewählten Inhaltsmodule werden trotzdem per Shadow-DOM dargestellt (schneller, ohne JavaScript), alle übrigen per iframe.</li>
						  <li><strong>Custom-Elemente ebenfalls per iframe anzeigen:</strong> nur bei iframe-Vorschau-Modus sichtbar - schaltet Custom-Elemente (standardmäßig Shadow-DOM) ebenfalls auf echtes iframe mit echtem JavaScript um.</li>
					  <li><strong>Media-Queries in der Vorschau:</strong> übersetzt breitenbasierte <code>@media</code>-Regeln in der Vorschau automatisch in Container-Queries, damit der Viewport-Umschalter (Smartphone/Tablet) tatsächlich greift.</li>
					  <li><strong>Editmode bei geringer Breite:</strong> öffnet die Inhaltsbearbeitung im Modal, sobald der verfügbare Platz die Mindestbreite unterschreitet.</li>
					  <li><strong>Mindestbreite (Editmode):</strong> die Breite, ab der inline statt im Modal editiert wird (Standard: 560).</li>
					  <li><strong>Darstellung der Fenster:</strong> Bearbeiten- und Einstellungsfenster wahlweise als zentriertes Modal oder als Sidebar, die von rechts einfährt (Breite 95 % des Fensters, höchstens 800 px; der Hintergrund wird abgedunkelt).</li>
					  <li><strong>Schmale Sidebar:</strong> zeigt die Sidebar standardmäßig mit 500 px statt 800 px Breite (gilt auch für die angedockte Sidebar der Live-Vorschau). Der Breiten-Schalter in der Kopfzeile der Sidebar wechselt weiterhin auf die breite Ansicht.</li>
					  <li><strong>Viewport-Buttons:</strong> zeigt in der Kopfzeile des Builders die Umschalter für Desktop, Tablet und Smartphone (Standard: an). Ausgeschaltet bleibt die Vorschau in der normalen Breite.</li>
					  <li><strong>Links in der Vorschau sperren:</strong> (Standard: an) Klicks auf Links (auch Mittelklick und Strg-/Cmd-Klick) und das Absenden von Formularen in der Live-Vorschau von Builder und Slice-Übersicht führen nichts aus - die Seite wird dadurch nie versehentlich verlassen. Skripte der Module (Lightbox, Akkordeon, Slider) laufen weiter, Link-Titel bleiben als Hinweis sichtbar. Die Links in den Bearbeitungsformularen sind nicht betroffen.</li>
					</ul>

					<h3>Builder - Toolbar</h3>
					<ul>
					  <li><strong>Kompakte Toolbox:</strong> platzsparendere Werkzeugleiste am Element.</li>
					  <li><strong>Drag-Handle:</strong> zeigt einen Ziehgriff am Rand jedes Elements zum Verschieben.</li>
					  <li><strong>Online/Offline:</strong> blendet den Umschalter zum Deaktivieren einzelner Elemente ein.</li>
					  <li><strong>Kopieren/Einfügen:</strong> blendet die entsprechenden Buttons ein.</li>
					  <li><strong>Spalten-Schnellumschalter:</strong> schnelles Wechseln der Spaltenaufteilung direkt am Element, ohne den Umweg über das Einstellungen-Formular.</li>
					  <li><strong>Elementlabel ausblenden:</strong> versteckt die Namensbeschriftung am Element (schlankere Optik).</li>
					  <li><strong>"Element hinzufügen" auch verschachtelt:</strong> zeigt den Hinzufügen-Button zusätzlich direkt in Sektion/Spalten, nicht nur am Wurzelknoten.</li>
					</ul>

					<h3>Builder - Vollbild-Modus</h3>
					<ul>
					  <li><strong>Speichern-Button im Vollbild:</strong> zeigt in der Vollbild-Leiste den Button "Block übernehmen" (Original-Button von REDAXO, Standard: an). Er speichert den Slice und öffnet das Vollbild an der bisherigen Scrollposition erneut. Daneben sitzt ein Button mit Speichern-Symbol ("Block speichern und schließen"): Er speichert den Slice und verlässt den Editiermodus, ohne dass das Vollbild vorher beendet werden muss.</li>
					  <li><strong>Sidebar Live-Edit-Vorschau:</strong> (Standard bei Neuinstallation: an) Nur mit Darstellung "Sidebar": Die Vorschau wird kurz nach jeder Änderung in der Sidebar neu aufgebaut. Im Vollbild und ab 1100 px Fensterbreite sitzt die Sidebar zusätzlich rechts neben der Fläche, statt sie abzudunkeln. Abbrechen stellt Werte und Vorschau wieder her. Bei schmaleren Fenstern gilt das normale Verhalten.</li>
					</ul>

					<h3>Builder - Slice-Übersicht</h3>
					<ul>
					  <li><strong>Vollbild-Button in der Slice-Übersicht:</strong> zeigt an jedem Gridblock der Content-Liste rechts oben einen Button, der den Block direkt im Vollbild-Editmodus öffnet (unabhängig von der Live-Vorschau; Standard: an).</li>
					  <li><strong>Live-Vorschau in der Slice-Übersicht:</strong> zeigt den Baustein in der Content-Liste direkt mit echtem Website-Styling statt unstyled.</li>
					  <li><strong>Max. Höhe der Vorschau in der Slice-Übersicht:</strong> begrenzt die Höhe der Live-Vorschau auf 300, 500 oder 800 px; höhere Vorschauen sind dann innerhalb des Rahmens scrollbar (Standard: Auto = volle Höhe). Wirkt nur mit aktiver Live-Vorschau in der Slice-Übersicht.</li>
					</ul>

					<h3 id="einstellungen-multisite">Multi-Site: Vorschau je Domain</h3>

					<p>Laufen mehrere Websites in einer REDAXO-Installation (YRewrite-Domains) mit unterschiedlichem Design, braucht die Builder-Vorschau je Domain andere CSS-/JS-Dateien. Mit aktivierter Multi-Site-Unterstützung erscheinen unter "Builder-Vorschau" Reiter: "Standard" plus je ein Reiter pro Domain. Der Builder erkennt die Domain automatisch anhand des Artikels, den du gerade bearbeitest.</p>
					<ul>
					  <li><strong>Standard + eigene Werte</strong> (Voreinstellung): Zuerst gelten die Werte des Reiters "Standard", die Werte der Domain kommen dazu. Praktisch, wenn sich die Websites nur in einer zusätzlichen CSS-Datei (z.&nbsp;B. andere Farben) unterscheiden. Bei den Modul-Überschreibungen hat der Eintrag der Domain Vorrang.</li>
					  <li><strong>Komplett eigene Werte:</strong> Es gelten nur die Werte des Domain-Reiters, der Reiter "Standard" wird für diese Domain ignoriert.</li>
					  <li><strong>Übernehmen aus Hauptdomain:</strong> kopiert die Werte des Reiters "Standard" in den Domain-Reiter, um sie dort weiter anzupassen.</li>
					  <li>Die Zuordnung hängt an der Domain-ID aus YRewrite, nicht am Namen: Eine Umbenennung der Domain ändert nichts. Wird eine Domain in YRewrite gelöscht, zeigt der Reiter eine Warnung und lässt sich beim Speichern entfernen.</li>
					  <li>Die Zuordnung erfolgt nur nach Domain, nicht nach Sprache. Ohne Eintrag für eine Domain gelten die Standard-Werte.</li>
					</ul>

					<h3 id="einstellungen-elementauswahl">Elementauswahl je Domain</h3>

					<p>Mit der Einstellung "Multi-Site-Unterstützung für Inhaltselemente aktivieren" legst du fest, welche REDAXO-Module, Custom-Elemente und welche Sektion im Builder auf welcher Domain verfügbar sind. Die Domain ergibt sich aus dem Artikel, den du bearbeitest (tiefster YRewrite-Mountpoint). Spalten sind immer verfügbar.</p>
					<ul>
					  <li><strong>Ohne eigene Einstellung</strong> gilt für eine Domain die globale Auswahl: bei REDAXO-Modulen die "Auswahl Inhaltsmodule", dazu alle aktivierten Custom-Elemente und die Sektion. Ein neues Element - auch aus einem Addon - steht sofort überall bereit.</li>
					  <li><strong>Je Domain</strong> (Reiter "Individuell", Domain im Auswahlfeld): "Wie global (abzüglich Nicht verfügbar)" übernimmt die globale Auswahl und blendet nur die unter "Nicht verfügbar" gewählten Einträge aus. "Komplett eigene Auswahl" ersetzt sie durch eine eigene Liste oder "Alle Module &amp; Elemente zulassen".</li>
					  <li>Rechts neben dem Auswahlfeld öffnet der Button eine Übersicht, was die Domain nach dem zuletzt gespeicherten Stand sieht.</li>
					  <li><strong>Zusammenspiel:</strong> Ein Modul oder Element erscheint nur, wenn alles zutrifft: die Domain-Auswahl, das Recht der Rolle (REDAXO-Modulrechte bzw. <code>permission: true</code>) und bei Modulen zusätzlich die globale Modulauswahl und die Modul-Zuordnung des Templates.</li>
					  <li><strong>Admins</strong> sind beim Hinzufügen an die Domain-Auswahl gebunden. Bereits platzierte Elemente bleiben für Admins unverändert bearbeitbar. Für andere Benutzer sind Elemente, die auf der Domain nicht (mehr) verfügbar sind - zum Beispiel nach dem Verschieben eines Artikels -, sichtbar und verschiebbar, aber nicht bearbeitbar, kopierbar, ein-/ausblendbar oder löschbar.</li>
						  <li><strong>Prüfung beim Speichern:</strong> Der Server prüft neu hinzugefügte Elemente auch beim Speichern des Blocks (Rolle, Template-Zuordnung bei Modulen und Domain-Auswahl) und lehnt nicht erlaubte ab - ein von Hand veränderter Request umgeht die Sperren der Oberfläche also nicht. Bereits gespeicherte Elemente bleiben davon unberührt und lassen sich unverändert weiterspeichern.</li>
					</ul>

					<h3>Builder-Vorschau</h3>
					<ul>
					  <li><strong>Frontend-CSS/-JS für Backend-Vorschau:</strong> je ein Pfad pro Zeile (ab Projekt-Root), damit die iframe-Vorschau wie die echte Website aussieht. JS nur in der iframe-Vorschau nutzbar, nicht bei Sektion/Spalten.</li>
					  <li><strong>CSS-Override für Inhaltsmodule:</strong> zusätzliches Vorschau-CSS für einzelne Module (Sonderfälle), Format zeilenweise <code>modulID_oder_key = /pfad/zu/datei.css</code>.</li>
					  <li><strong>Sichtbarkeits-Korrekturen für die Vorschau:</strong> CSS-Gegenregeln für Bibliotheken, die Inhalte per CSS verstecken (z.B. Einblendeffekte wie AOS) - Details unter <a href="#vorschau">Vorschau-Technik</a>.</li>
					  <li><strong>Wrapper-Klasse der Vorschau:</strong> eine oder mehrere Klassen (z.&nbsp;B. <code>content</code>), die in der Vorschau an die Hülle <code>.gb2-preview-content</code> angehängt werden (Shadow-DOM-Hülle bzw. <code>&lt;body&gt;</code> der iframe-Vorschau). Nötig, wenn dein Theme seine Regeln auf einen Content-Container begrenzt (z.&nbsp;B. <code>.content h2 { ... }</code>) und die Vorschau sonst ungestaltet aussähe. Mit Multi-Site gilt je Domain "Standard + eigene Werte" (Klassen beider Reiter) oder "Komplett eigene Werte".</li>
					  <li><strong>Element-CSS im Frontend:</strong> bindet die gesammelte <code>style.css</code> aller Custom-Elemente automatisch im Frontend ein (Standard: aus), Details unter <a href="#builder">Der Builder</a>, "CSS der Custom-Elemente".</li>
					  <li><strong>Contentsettings-Feld für Spalten-Abstand</strong> und <strong>Werte für nativen Spalten-Abstand:</strong> siehe <a href="#spaltenabstand">Spalten-Abstand</a>.</li>
					</ul>

					<h3 id="einstellungen-datei">Einstellungen vorgeben (install/settings.json)</h3>

					<p>Für vorgefertigte Installationspakete lassen sich die Einstellungen schon bei der <strong>Erstinstallation</strong> des Addons vorgeben: Lege dafür die Datei <code>install/settings.json</code> in den Addon-Ordner (neben <code>install.sql</code>). Als Vorlage dient die mitgelieferte <code>install/settings.json.example</code>.</p>
					<ul>
					  <li><strong>Struktur:</strong> dieselben Schlüssel wie in den Einstellungen (z.&nbsp;B. <code>gb_builder_maxdepth</code>). Checkboxen als <code>true</code>/<code>false</code>, Listen (Module, Stylesheets, Zeilen für den Spaltenabstand) als Array, die Palette als JSON-Objekt oder als Text.</li>
					  <li><strong>Nur bei der Erstinstallation:</strong> Die Datei wird einmal gelesen. Bei einer Neuinstallation über vorhandene Einstellungen und bei Updates bleiben die gespeicherten Werte unverändert. Später ändert man die Werte wie gewohnt unter "Einstellungen".</li>
					  <li><strong>Prüfung:</strong> Unbekannte Schlüssel, ungültige Werte und eine fehlerhafte Palette werden ignoriert. Die Hinweise stehen im REDAXO-Log. Schlüssel mit einem Unterstrich am Anfang (z.&nbsp;B. <code>_hinweis</code>) sind für Kommentare gedacht und werden übersprungen.</li>
					  <li><strong>Einrichtungsassistent:</strong> Wurden Werte aus der Datei übernommen, gilt der Assistent auf der Übersichtsseite als erledigt.</li>
					  <li>Bei einem Addon-Update über den Installer wird der Addon-Ordner ersetzt, die Datei ist danach nicht mehr vorhanden. Das ist unproblematisch, weil sie nur bei der Erstinstallation gebraucht wird.</li>
					</ul>
					</section>

					<section class="gb-help-topic" id="api">
					<h2>API für Templates und Module</h2>

					<p>Diese Methoden und Hooks sind für die Nutzung in Templates, Modulen und anderen Addons gedacht. Alles andere (die Klassen <code>Gridblock2Builder_*</code>, <code>Gridblock2Palette</code> und die internen <code>rex_api</code>-Aufrufe des Builders) ist intern und kann sich ändern.</p>

					<h3>Projektfarben</h3>
					<p>Liefern die Palette aus den Einstellungen (siehe <a href="#projektfarben">Projektfarben</a>), für den aktuellen Artikel und die aktuelle Domain, also auch mit Domain-Palette und dem Extension-Point <code>GRIDBLOCK2_PALETTE</code>.</p>
					<ul>
					  <li><code>rex_gridblock2::getPaletteColors(): array</code> - alle Farben, Schlüssel ist der Key der Palette.</li>
					  <li><code>rex_gridblock2::getPaletteColor(string $key): ?array</code> - eine Farbe oder <code>null</code>, wenn es den Key nicht gibt.</li>
					</ul>
					<p>Jeder Eintrag hat dieselben Felder, nicht angegebene sind ein leerer Text:</p>
					<pre>[
  'key'              =&gt; 'primary',
  'label'            =&gt; 'Primärfarbe',
  'class'            =&gt; 'bgCol-primary',       // ohne Punkt
  'color'            =&gt; 'var(--col-primary)',  // Variable oder Farbwert
  'text_color'       =&gt; '#ffffff',
  'hover_color'      =&gt; '',
  'hover_text_color' =&gt; '',
]</pre>
					<p>Beispiel im Template: Die Projektfarben als CSS-Variablen ausgeben oder die Textfarbe zu einer Hintergrundfarbe verwenden:</p>
					<pre>&lt;?php
$primary = rex_gridblock2::getPaletteColor('primary');
if ($primary !== null) {
    echo '&lt;meta name="theme-color" content="'.rex_escape($primary['color']).'"&gt;';
}</pre>
					<p>Enthält <code>color</code> eine CSS-Variable, lässt sie sich in PHP nicht in einen Hex-Wert umrechnen. Dafür eignen sich nur Einträge mit festem Farbwert.</p>

					<h3>CSS für die manuelle Einbindung</h3>
					<p>Im Frontend bindet Gridblock² das generierte CSS automatisch ein, wenn die Einstellungen "Natives Grid-CSS im Frontend" bzw. "Element-CSS im Frontend" aktiv sind. Wer es selbst einbinden will (eigene Stelle im <code>&lt;head&gt;</code>, <code>preload</code>, eigener Asset-Ablauf), schaltet die jeweilige Einstellung aus und holt sich die URL:</p>
					<ul>
					  <li><code>rex_gridblock2::getNativeGridCssUrl(): string</code> - das native Grid-CSS (Spalten-Abstände).</li>
					  <li><code>rex_gridblock2::getElementsCssUrl(): string</code> - das gesammelte CSS aller Custom-Elemente (<code>style.css</code>).</li>
					</ul>
					<p>Beide liefern einen Pfad ab Webroot mit Versionsparameter (<code>/assets/addons/gridblock2/css/...?v=...</code>) und erzeugen die Datei bei Bedarf neu. Sie liefern die URL unabhängig von den beiden Einstellungen. Ist die Einstellung aktiv und du gibst den Link zusätzlich selbst aus, steht er zweimal im Quelltext.</p>
					<pre>&lt;?php
echo '&lt;link rel="stylesheet" href="'.rex_escape(rex_gridblock2::getNativeGridCssUrl()).'"&gt;';
echo '&lt;link rel="stylesheet" href="'.rex_escape(rex_gridblock2::getElementsCssUrl()).'"&gt;';</pre>

					<h3>Weitere öffentliche Stellen</h3>
					<ul>
					  <li><strong>Kontext:</strong> <code>rex_gridblock2::isBuilderEdit()</code>, <code>isBuilderPreview()</code> und <code>isBackend()</code> - Module können damit in der Builder-Vorschau anders rendern als im Frontend (siehe <a href="#modul">Modul &amp; Platzhalter</a>).</li>
					  <li><strong>Einstellungen:</strong> <code>rex_gridblock2::getConfig($key = null)</code> liest eine Einstellung oder alle.</li>
					  <li><strong>Ausgabe-Helfer für Contentsettings:</strong> <code>rex_gridblock2_helper::getSectionClasses/-Styles/-Data</code>, <code>getColumnClasses/-Styles/-Data</code> und <code>getTemplateClasses/-Styles/-Data</code> (siehe <a href="#contentsettings">Contentsettings</a>).</li>
					  <li><strong>Extension-Points:</strong> <code>GRIDBLOCK2_PALETTE</code> (siehe <a href="#projektfarben">Projektfarben</a>) und <code>GRIDBLOCK2_PREVIEW_CSS</code> (siehe <a href="#vorschau">Vorschau-Technik</a>).</li>
					  <li><strong>JavaScript-Events im Builder:</strong> <code>gb2:beforeEdit</code>, <code>gb2:afterOpen</code>, <code>gb2:beforeApply</code>, <code>gb2:afterApply</code>, <code>gb2:beforeCancel</code>, <code>gb2:afterCancel</code>, <code>gb2:afterRestore</code> (siehe <a href="#edit-events">Events für Tools</a>).</li>
					</ul>
					</section>



					<p>&nbsp;</p>

					<!-- Fragen / Probleme -->
					<h3>Fragen, Wünsche, Probleme?</h3>
					Du hast einen Fehler gefunden oder ein nettes Feature parat?<br>
					Lege ein Issue unter <a href="<?php echo $this->getProperty('supportpage'); ?>" target="_blank"><?php echo $this->getProperty('supportpage'); ?></a> an.


					<!-- Credits -->
					<h3>Credits</h3>
					Contentsettings/Synchronizer-Idee: <a href="https://github.com/novinet-git" target="_blank">Daniel Steffen</a><br>
					Ursprüngliche Gridblock-Idee: <a href="https://github.com/bloep" target="_blank">Marcel Kuhmann</a><br>

				</div>
				
			</div>

		</div>
	</div>
</div>

</section>

<script>
(function () {
	"use strict";

	var searchInput = document.getElementById('gbHelpSearch');
	var searchCount = document.getElementById('gbHelpSearchCount');
	var noResults = document.getElementById('gbHelpNoResults');
	var topics = Array.prototype.slice.call(document.querySelectorAll('.gb-help-topic'));
	var navItems = Array.prototype.slice.call(document.querySelectorAll('#gbHelpNav li[data-topic]'));
	var navHeadings = Array.prototype.slice.call(document.querySelectorAll('#gbHelpNav li.gb-help-nav-heading'));
	if (!searchInput || !topics.length) { return; }

	function updateNavHeadings() {
		navHeadings.forEach(function (heading) {
			var group = heading.getAttribute('data-group');
			var groupItems = navItems.filter(function (li) { return li.getAttribute('data-group') === group; });
			var groupHasVisibleItem = groupItems.some(function (li) { return !li.classList.contains('gb-help-nav-hidden'); });
			heading.classList.toggle('gb-help-nav-hidden', !groupHasVisibleItem);
		});
	}

	//Original-Markup je Thema merken, um Hervorhebungen wieder rückstandsfrei entfernen zu können
	var originalHtml = topics.map(function (topic) { return topic.innerHTML; });

	function escapeRegExp(text) {
		return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	function highlight(root, term) {
		var pattern = new RegExp('(' + escapeRegExp(term) + ')', 'gi');
		var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
		var textNodes = [];
		var node;
		while ((node = walker.nextNode())) {
			if (node.nodeValue && pattern.test(node.nodeValue)) {
				textNodes.push(node);
			}
			pattern.lastIndex = 0;
		}

		var hits = 0;
		textNodes.forEach(function (textNode) {
			var parts = textNode.nodeValue.split(pattern);
			if (parts.length <= 1) { return; }

			var fragment = document.createDocumentFragment();
			parts.forEach(function (part, index) {
				if (index % 2 === 1) {
					var mark = document.createElement('mark');
					mark.textContent = part;
					fragment.appendChild(mark);
					hits++;
				} else if (part.length) {
					fragment.appendChild(document.createTextNode(part));
				}
			});
			textNode.parentNode.replaceChild(fragment, textNode);
		});

		return hits;
	}

	function performSearch() {
		var term = searchInput.value.trim();

		if (!term) {
			topics.forEach(function (topic, index) {
				topic.innerHTML = originalHtml[index];
				topic.classList.remove('gb-help-topic-hidden');
			});
			navItems.forEach(function (li) { li.classList.remove('gb-help-nav-hidden'); });
			navHeadings.forEach(function (heading) { heading.classList.remove('gb-help-nav-hidden'); });
			searchCount.textContent = '';
			noResults.classList.remove('gb-help-visible');
			return;
		}

		var totalHits = 0;
		var matchingTopics = 0;

		topics.forEach(function (topic, index) {
			topic.innerHTML = originalHtml[index];
			var hits = highlight(topic, term);
			var matches = hits > 0;

			topic.classList.toggle('gb-help-topic-hidden', !matches);

			var navItem = navItems.filter(function (li) { return li.getAttribute('data-topic') === topic.id; })[0];
			if (navItem) { navItem.classList.toggle('gb-help-nav-hidden', !matches); }

			if (matches) {
				totalHits += hits;
				matchingTopics++;
			}
		});

		updateNavHeadings();

		searchCount.textContent = totalHits ? (totalHits + ' Treffer in ' + matchingTopics + ' Themen') : '';
		noResults.classList.toggle('gb-help-visible', totalHits === 0);
	}

	var searchTimeout;
	searchInput.addEventListener('input', function () {
		clearTimeout(searchTimeout);
		searchTimeout = setTimeout(performSearch, 200);
	});

	document.addEventListener('keydown', function (event) {
		if (event.key !== '/' || event.target === searchInput) { return; }
		var target = event.target;
		var isTyping = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);
		if (isTyping) { return; }
		event.preventDefault();
		searchInput.focus();
	});
})();
</script>
