# Gridblock: eigene Custom-Elemente ("Mini-Elemente") anlegen

Zusätzlich zu echten REDAXO-Modulen und den System-Elementen (Sektion/Spalten, siehe `elements/system/`)
können hier eigene, schlanke, projekteigene Elemente angelegt werden - ganz ohne Datenbank-Migration oder
Code-Änderung am Addon selbst. Vorbild ist der bei `fm_builder`.

```
elements/custom/
  <typ>/
    element.yml     erforderlich  - label, icon, group, description (siehe unten)
    input.php        erforderlich  - Backend-Formularfelder (Bearbeiten-Ansicht im Builder)
    output.php       erforderlich  - Frontend-Ausgabe (echtes Rendering auf der Website)
    style.css        optional      - nur für die Backend-Live-Vorschau im Builder (siehe unten)
```

`<typ>` ist zugleich der interne Bezeichner (Ordnername = `elementType` im gespeicherten JSON-Baum) -
Kleinbuchstaben, keine Sonderzeichen, z. B. `spacer`, `zitat`.

Ein Ordner ohne alle 3 Pflichtdateien wird beim Durchsuchen einfach ignoriert (kein Fehler).


## style.css (optional)

`output.php` liefert bewusst nur nackte HTML-Klassen (`gb-el-<typ>`) ohne eigenes Grundlayout - auf der
echten Website übernimmt das eigene Projekt-CSS die Gestaltung. Im Builder selbst (Backend-Vorschau) gibt
es dieses Projekt-CSS aber oft nicht oder nicht passend, wodurch das Element dort unstyled/leer wirkt.

Liegt neben `input.php`/`output.php` zusätzlich eine `style.css`, wird ihr Inhalt automatisch als
`<style>`-Block NUR in die Backend-Live-Vorschau eingebunden - nicht auf der echten Website (dort bleibt
weiterhin die eigene Projekt-CSS zuständig). Kein zusätzlicher Handgriff nötig, die Datei wird einfach
über `Gridblock2Builder_Tree::getCustomElementPreviewCss()` erkannt, sobald sie existiert.


## element.yml

```yaml
label: Zitat
icon: rex-icon fa-quote-right
group: special
description: Zitat mit Autor, optisch hervorgehoben.
```

`group` bestimmt, in welcher Kategorie das Element im Fenster "Element hinzufügen" erscheint: `layout`
(neben Sektion/Spalten), `standard` (neben den echten Modulen) oder `special` (Kategorie "Weitere
Elemente", ganz unten). Ohne Angabe: `special`. Jeder andere Wert (z. B. `group: Formulare`) legt eine
eigene Kategorie an, deren Überschrift genau dieser Text ist; gleich geschriebene Werte (ohne Beachtung
der Groß-/Kleinschreibung) landen in derselben Kategorie. Eigene Kategorien stehen zwischen den Modulen
und "Weitere Elemente", in der Reihenfolge ihres ersten Auftretens.

`description` (optional) ist ein kurzer Text, der im Fenster "Element hinzufügen" unter dem Titel steht.

Optional: `preview: iframe` oder `preview: shadowdom` legt den Vorschau-Modus dieses Elements fest und
überschreibt die globale Vorgabe aus den Einstellungen (nur dieses Element). `iframe` = mit JavaScript,
`shadowdom` = schneller, nur CSS. Ohne Angabe gilt die globale Einstellung.

Optional: `status: offline` blendet dieses Element aus dem "Hinzufügen"-Menü aus, ohne die Datei zu
löschen - praktisch, um einen Baustein projektweit vorübergehend zu deaktivieren. Bereits platzierte
Instanzen bleiben unverändert render-/editierbar. Ohne Angabe (oder `status: online`) ist das Element
wie bisher immer verfügbar.

Optional: `requires` macht ein Element von einem oder mehreren Addons abhängig - es ist dann nur verfügbar
(im "Hinzufügen"-Menü, in der Vorschau, in der Ausgabe), wenn alle genannten Addons installiert und aktiv sind:

```yaml
requires: vector_maps
# oder mehrere:
# requires:
#   - vector_maps
#   - yform
```

Fehlt ein Addon, erscheint das Element nicht im Menü. Bereits gespeicherte Instanzen bleiben erhalten: der
Builder zeigt statt des Formulars einen Hinweis (Verschieben und Löschen bleiben möglich), im Frontend wird
nichts ausgegeben. Ohne Angabe ist das Element immer verfügbar. Gilt auch für das System-Element Sektion
(`data/elements/system/section/element.yml`, dort nur für das "Hinzufügen"-Menü). Für Spalten wird die Angabe ignoriert.

Optional: `permission: true` macht ein Element rechtegesteuert. In der Rollenverwaltung erscheint dann (direkt unter
"Module") das Feld "Gridblock-Elemente", in dem du je Rolle festlegst, welche dieser Elemente genutzt werden dürfen
(oder "Alle Gridblock-Elemente"). Das Rollenrecht gilt für Admins nicht (die Elementauswahl je Domain gilt beim Hinzufügen trotzdem), im Demobereich gilt keine Einschränkung.

```yaml
permission: true
```

Ohne Recht fehlt das Element im "Hinzufügen"-Menü. Bereits platzierte Instanzen bleiben sichtbar (Vorschau,
Frontend) und lassen sich verschieben, aber nicht bearbeiten, kopieren, ein-/ausblenden oder löschen. Ohne Angabe
ist das Element für alle Rollen verfügbar. Gilt auch für Elemente aus anderen Addons (Schlüssel `<addon>.<typ>`) und
für das System-Element Sektion (`data/elements/system/section/element.yml`). Für Spalten wird die Angabe ignoriert.


## Theme-Klasse/-Style aus einer umschließenden Spalte übernehmen

Ein Inhaltselement (Custom-Element oder echtes Modul), das innerhalb einer Spalte liegt, hat in der
Backend-Vorschau eine EIGENE, von der Spalte komplett getrennte Vorschau (eigener Shadow-Root ODER
eigenes iframe-Dokument, je nach Vorschau-Modus) - normale CSS-Vererbung (auch über CSS-Variablen)
erreicht es daher nicht. Setzt `column.php` (siehe `elements/system/column/`) auf einem beliebigen
eigenen Element `data-gb2-theme-class="..."` und/oder `data-gb2-theme-style="..."`, reicht der Builder
diese Werte automatisch bis in die `.gb2-preview-content`-Hülle jedes direkt oder verschachtelt darin
liegenden Inhaltselements durch - auch in eigene Custom-Elemente hinein, unabhängig davon, ob das
jeweilige Element per Shadow-DOM oder iframe läuft (im iframe landet die Klasse direkt auf dessen echtem
`<body>`), ganz ohne eigenes Zutun in `input.php`/`output.php`. Was genau durchgereicht wird (z. B. eine
Farbset-Klasse wie `bc-light`), entscheidet ausschließlich das eigene `column.php` - der Addon-Kern
transportiert nur, wertet den Inhalt aber nicht aus.


## Elemente aus anderen Addons

Ein Addon kann eigene Gridblock-Elemente mitliefern, ganz ohne Eingriff in dieses Projekt. Dazu legt es
dieselbe Ordnerstruktur wie oben in seinem eigenen Addon-Ordner ab:

```
redaxo/src/addons/<addon>/gridblock2/elements/<typ>/
  element.yml, input.php, output.php, style.css (optional)
```

Solche Elemente erscheinen automatisch im Add-Menue, solange das Addon verfuegbar (installiert und aktiv)
ist. Ihr Schluessel lautet `<addon>.<typ>` (z. B. `vector_maps.location_map`) - der Praefix verhindert
Kollisionen zwischen Addons und steht so im gespeicherten Baum.

**Anpassen ohne Eingriff ins Fremdaddon:** Ein Ordner mit demselben vollstaendigen Schluessel im Projekt-
Ordner (`data/addons/gridblock2/elements/custom/vector_maps.location_map/`) hat Vorrang vor dem Element des
Addons. Einfach den Addon-Ordner dorthin kopieren und anpassen - das Original bleibt unangetastet und
update-sicher.

**Hinweis zur Sicherheit:** `input.php` und `output.php` eines Addon-Elements laufen als normales PHP im Backend (Bearbeiten, Vorschau) und im Frontend (Ausgabe) - mit denselben Rechten wie der restliche Code des Addons. Gridblock² prueft den Inhalt nicht. Installiere und aktiviere daher nur Addons, denen du vertraust, genau wie bei jedem anderen REDAXO-Addon.

Wird das Addon spaeter deaktiviert oder entfernt, bleiben bereits gespeicherte Elemente erhalten: der Builder
zeigt dann einen Hinweis statt des Formulars, Verschieben und Loeschen bleiben moeglich, die gespeicherten
Werte gehen beim Speichern nicht verloren. Die Frontend-Ausgabe entfaellt, solange das Element nicht
aufloesbar ist.


## preview_css (optional): CSS von Addons in der Live-Vorschau

Die Live-Vorschau eines Elements laeuft in einem Shadow-Root. Das Backend-CSS (z. B. von Addons wie vector_maps)
erreicht ihn nicht. Damit die Vorschau trotzdem korrekt aussieht, kann die `element.yml` zusaetzliche
Stylesheets aus Addon-Assets angeben:

```yaml
preview_css:
  - addon:vector_maps/maplibre/maplibre-gl.css
  - addon:vector_maps/build/vectormaps.css
```

Format: `addon:<addonname>/<pfad-innerhalb-der-assets>`. Die Pfade werden ueber REDAXO aufgeloest (funktioniert
auch bei Installation in einem Unterordner), mit Cache-Buster versehen und nur in der Backend-Vorschau
geladen, nicht im Frontend. Nicht vorhandene Dateien werden ignoriert.


## Variablen im Scope

Beide Dateien laufen per normalem PHP-`include` innerhalb eines regulär gebooteten REDAXO-Requests
(Backend beim Bearbeiten/Vorschau, echtes Frontend-Rendering) - kein `$this` verfügbar. Analog zu den
System-Elementen (siehe `elements/system/`) kommen alle vom Builder bereitgestellten Werte gebündelt
als EIN einziges Objekt `$_gb` an (Unterstrich-Präfix, reservierter Name) - keine losen
Einzelvariablen, damit Feldnamen wie `uid`/`values`/`gb` innerhalb der Datei frei für eigene Zwecke
bleiben.

| Eigenschaft    | Typ       | In           | Inhalt |
|---|---|---|---|
| `$_gb->values` | array     | input+output | Aktuell gespeicherte Werte (leeres Array bei neuem Element) - Schlüssel = `name`-Attribute aus `input.php` |
| `$_gb->uid`    | string    | input        | Pro Bearbeitungsvorgang eindeutige ID - **muss** als Präfix für jedes `id`-Attribut genutzt werden (Label-Zuordnung, CKEditor-Bindung, o.ä.), analog `$uid` bei fm_builder |
| `$_gb->addon`  | rex_addon | input+output | Eigene Addon-Instanz (`rex_addon::get('gridblock2')`) |

`output.php` darf frühzeitig mit `return;` abbrechen (kein `echo`), z. B. wenn ein Pflichtfeld leer
ist. Ein unbekannter/gelöschter Elementtyp im gespeicherten Baum wird beim Rendern einfach
übersprungen (kein Fehler).


## Ein-/Ausgabelogik und Feldnamen

Es gibt **keinen serverseitigen Save-Handler, der Feldnamen kennt** - der Builder liest die Werte
direkt aus dem DOM aus (`harvestElementValues()` in `gridblock_builder.js`) und legt sie 1:1 als
`values`-Objekt des Knotens ab. Daraus folgen dieselben Grundregeln wie bei fm_builder:

1. **`name`-Attribute sind einfache, flache Schlüssel** (`name="content"`, nicht
   `name="f[content]"`) - der Feldname in `input.php` muss exakt dem Array-Key entsprechen, den
   `output.php` später ausliest (`name="text"` ↔ `$_gb->values['text']`).
2. **`id`-Attribute müssen dokumentweit eindeutig sein** - immer mit `$_gb->uid` präfixen
   (`id="<?php echo $_gb->uid; ?>_text"`), insbesondere bei `<label for="...">` und CKEditor-Textareas.
3. **Checkbox-Konvention:** `value="checked"`, PHP-Vergleich per `(@$_gb->values['feld'] == 'checked')`,
   Vorbelegung direkt als Attribut: `<?php echo @$_gb->values['feld']; ?>`.
4. **CKEditor** wird unterstützt - `harvestElementValues()` ruft vor dem Auslesen automatisch
   `CKEDITOR.instances[id].updateElement()` auf (dieselbe, von fm_builder bekannte
   Absicherung gegen verzögerte WYSIWYG-Synchronisierung).
5. **REDAXO-native Widgets** (Medienauswahl, Linkliste) können über die vorhandenen
   `rex_var_*::getWidget()`-Helfer eingebunden werden, z. B.
   `rex_var_media::getWidget($_gb->uid.'_media', 'media', (string) @$_gb->values['media'], [])`.
6. **Nicht verfügbar:** die Modul-Platzhalter-Syntax `REX_MEDIA[...]`/`REX_LINK[...]` (nur für
   klassische Module, wird nie ausgewertet) - stattdessen direkte PHP-Äquivalente nutzen
   (`rex_media::get()`, `rex_getUrl()` usw.).


## Live-Vorschau

Custom-Elemente bekommen automatisch eine Shadow-DOM-Live-Vorschau (dieselbe Technik wie bei
Sektion/Spalten) - keine eigene Konfiguration nötig.


## Repeater/Multi-Link-Feld ohne Hard-Dependency auf "_fm_helper"

Für wiederholbare Zeilen (HTML-Repeater) bzw. ein Multi-Link-Feld (Telefon/E-Mail/URL/interner
Link/Medienpool-Datei/Sprunganker in einem Feld) NICHT direkt `\IcemanFx\FmHelper\fmHelper_repeater`/
`fmHelper_multilink` aufrufen, sondern immer über die Fassade:

```php
echo Gridblock2Builder_Tree::renderRepeater('cards', $v['cards'] ?? '', $rowMarkup);
echo Gridblock2Builder_Tree::renderMultilink('link', $v['link'] ?? '');
```

Die Fassade nutzt automatisch das echte `_fm_helper`-Addon, falls installiert UND dort das jeweilige
Werkzeug aktiviert ist - andernfalls GB2s eigene, vollständig unabhängige Vendor-Kopie
(`Gridblock2Builder_VendorRepeater`/`-VendorMultilink`, siehe deren Klassenkommentare). Dadurch bleibt
GB2 auf JEDER Installation voll funktionsfähig, auch ohne "_fm_helper". Passende CSS/JS wird ebenso
automatisch (in `boot.php`) entweder von "_fm_helper" oder aus GB2s eigenem `assets/vendor/fm_helper/`
geladen - für eigene Custom-Elemente ist dafür kein zusätzlicher Handgriff nötig.

Optional ein vierter Parameter mit Optionen. Mit `collapsible` bekommt jede Zeile eine Titelzeile zum Ein-/Ausklappen:

```php
echo Gridblock2Builder_Tree::renderRepeater('cards', $v['cards'] ?? '', $rowMarkup, [
	'collapsible' => true,
	'titleField' => 'titel',
	'collapsed' => true,
]);
```

- `titleField`: Feldname(n) der Zeile für den Titel (Komma-Liste, das erste Feld mit Wert gewinnt, z. B. `titel,untertitel`). Ohne Angabe: erstes Text-/Textarea-/Select-Feld mit Wert. Der Titel aktualisiert sich beim Tippen.
- `titleFallback`: Text, wenn kein Feld einen Wert liefert (Standard "Eintrag", ergibt "Eintrag 1", "Eintrag 2", ...).
- `collapsed`: bestehende Zeilen starten eingeklappt. Neue und duplizierte Zeilen sind immer offen; ein ungültiges Pflichtfeld klappt seine Zeile beim Absenden automatisch auf.
- `maxRows`: höchste Zeilenzahl (0 oder ohne Angabe = unbegrenzt). Ist die Grenze erreicht, sind "Hinzufügen" und "Duplizieren" gesperrt und ein Hinweis erscheint unter der Liste. Bereits vorhandene Zeilen bleiben erhalten. Die Ausgabe sollte trotzdem selbst begrenzen (z. B. `array_slice`), falls ältere Daten mehr Zeilen enthalten.
- `minRows`: kleinste Zeilenzahl (0 oder ohne Angabe = keine Vorgabe). Fehlende Zeilen werden beim Öffnen leer ergänzt, "Entfernen" ist gesperrt, solange nur so viele Zeilen vorhanden sind.

## Dark Mode in `style.css`

Die Vorschau bekommt im Backend automatisch die Klasse `gb2-be-dark` bzw. `gb2-be-light` (je nach REDAXO-Profil,
bei "automatisch" keine von beiden). `body.rex-theme-dark` ist innerhalb der Vorschau nicht erreichbar, daher gilt
dieselbe Zweiteilung wie bei den REDAXO-Backend-Styles:

```css
.gb2-be-dark .gb-el-zitat { background: #1d2935; color: #e6edf3; }

@media (prefers-color-scheme: dark) {
	.gb-el-zitat:not(.gb2-be-light .gb-el-zitat) { background: #1d2935; color: #e6edf3; }
}
```

Der erste Block gilt bei erzwungenem Dark-Profil, der zweite bei Betriebssystem-Dark (im Frontend und im Backend mit
Profil "automatisch"). Mit eigener dunkler Fläche bleibt das Element lesbar, auch wenn die Website selbst keinen
Dark Mode umsetzt.
