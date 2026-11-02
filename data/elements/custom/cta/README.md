# Custom-Element "Call-to-Action Button"

Ein oder mehrere Buttons mit Link, Icon und Stil (gefüllt, umrandet, Textlink). Es wird kein JavaScript benötigt.

Diese Datei ist nur eine Hilfe für Admins und wird vom Addon nicht ausgewertet.


## Dateien dieses Elements

| Datei | Aufgabe |
|---|---|
| `element.yml` | Name, Symbol, Beschreibung |
| `input.php` | Editor im Builder (Buttons-Liste und Einstellungen) |
| `output.php` | Ausgabe |
| `helpers.php` | Liste der Hover-Effekte und Farbhilfen (Klasse `GbCtaKit`) |
| `style.css` | Gestaltung der Backend-Vorschau (siehe Abschnitt Styling) |
| `README.md` | diese Hilfe |


## Buttons (Liste)

Jede Zeile ist ein Button. Die Zeilen lassen sich verschieben, kopieren, ausschalten und einklappen. Buttons ohne Titel
werden nicht ausgegeben.

| Feld | Wirkung |
|---|---|
| Titel | Beschriftung des Buttons |
| Stil | Gefüllt, Umrandet oder Textlink |
| Link | alle Linktypen (Artikel, URL, Medium, E-Mail, Telefon). Ohne Link wird ein `<span>` statt eines `<a>` ausgegeben |
| Linkbeschreibung | optional, für Screenreader. Ersetzt den Titel als zugänglicher Name (`aria-label`). Sie sollte den sichtbaren Titel enthalten und das Ziel beschreiben. Leer = Titel |
| Link in neuem Fenster öffnen | Häkchen unter dem Link, setzt `target="_blank"` und `rel="noopener"`. Screenreader erfahren automatisch "(öffnet in neuem Tab)" |
| Fontawesome-Icon | vollständige Klassen, wie im Frontend eingebunden (z. B. `fas fa-arrow-right`). Ist das Addon `fa_iconpicker` aktiv, öffnet ein Klick ins Feld die Icon-Auswahl |
| Position Icon | links oder rechts vom Titel |
| Farbe | nur sichtbar, wenn in den GB2-Einstellungen Projektfarben gepflegt sind: eine Projektfarbe oder "Individuell" (siehe unten) |
| Hintergrundfarbe | nur bei "Gefüllt". Bei vorhandenen Projektfarben unter "Individuell" |
| Textfarbe | bei "Umrandet" auch Rahmenfarbe, bei "Textlink" die Linkfarbe |
| Hover-Hintergrund, Hover-Text | optional, bei "Gefüllt" und "Umrandet". Ohne Projektfarben eingeklappt unter "Hover-Farben" |

Eigene Farben legen Redakteure frei fest, der Kontrast wird nicht geprüft.

## Projektfarben

Pflegt der Admin in den GB2-Einstellungen "Projektfarben (Palette)" (JSON, je Farbe ein Eintrag mit festem Key und den Feldern `label`, `class`, `color`, `text_color`, `hover_color`, `hover_text_color`,
Variablen wie `--col-primary` oder Farbwerte, optional je Domain), erscheint pro Button ein Auswahlfeld "Farbe" mit Farbfeldern.
Der letzte Eintrag "Individuell" zeigt die freien Farbfelder, dann gilt nichts automatisch. Ein neuer Button startet mit der ersten Projektfarbe,
bestehende Buttons mit eigenen Farben erscheinen als "Individuell".

| Fall | Hover |
|---|---|
| Zeile ohne Hover-Angabe | automatischer Tausch: Gefüllt = Füllung in der Textfarbe, Text in der Hintergrundfarbe, mit innerem Ring in der Hintergrundfarbe. Umrandet = Füllung in der Hintergrundfarbe, Text in der Textfarbe (leer: `--cta-on-accent`) |
| Zeile mit Hover-Angabe | diese Werte. Ist nur eines der beiden Hover-Felder gefüllt, bleibt das andere wie im Normalzustand |
| Hintergrund `transparent` ohne Hover-Angabe | kein Tausch, es bleibt der normale Hover |
| Textlink | ignoriert Hover-Farben |
| Schlüssel der Projektfarbe fehlt (z. B. nach Import in ein anderes Projekt) | Standardfarben |

Hat ein Eintrag eine **Klasse** (`.uk-background-primary`), kommt diese Klasse zusätzlich an den Button, Form, Hover-Effekte und Schatten bleiben. Die Standardfarben (blauer Verlauf, weiße Schrift) gelten nur ohne Farbwahl: Sie hängen an der Klasse `gb-el-cta-default`, die nur ohne Palettenfarbe gesetzt wird. Eigene Module, die die Button-Klassen selbst ausgeben, setzen `gb-el-cta-default` für den Standardverlauf mit. `color` dient bei einer Klasse nur als Anzeigefarbe im Auswahlfeld und als Farbe für die Hover-Effekte, `text_color` (falls vorhanden) setzt die Schriftfarbe. Eingetragene Hover-Werte (`hover_color`, `hover_text_color`) wirken auch hier, einen automatischen Tausch gibt es bei Klassen nicht. Die Klasse gilt nur bei "Gefüllt": Bei "Umrandet" und "Textlink" wird sie nicht ausgegeben, `color` ist dort die Farbe von Schrift und Rahmen (ohne `color` gilt die Standardfarbe).

Gespeichert wird der Schlüssel der Zeile (die Klasse, sonst aus der Bezeichnung abgeleitet), nicht die Farbe: Ändert der Admin eine Variable oder einen Wert in der Palette,
ziehen alle Buttons mit. Wird eine Bezeichnung umbenannt, verlieren Buttons dieser Farbe ihre Zuordnung und zeigen die Standardfarben.


## Einstellungen (gelten für alle Buttons des Elements)

| Feld | Wirkung |
|---|---|
| Ausrichtung | links, Mitte oder rechts |
| Eckenrundung | Pille, Abgerundet oder Eckig |
| Hover-Effekt | "- keine -" (Standard, der bisherige Hover) oder einer der Effekte unten |
| Schatten | Häkchen, Standard an (wirkt nur bei "Gefüllt") |
| Volle Breite | jeder Button über die gesamte Breite, mehrere Buttons stehen untereinander |


## Bestehende Buttons

Buttons aus der früheren Version (ein Button mit Titel, Icon, Link und Farben) werden unverändert ausgegeben, auch mit
automatisch neuem Tab bei externen URLs. Beim ersten Öffnen im Editor erscheinen sie als erste Zeile der Liste.


## Ausgabe (Markup)

```html
<div class="gb-el-cta-align gb-el-cta-align-links gb-el-cta-radius-pille gb-el-cta-shadow-off gb-el-cta-full">
  <a class="gb-el-cta gb-el-cta-style-fill gb-el-cta-icon-rechts" href="…" target="_blank" rel="noopener"
     aria-label="Linkbeschreibung (öffnet in neuem Tab)" style="background:#…;color:#…;">
    Titel <i class="fas fa-arrow-right" aria-hidden="true"></i>
  </a>
</div>
```

Eigene Farben stehen direkt am Button (`background`, `color`), damit sie auch ohne die `style.css` wirken. Projektfarben und eigene Hover-Farben kommen
stattdessen als Variablen (`--cta-bg`, `--cta-fg`, `--cta-hbg`, `--cta-hfg`, `--cta-hbd`) mit den Klassen `gb-el-cta-pal`, `gb-el-cta-hov` und `gb-el-cta-ring` und brauchen die `style.css`. Rundung,
Schatten, Breite und Hover-Effekt werden als Klassen am äußeren `div` ausgegeben (`gb-el-cta-fx-NAME`).


## Hover-Effekte

Der Effekt gilt für alle Buttons des Elements. Er braucht kein zusätzliches Markup (nur `::before`/`::after`) und wirkt auch bei
Tastaturfokus (`:focus-visible`). Bei der Systemeinstellung "Bewegung reduzieren" laufen Effekte ohne Übergang und Animation.

| Effekt | Klasse | Gefüllt / Umrandet | Textlink |
|---|---|---|---|
| Vergrößern | `gb-el-cta-fx-vergroessern` | auf 107 % | wie links |
| Pulsieren | `gb-el-cta-fx-pulsieren` | ein Ring pulsiert nach außen | Textschein pulsiert |
| Hüpfen | `gb-el-cta-fx-huepfen` | einmaliges Hüpfen | wie links |
| Gummiband | `gb-el-cta-fx-gummiband` | elastisches Zusammendrücken und Dehnen | wie links |
| Schweben | `gb-el-cta-fx-schweben` | sanftes Treiben, schneller Rückweg beim Verlassen | wie links |
| Füllung von links | `gb-el-cta-fx-fuellung-links` | Fläche füllt sich von links, Rahmen und Füllung enden in derselben Farbe | Linie läuft von links unter dem Text ein |
| Glanzstreifen | `gb-el-cta-fx-glanz` | breiter Lichtstreifen läuft diagonal über den Button | Textmarker wächst hinter dem Text |
| Welle | `gb-el-cta-fx-welle` | heller Ring breitet sich einmal aus der Mitte aus | weiche Fläche blendet hinter dem Text ein |
| Unterstreichung | `gb-el-cta-fx-unterstreichen` | Linie wächst aus der Mitte | Linie wächst von links |

**Farben:** Die Effekte richten sich nach den Farben des Buttons (Projektfarbe oder eigene Farben). Ist ein Effekt gewählt, setzt das Element am Button die
Akzentfarbe (`--cta-accent`) aus der Hintergrundfarbe (Gefüllt, bei einem Verlauf die erste Farbe) bzw. der Textfarbe (Umrandet, Textlink).
Ring, Schein und Füllung (Umrandet) nutzen diese Farbe, die Füllung bei "Gefüllt" dunkelt die eigene Fläche um 30 % ab. Ohne Effekt bleibt
alles wie bisher.

Wer die Effekte in eigenen Modulen nutzen will, übernimmt die Regeln aus `style.css` (Abschnitt "Hover-Effekte") und setzt die Klasse
an einen Wrapper um Buttons mit den Klassen `gb-el-cta` und `gb-el-cta-style-fill|outline|link`.

"Schweben" braucht die registrierte CSS-Variable `--gb-cta-k`. In der Frontend-`style.css` steht sie als `@property`; für die
Shadow-DOM-Vorschau im Builder ist sie zusätzlich in `assets/css/gb-builder.min.css` registriert (`@property` gilt nur im Dokument).
Ohne diese Registrierung funktioniert der Effekt weiter, nur der weiche Rückweg beim Verlassen entfällt.


## Styling

Das Addon schreibt keine weiteren Styles ins Markup. Die Gestaltung steckt in `style.css` und gilt für die Vorschau im
Backend. Für die Website entweder die Klassen im Projekt-CSS gestalten oder in den Addon-Einstellungen "Element-CSS im
Frontend" aktivieren.

Anpassbar über CSS-Variablen:

| Variable | Standard | Element |
|---|---|---|
| `--gb-cta-gap` | `12px` | `.gb-el-cta-align`, Abstand zwischen mehreren Buttons |
| `--cta-fill-a`, `--cta-fill-b` | `#3a76b2`, `#2c6196` | `.gb-el-cta`, Verlauf des gefüllten Buttons (weiße Schrift: Kontrast mindestens 4,5:1) |
| `--cta-accent` | `#2c6196` | `.gb-el-cta`, Farbe von "Umrandet" und "Textlink", wenn keine Textfarbe gesetzt ist |
| `--cta-radius` | `999px` | `.gb-el-cta`, wird von den Rundungsklassen gesetzt |
| `--cta-on-accent` | `#fff` | `.gb-el-cta`, Textfarbe auf der Akzentfarbe (Effekt "Füllung von links" bei Umrandet) |
| `--cta-shadow`, `--cta-shadow-hover` | `rgba(44, 97, 150, …)` | `.gb-el-cta`, Schattenfarbe |

Icons: Das Feld ist ein Textfeld mit der Klasse `rex-fa-iconpicker`. Mit dem Addon `fa_iconpicker` erscheint die
Icon-Auswahl (Einstellungen `data-fa-add-weight="1"` und `data-fa-preview-weight="S"`: eingetragen wird z. B. `fas fa-user`, Standardstärke Solid), ohne das Addon bleibt es ein
normales Textfeld. In der Backend-Vorschau lädt `output.php` das FontAwesome-CSS des aktiven Pakets von `fa_iconpicker`. Ohne
das Addon kennt `style.css` nur `fa-arrow-right`. Für die Website bindest du FontAwesome selbst ein, bei `fa_iconpicker`
mit `REX_FA_ICONPICKER[]` im `<head>` des Templates (nur direkt im Template, nicht in Includes). Der Picker setzt den Wert
ohne Ereignis, deshalb meldet `input.php` die Auswahl über `data-fa-onselect="gb2CtaIconPicked"` per `change` an den Repeater
und die Live-Vorschau.
