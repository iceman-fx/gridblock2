# Custom-Element "Hero"

Großer Einstieg einer Seite mit Überschrift, Text, Buttons und Bild oder Video. Vier Darstellungen, einstellbare
Bildformate, optionale Abdunklung, Infokarten und eine maximale Breite.

Diese Datei ist nur eine Hilfe für Admins und wird vom Addon nicht ausgewertet.


## Dateien dieses Elements

| Datei | Aufgabe |
|---|---|
| `element.yml` | Name, Symbol, Beschreibung |
| `input.php` | Editor im Builder (Darstellung, Maximale Breite, Tabs "Text & Buttons" und "Bild & Video") |
| `output.php` | Ausgabe |
| `helpers.php` | Hilfsklasse `GbHeroKit` (Listen, Button- und Überschrift-Ausgabe). Liegt im Elementordner, das Element bleibt damit autark |
| `style.css` | Gestaltung der Backend-Vorschau (siehe Abschnitt Styling) |
| `README.md` | diese Hilfe |

Das Element braucht kein anderes Element. Optional genutzt werden nur: das Addon `vidstack` (Player für Videos), das Addon
`fa_iconpicker` (Icon-Auswahl bei den Buttons) und `rex_media` (Alt-Text des Bildes).


## Darstellungen

| Darstellung | Aufbau | Video |
|---|---|---|
| Text und Bild nebeneinander | Text und Medium in zwei Spalten, Bild links oder rechts. Auf schmalem Platz untereinander | ja, im Player |
| Zentriert, Bild über oder unter dem Text | eine Spalte, Textblock links, mittig oder rechts | ja, im Player |
| Vollflächiges Bild oder Video | Bild oder Video als Hintergrund mit optionaler Abdunklung, Text darüber | ja, stumm im Hintergrund |
| Text mit Bild & Infokarten | wie "nebeneinander", dazu bis zu 3 halbtransparente Karten auf dem Bild | nein |
| Geteilt, Bild bis zum Rand | Fläche über die volle Breite des Elements: Text mit Innenabstand in einer Spalte, Bild oder Video bündig am Rand in der anderen, ohne Rundung und Schatten. Die Bildspalte füllt die Höhe der Fläche, auch wenn der Text höher wird. Unter 720 px Breite stehen Text und Bild untereinander | ja, im Player |

Ohne Bild und Video entfällt die Medien-Spalte, der Text nutzt die ganze Breite.


## Oben im Editor

| Feld | Wirkung |
|---|---|
| Darstellung | siehe oben |
| Maximale Breite | begrenzt den ganzen Hero und zentriert ihn: Volle Breite (keine Begrenzung), Breit (1440 px), Normal (1200 px), Schmal (960 px). Die Liste steht in `helpers.php` (`MAX_WIDTHS`) und lässt sich dort anpassen |


## Tab "Text & Buttons"

Die Bereiche "Buttons", "Dachzeile" (zugeklappt, solange leer), "Pluspunkte" und "Sonstiges" sind beim Öffnen zugeklappt, damit zuerst Überschrift, Text und Buttons sichtbar sind.

**Farbfelder mit Projektfarben:** Sind Projektfarben gepflegt, hat jedes Farbfeld (Überschrift, Hervorhebung, Text, Abdunklung, Kartenhintergrund, Kartentext) ein Auswahlfeld mit den Projektfarben, "- keine Farbe -" und "Individuell" (öffnet den Colorpicker). Je Feld wird das passende Feld des Eintrags genutzt: Überschrift, Hervorhebung, Text, Abdunklung und Kartenhintergrund nehmen `color`, der Kartentext `text_color`. Klassen spielen bei diesen Feldern keine Rolle. Gespeichert wird der Key des Eintrags (`@key`), die Farbe wird erst bei der Ausgabe eingesetzt, eine Änderung der Palette wirkt also auf alle Heros. Schon gespeicherte freie Farben bleiben als "Individuell" erhalten.

Die Reihenfolge der Felder entspricht der Ausgabe (Dachzeile, Überschrift, Text, Buttons, Pluspunkte; die Pluspunkte können über die Position auch zwischen Text und Buttons wandern).

| Feld | Wirkung |
|---|---|
| Überschrift | Pflicht. Ein Wort in `*Sternchen*` wird hervorgehoben |
| Typ der Überschrift | H1, H2, H3 oder Normal (einfacher Text ohne Überschrift-Tag). Pro Seite sollte es nur eine H1 geben, weitere Heros besser als H2 oder H3. Eine H1, H2 oder H3 erhält keine eigene Schriftgröße, Zeilenhöhe oder Schriftstärke und übernimmt die Darstellung der jeweiligen Überschrift deines Projekts. "Normal" nutzt die festen Werte aus der `style.css`, die du je Projekt anpassen kannst |
| Hervorhebungsfarbe | optional, sonst Standard aus dem CSS (`--gb-hero-accent`). Gilt für die hervorgehobenen Wörter in der Überschrift sowie für Häkchen, Icons und Trenner der Pluspunkte |
| Farbe der Überschrift | optional (steht unter "Typ der Überschrift"), sonst die Textfarbe der Seite (bei "Vollflächig" Weiß) |
| Farbe des Textes | optional (steht unter dem Text), wie oben. Gilt für den Text; die Pluspunkte übernehmen sie, solange keine eigene Pluspunktfarbe gesetzt ist |
| Dachzeile | optional, kleine Zeile (`p.gb-el-hero-eyebrow`) direkt über der Überschrift. Eigene Farbe (Feld "Farbe der Dachzeile") und Haken "Dachzeile fett". Leer = keine Ausgabe |
| Text fett | Haken, setzt den Text (`p.gb-el-hero-lead`) fett (Klasse `gb-el-hero-bold`) |
| Farbe der Pluspunkte | optional; leer = Farbe des Textes (wie bisher). Häkchen, Icons und Trenner behalten die Hervorhebungsfarbe |
| Pluspunkte fett | Haken, setzt die Pluspunkte fett (Klasse `gb-el-hero-bold`) |
| Maximale Breite des Textblocks | optional, Zahl (1 bis 9999) mit Einheit px oder % (bei % höchstens 100). Leer = keine Begrenzung. Wirkt als `max-width` auf `.gb-el-hero-text`; ungültige Eingaben werden ignoriert. Unabhängig von der "Maximalen Breite" des ganzen Heros |
| Große Überschrift | Häkchen, größere Schrift. Bei H1 bis H3 wächst die Schrift um den Faktor `--gb-hero-title-scale` (Standard 1,6) auf Basis der jeweiligen Überschrift deines Projekts (der Text steckt dafür in einem eigenen Inline-Element `<gb-hero-scale>`, damit keine Projekt-Regeln für `span` greifen), bei "Normal" gilt ein fester großer Wert aus der `style.css` |
| Text | Freitext, Zeilenumbrüche bleiben erhalten |
| Buttons | Repeater, höchstens 3 (weitere lassen sich im Editor nicht anlegen). Felder wie beim CTA-Element: Titel, Stil (Gefüllt, Umrandet, Textlink), Link, Linkbeschreibung (Screenreader), neuer Tab, Icon, Farbe (Projektfarbe oder "Individuell", siehe unten), Hover-Farben |
| Buttons | Klappbox mit dem Buttons-Repeater sowie Hover-Effekt und Eckenrundung |
| Hover-Effekt der Buttons | "- keine -" (Standard-Hover) oder einer der Effekte des CTA-Elements (Vergrößern, Pulsieren, Hüpfen, Gummiband, Schweben, Füllung von links, Glanzstreifen, Welle, Unterstreichung) |
| Verhältnis von Text und Bild | nur "Geteilt" (Medien-Tab): 70 : 30, 60 : 40 (Standard), 50 : 50, 40 : 60 oder 30 : 70. Die Liste steht in `helpers.php` (`SPLITS`). Steht das Bild links, spiegelt sich das Verhältnis |
| Kante des Bildes | nur "Geteilt": Gerade (Standard), Schräg (die Textfläche überdeckt einen Teil des Bildes) oder Schräg mit Akzentstreifen (Streifen in der Hervorhebungsfarbe). Der Überhang folgt der Breite des Elements |
| Eckenrundung der Buttons | Pille (Standard), Abgerundet oder Eckig; wirkt auf alle Buttons des Elements (Variable `--gb-hero-btn-radius`) |
| Kurze Pluspunkte | eine pro Zeile, erscheinen unter den Buttons (oder über der Überschrift, siehe Position) |
| Darstellung der Pluspunkte | "Mit Häkchen oder Icon" (Standard) oder "Nur Text, durch Punkte getrennt" (ohne Häkchen/Icon, Trennzeichen • in der Hervorhebungsfarbe) |
| Position der Pluspunkte | "Unter den Buttons" (Standard), oder "Zwischen Text und Buttons". Die frühere Position "Über der Überschrift" gibt es nicht mehr (dafür die Dachzeile); bereits gespeicherte Heros mit dieser Position werden unverändert ausgegeben, das Auswahlfeld zeigt dann "Unter den Buttons" und übernimmt das beim nächsten Speichern |
| Anordnung der Pluspunkte | "Nebeneinander" (Standard, umbrechend) oder "Untereinander" als Liste. Nur bei "Mit Häkchen oder Icon" |
| Icon der Pluspunkte | optional, ein Icon für alle Pluspunkte (Icon-Auswahl wie bei den Buttons). Leer = Häkchen. Nur bei "Mit Häkchen oder Icon" |
| Position des Textblocks | neun Positionen: Oben, Mitte oder Unten jeweils links, mitte oder rechts (ein Auswahlfeld). Die vertikale Position (oben, unten) wirkt nur bei "Vollflächig", bei "Zentriert" zählt nur die horizontale. Ältere Werte (links, Mitte, rechts) bleiben gültig und entsprechen "Mitte links", "Mitte" und "Mitte rechts" |
| Textausrichtung | links, Mitte oder rechts (nur "Zentriert" und "Vollflächig") |
| Horizontaler Abstand des Textes zum Rand | nur "Vollflächig" und "Geteilt": Standard, Groß oder Sehr groß. Der Abstand ist in Prozent der Breite angegeben und passt sich der Browserbreite an (mit Mindest- und Höchstwert) |


### Farben der Buttons

Wie beim CTA-Element: Sind in den GB2-Einstellungen "Projektfarben" gepflegt (oder liefert ein Addon sie über `GRIDBLOCK2_PALETTE`), zeigt jeder Button ein
Auswahlfeld "Farbe" mit den Projektfarben und "Individuell" (freie Farbfelder: Hintergrund, Text, Hover-Hintergrund, Hover-Text). Ohne Projektfarben bleiben die
Felder Hintergrund und Text wie bisher, die Hover-Farben sind eingeklappt. Hover: Eine Zeile ohne Hover-Angabe tauscht Füllung und Text (Gefüllt mit innerem
Ring), mit Hover-Angabe gelten diese Werte; Textlinks ignorieren Hover-Farben. Hat ein Eintrag der Palette eine **Klasse** (`.uk-background-primary`), kommt diese Klasse zusätzlich an den Button, Form und Hover-Effekte bleiben. Die Standardfarben (blauer Verlauf, weiße Schrift) hängen an der Klasse `gb-el-hero-btn-default`, die nur ohne Palettenfarbe gesetzt wird. `text_color` des Eintrags (falls vorhanden) setzt die Schriftfarbe. Eingetragene Hover-Werte wirken auch hier, einen automatischen Tausch gibt es bei Klassen nicht. Die Klasse gilt nur bei "Gefüllt": Bei "Umrandet" und "Textlink" wird sie nicht ausgegeben, `color` ist dort die Farbe von Schrift und Rahmen (ohne `color` gilt die Standardfarbe). Gespeichert wird der Key der Projektfarbe (fest vergeben, unabhängig von Bezeichnung und Klasse). Die Ausgabe nutzt die Klassen
`gb-el-hero-btn-pal`, `-hov` und `-ring` sowie die Variablen `--gb-hero-btn-bg`, `-fg`, `-hbg`, `-hfg` und `-hbd` am Button. Die Hover-Effekte hängen
als Klasse `gb-el-hero-fx-NAME` am Wrapper `.gb-el-hero-buttons`.

## Tab "Bild & Video"

Solange weder Bild noch Video gewählt ist, zeigt der Editor (Builder-Vorschau und Editfenster) eine graue Platzhalterfläche mit Bild-Symbol (`gb-el-hero-placeholder`, reines Inline-SVG, kein Medienpool). Im Frontend wird sie nicht ausgegeben.

| Feld | Wirkung |
|---|---|
| Bild | aus dem Medienpool, mindestens 1600 px breit. Alt-Text aus der Medienpool-Beschreibung (`med_description`), leer = dekorativ |
| Video | optional, nur MP4 aus dem Medienpool. Ersetzt das Bild, das Bild dient als Standbild |
| Untertitel | optional, VTT-Datei aus dem Medienpool plus Sprache (Deutsch, English, Français, Italiano, Español, Slovenščina). Nur bei "Nebeneinander" und "Zentriert" (Player); das stumme Hintergrundvideo bei "Vollflächig" hat keine Untertitel. Im Player standardmäßig eingeschaltet |
| Ausrichtung des Bildes | welcher Teil des Bildes sichtbar bleibt, wenn das Bildformat es beschneidet: Mitte (Standard) oder eine der acht Randpositionen (links oben bis rechts unten). Gilt auch für Video und Standbild |
| Abgerundete Ecken | Häkchen, Bild und Video mit abgerundeten Ecken (Standard: an, nicht bei "Vollflächig"). Ohne Häkchen sind sie eckig |
| Schatten am Bild | Häkchen, Schatten unter Bild und Video (Standard: aus, nicht bei "Vollflächig") |
| Bildformat | 1:1, 16:9 (Standard), 21:9, 3:1, 4:3, 3:2, 8:5, 5:8, 2:3, 3:4, 4:5. Gilt für alle Darstellungen. Bei "Vollflächig" ist es die Mindesthöhe der Fläche, bei viel Text wächst sie nach unten. Bei "Geteilt" gilt es für die Bildspalte (Breite der Spalte : Höhe), nicht für die ganze Fläche; wird der Text höher, wächst die Fläche |
| Position des Bildes | links oder rechts vom Text (nebeneinander, Infokarten) bzw. über oder unter dem Text (zentriert) |
| Abdunklung | nur "Vollflächig": Keine, Leicht, Mittel, Stark, Verlauf von unten, Verlauf von links unten (stark in der linken unteren Ecke, läuft bei etwa 70 % der Breite aus), Verlauf von rechts unten (spiegelverkehrt, gleiche Werte) |
| Farbe der Abdunklung | nur "Vollflächig", optional, sonst Schwarz. Gilt für alle Abdunklungen und Verläufe (Farbwahl mit Deckkraft über die Stufen). Bei heller Farbe den Kontrast des weißen Textes prüfen |
| Infokarten | nur "Infokarten": Repeater, höchstens 3 (weitere lassen sich im Editor nicht anlegen). Pro Karte ein optionales Bild und ein Text, keine Titel. Im Text wird `*Wort*` fett dargestellt |
| Kartenhintergrund, Kartentext | Farben der Karten (Hintergrund auch mit Transparenz) |
| Position der Karten | nach außen versetzt oder innerhalb des Bildes |
| Vertikale Position der Karten | oben, Mitte oder unten |

Die Felder der Karten heißen `kbild` und `ktext`, damit sie sich nicht mit den Hauptfeldern `bild` und `text` überschreiben.


## Video

- **Vollflächig:** natives HTML-Video im Hintergrund (stumm, Schleife, `playsinline`) mit Pause-Knopf. Bei der Systemeinstellung
  "Bewegung reduzieren" wird es pausiert. Das Bild ist das Standbild.
- **Nebeneinander und Zentriert:** Vidstack-Player (Addon `vidstack`), ohne Autostart. Ist das Addon nicht aktiv, wird ein
  natives `<video controls>` ausgegeben.
- **Frontend:** Vidstack braucht CSS und JS im Template (siehe README des Addons: `vidstack.css`, `vidstack_helper.css`,
  `vidstack.js`, `vidstack_helper.js`). Das lädt das Element nicht selbst.
- **Backend-Vorschau:** zeigt das Standbild mit Play-Symbol, kein laufendes Video.


## Ausgabe (Markup)

```html
<div class="gb-el-hero gb-el-hero-layout-nebeneinander gb-el-hero-ratio-16-9 gb-el-hero-align-links gb-el-hero-pos-links gb-el-hero-side-rechts"
     style="--gb-hero-max:1200px;">
  <div class="gb-el-hero-inner">
    <div class="gb-el-hero-text">
      <h1 class="gb-el-hero-title">Ein <span class="gb-el-hero-accent">Einstieg</span></h1>
      <p class="gb-el-hero-lead">…</p>
      <div class="gb-el-hero-buttons"><a class="gb-el-hero-btn gb-el-hero-btn-fill" href="…">…</a></div>
      <ul class="gb-el-hero-bullets"><li>…</li></ul>
    </div>
    <div class="gb-el-hero-media"><img class="gb-el-hero-img" src="…" alt="…"></div>
  </div>
</div>
```

Bei "Vollflächig" steht `.gb-el-hero-media` (mit `.gb-el-hero-overlay`) vor `.gb-el-hero-inner`. Bei den Infokarten enthält
`.gb-el-hero-media` zusätzlich `.gb-el-hero-cards` mit den `.gb-el-hero-card`.


## Barrierefreiheit

- Buttons: Linkbeschreibung als `aria-label`, bei neuem Fenster mit dem Zusatz "(öffnet in neuem Tab)", `rel="noopener"`.
- Hintergrundvideo: dekorativ (`aria-hidden`), mit sichtbarem Pause-Knopf (WCAG 2.2.2).
- Hervorhebung nur per Farbe: Der Kontrast der gewählten Farbe zum Hintergrund liegt bei dir.
- Ohne Abdunklung bei "Vollflächig" den Kontrast des weißen Textes zum Bild prüfen.
- Karten und Pluspunkte sind echter Text, keine Bilder. Das Häkchen vor den Pluspunkten ist für Screenreader ausgeblendet.


## Styling

Das Addon schreibt keine Styles ins Markup außer der Breite (`--gb-hero-max`) und den Kartenfarben. Die Gestaltung steckt in
`style.css` und gilt für die Backend-Vorschau. Für die Website entweder die Klassen im Projekt-CSS gestalten oder in den
Addon-Einstellungen "Element-CSS im Frontend" aktivieren.

Anpassbar über CSS-Variablen am Element `.gb-el-hero`:

| Variable | Standard | Bedeutung |
|---|---|---|
| `--gb-hero-max` | keine | maximale Breite (wird vom Feld gesetzt) |
| `--gb-hero-gap` | `60px` | Abstand zwischen Text und Bild, wenn sie untereinander stehen (Zentriert, Handy) |
| `--gb-hero-gap-wide` | `80px` | Abstand zwischen Text und Bild bei "Nebeneinander" und "Infokarten" (nebeneinander stehend) |
| `--gb-hero-inset` | `clamp(16px, 5%, 64px)` | Seitlicher Abstand des Textes bei "Vollflächig" ("Groß" und "Sehr groß" setzen ihn auf `clamp(24px, 10%, 140px)` bzw. `clamp(32px, 15%, 220px)`) |
| `--gb-hero-text-max` | `74rem` | Maximale Breite des Textblocks bei "Vollflächig". Bei "Zentriert" ist der Textblock bis 96 rem breit (fast die ganze Breite des Heros) |
| `--gb-hero-overlay` | `#000` | Farbe der Abdunklung bei "Vollflächig" |
| `--gb-hero-radius` | `12px` | Rundung von Bild und Video |
| `--gb-hero-accent` | `#2c6196` | Farbe der Hervorhebung und der Häkchen |
| `--gb-hero-btn-a`, `--gb-hero-btn-b` | `#3a76b2`, `#2c6196` | Verlauf der gefüllten Buttons |
| `--gb-hero-btn-accent` | `#2c6196` | Farbe der Buttons "Umrandet" und "Textlink" |
| `--gb-hero-btn-on` | `#fff` | Textfarbe auf der Akzentfarbe (Hover-Effekt "Füllung von links" bei "Umrandet") |
| `--gb-hero-btn-shadow` | `rgba(44, 97, 150, 0.35)` | Schatten der Buttons bei Hover-Effekten |
| `--gb-hero-card-bg`, `--gb-hero-card-color` | `rgba(255,255,255,.78)`, `#1f2d3d` | Karten (werden von den Feldern gesetzt) |

Die Karten reagieren beim Darüberfahren mit einer kleinen Bewegung. Bei der Systemeinstellung "Bewegung reduzieren" entfällt sie.
