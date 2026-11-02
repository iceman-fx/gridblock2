Gridblock²
==========

Changelog
---------
<b>Version 1.0.0-beta1</b>
- Komplett neue Backend-Oberfläche (Builder): eine flache Karten-Liste ersetzt die bisherigen Tabs, mit Live-Vorschau (iframe oder Shadow-DOM), Drag & Drop, Toolbar-Menü, Kopieren/Einfügen, Online/Offline, Vollbild, Sidebar- oder Modal-Bearbeitung und Touch-Bedienung für Tablets
- Neue Kernelemente "Sektion" und "Spalten" für frei kombinierbare, beliebig tief verschachtelbare Layouts, mit eigener Hintergrund-/Abstands-/Rand-Konfiguration je Element und je Spalte
- Spaltenvorlagen legen nur noch die Aufteilung (Breiten, CSS-Klasse) fest; mitgeliefertes natives Grid-CSS und konfigurierbare Spaltenabstände
- Custom-Elemente: Hero-Banner, Call-to-Action, Zitat, Formular (benötigt YForm) und Vector Map (benötigt das Addon vector_maps); Karten-Gruppe und Abstand als Beispiele im Demobereich. Eigene Elemente über Ordner mit <code>element.yml</code> (auch aus anderen Addons)
- Projektfarben-Palette für Farbfelder (Einstellung oder Extension-Point <code>GRIDBLOCK2_PALETTE</code>), Vorschau-CSS aus anderen Addons (<code>GRIDBLOCK2_PREVIEW_CSS</code>)
- Contentsettings mit eigenen Ebenen für Sektion, Spalten-Zeile und Spalte sowie Blockeinstellungen für jeden Block
- Multi-Site (YRewrite): eigene Vorschau-Werte und eigene Element-Auswahl je Domain
- Rechte: Gridblock-Elemente je Rolle freigeben (<code>permission: true</code> in der <code>element.yml</code>); Rolle, Template-Zuordnung und Domain-Auswahl werden auch beim Speichern serverseitig geprüft
- Plugin "contentsettings" & "synchronizer" sind keine separaten Plugins mehr, sondern nativer Bestandteil des Addons; Import/Export von Spaltenvorlagen weiterhin möglich
- Events für Tools im Bearbeiten-Formular (<code>gb2:beforeEdit</code> u. a.)
- Diverse Sicherheits- und Stabilitätsfixes (u. a. CSRF-Schutz der Verwaltungsaktionen, gehärteter ZIP-Import, XSS-Härtung der Contentsettings-Ausgabe, Einrichtungsassistent und PHP-Code von Spaltenvorlagen nur für Administratoren)
- Neue Onlinehilfe mit Suche und modernisierte Einstellungsseiten
- Gridblock 1.x Strukturen weiterhin direkt nutzbar - Umstellung auf GB2-Strukturen passiert transparent beim nächsten Speichern
- Migrationsassistent für den Umstieg von Gridblock 1.x (Spaltenvorlagen, Contentsettings, Modulcode)
- Benötigt REDAXO 5.20 oder neuer
