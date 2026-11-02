# Custom-Element "Zitat"

Ein oder mehrere Zitate mit Autor, Funktion, Quelle (mit Link) und Bild. Drei Darstellungen: einzeln, Raster
und Streifen (seitlich scrollbar). Es wird keine Bibliothek und kein JavaScript benötigt.

Diese Datei ist nur eine Hilfe für Admins und wird vom Addon nicht ausgewertet.


## Dateien dieses Elements

| Datei | Aufgabe |
|---|---|
| `element.yml` | Name, Symbol, Beschreibung |
| `input.php` | Editor im Builder (Darstellung, Zitate-Liste, Ausrichtung, Bild) |
| `output.php` | Ausgabe |
| `style.css` | Gestaltung der Backend-Vorschau (siehe Abschnitt Styling) |
| `README.md` | diese Hilfe |


## Einstellungen

| Feld | Wirkung |
|---|---|
| Darstellung | Einzeln (nur das erste aktive Zitat, groß), Raster oder Streifen (scrollbar) |
| Spalten | Raster: höchstens so viele Spalten. Streifen: so viele Zitate gleichzeitig sichtbar. Auf schmalem Platz werden es automatisch weniger |
| Ausrichtung | Links, Mitte oder Rechts (gilt für alle Zitate) |
| Bild steht | Links oder rechts vom Autor |
| Bilder rund darstellen | Standard ist eckig |
| Hintergrundfarben ausschalten | entfernt die Hintergrundfarbe der Zitate und des Autorenblocks, der feine Rahmen bleibt |

Pro Zitat (Zeilen lassen sich verschieben, kopieren, ausschalten und einklappen):

| Feld | Wirkung |
|---|---|
| Zitat | Text, Zeilenumbrüche bleiben erhalten. Zeilen ohne Text werden nicht ausgegeben |
| Autor | optional |
| Funktion | optional, erscheint unter dem Autor |
| Quelle | optional |
| Link der Quelle | optional, nur externe Links, öffnet in neuem Tab (für Screenreader per `aria-label` mit "(öffnet in neuem Tab)" angekündigt). Ohne Quelltext wird der Domainname als Linktext verwendet |
| Bild / Logo | optional, wird quadratisch (1:1) dargestellt. SVG wird eingepasst, andere Formate werden zugeschnitten |

Das Bild bekommt ein leeres `alt`, weil der Name direkt daneben steht.


## Bestehende Zitate

Zitate aus der früheren Version (Felder Text, Autor, Quelle) werden weiter ausgegeben. Beim ersten Öffnen im Editor
erscheinen sie als erste Zeile der Liste.


## Ausgabe (Markup)

```html
<div class="gb-el-zitate gb-el-zitate-layout-raster gb-el-zitate-cols-3 gb-el-zitate-imgpos-links gb-el-zitate-round">
  <div class="gb-el-zitate-list">
    <figure class="gb-el-zitat gb-el-zitat-align-links">
      <blockquote class="gb-el-zitat-text" cite="https://…"><p>Text</p></blockquote>
      <figcaption class="autor gb-el-zitat-cap-img">
        <img class="gb-el-zitat-img" src="…" alt="">
        <span class="gb-el-zitat-cap-text">
          <span class="gb-el-zitat-author">Name</span>
          <span class="gb-el-zitat-role">Funktion</span>
          <cite class="gb-el-zitat-source"><a href="…">Quelle</a></cite>
        </span>
      </figcaption>
    </figure>
  </div>
</div>
```

Der Streifen-Container (`.gb-el-zitate-list`) trägt zusätzlich `role="region"`, `aria-label="Zitate"` und `tabindex="0"`,
damit er per Tastatur gescrollt werden kann.


## Styling

Das Addon schreibt keine Styles ins Markup. Die Gestaltung steckt in `style.css` und gilt für die Vorschau im Backend.
Für die Website entweder die Klassen im Projekt-CSS gestalten oder in den Addon-Einstellungen „Element-CSS im Frontend“
aktivieren (dann wird die `style.css` aller Elemente eingebunden).

Anpassbar über CSS-Variablen am Element `.gb-el-zitate`:

| Variable | Standard | Bedeutung |
|---|---|---|
| `--gb-zitat-gap` | `20px` | Abstand zwischen den Zitaten |
| `--gb-zitat-img-size` | `52px` | Kantenlänge des Bildes |
| `--gb-zitat-slide-min` | `280px` | Mindestbreite eines Zitats im Streifen |
| `--gb-zitat-grid-min` | `260px` | Mindestbreite einer Spalte im Raster |

An `.gb-el-zitat` (Farben): `--zt-border` (Rahmen, Standard wie Autorenblock), `--zt-bg`, `--zt-text`, `--zt-mark`, `--zt-mark-opacity`, `--zt-cap-text`, `--zt-cap-bg`.

Das Raster nutzt CSS-Spalten (`column-count`). Die Zitate laufen deshalb spaltenweise von oben nach unten, die
Reihenfolge ist also nicht zeilenweise. Der Streifen nutzt `scroll-snap` und eine eingefärbte Scrollleiste
(Farben über `--zt-thumb` und `--zt-track` an `.gb-el-zitate-list`).
