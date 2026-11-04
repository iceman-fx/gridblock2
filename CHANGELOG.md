Gridblock²
==========

Changelog
---------
<b>Version 1.0.0-beta2</b>
- Neues Custom-Element "Karten": fünf Darstellungen (Klassisch, Offen, Bildkachel, Bildkachel mit Button, Kompakt), Kartenbreite statt fester Spaltenzahl, Ausrichtung, Bildformat und -größe, Projektfarben, Schatten und Rundung wählbar
- Hero: Pluspunkte auch zwischen Text und Buttons sowie untereinander darstellbar; Bilder der Infokarten füllen ihre Hülle sauber, Hover-Textfarbe der umrandeten Buttons (auch im Call-to-Action) passt zur gewählten Palettenfarbe
- Formular: Feldtyp "Datum" mit wählbarer Datumsart (Datum, Kalenderwoche, Monat)
- Repeater: neue Option <code>minRows</code> (Mindestzahl der Zeilen)
- Öffentliche API für Templates und Module: <code>rex_gridblock2::getPaletteColors()</code>, <code>getPaletteColor($key)</code>, <code>getNativeGridCssUrl()</code>, <code>getElementsCssUrl()</code>; neues Hilfe-Thema "API"
- Projektfarben in Contentsettings: neue Option <code>"palette": "colors"</code> liefert immer die Farbe statt der Klasse (für Rahmen- und Trennerfarbe)
- Werte für den nativen Spalten-Abstand jetzt als JSON-Objekt (<code>{"md": {"label": "Mittel", "value": "24px"}}</code>) mit Prüfung beim Speichern; das bisherige Zeilenformat wird weiterhin gelesen
- Einstellungen vorgeben: Für vorgefertigte Installationspakete liest das Addon bei der Erstinstallation optional <code>install/settings.json</code> (Vorlage: <code>install/settings.json.example</code>); der Einrichtungsassistent gilt dann als erledigt
- Builder: neue Einstellung "Hinzufügen-Button zwischen den Elementen" (bei aktiven Layouthilfen), mit Trennlinie und Button zwischen den Elementen, auch auf Touch-Geräten bedienbar
- Builder: Vollbild bleibt nach einem harten Neuladen der Seite erhalten (Marker als URL-Parameter <code>gbfs</code>, der Anker der Seite bleibt erhalten)
- Builder: Vollbild sperrt den Seiten-Scroll nach dem Speichern nicht mehr dauerhaft, doppelte Palettenfelder beim Duplizieren von Repeater-Zeilen behoben, Kebab-Menü öffnet bei wenig Platz nach oben, Selbsttest der Palettenfelder im REDAXO-Debug-Modus
- Spaltenvorlagen: Die Löschsperre und die Anzeige "in Verwendung" erkennen jetzt auch Vorlagen, die im neuen Builder verwendet werden (bisher nur das alte Gridblock-1-Format); die Reihenfolge wird beim Speichern ohne Lücken neu nummeriert, beim Löschen werden auch die Contentsettings-Ordner der Vorlage entfernt

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
