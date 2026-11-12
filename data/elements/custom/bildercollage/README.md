# Custom-Element "Bildercollage"

Bilder frei auf einer Fläche anordnen (bis zu 10), mit Format, Eckenrundung, Schatten, Z-Index, Parallax und Lightbox.
Umsetzung des bisherigen Spezial-Moduls "Bildercollage" als Custom-Element. Frontend-Klassen (`icGalerie`, `icGalerie-item`, ...) und
Frontend-Skript bleiben wie beim Modul. Projektspezifische Optionen (Blockbreite, Breite begrenzen, CSS-Klasse, Sichtbarkeit, Ausrichtung) gehören nicht zum Element.

## Dateien

| Datei | Zweck |
|---|---|
| `input.php` | Editor (Bilder auswählen, per Ziehen oder Pfeiltasten anordnen, Einstellungen je Bild) und Optionen |
| `output.php` | Frontend-Ausgabe und Backend-Vorschau |
| `helpers.php` | Klasse `GbCollageKit` (Auswahllisten, Prüfung der gespeicherten Werte, Medien-URLs) |
| `style.css` | CSS der Collage für die Backend-Live-Vorschau (ohne Hover-Overlay mit Pluszeichen) |

## Felder (Schlüssel in `values`)

| Schlüssel | Inhalt |
|---|---|
| `collage` | JSON `{"images":[{file, x, y, width, z, format, radius, shadow, parallax}]}` (x/y/Breite in Prozent der Breite, wie beim Modul) |
| `mouseover` | Maus-Over-Effekt (`icG-hover-grey`, `-color`, `-blur`, `-scaleup`) |
| `s_greytone` | Haken: Bilder ausgrauen (`icG-greytone`) |
| `s_nolinks` | Haken: keine Lightbox (`icG-nolinks`) |

Eckenrundung je Bild: `keine`, `abgerundet` (15 px), `pille` (volle Rundung), `rund` (50 %). Die Klassen `icGalerie-r-<Wert>` sind in der `style.css` definiert, das Element braucht keine Variablen aus dem Projekt.

Alt-Text: Medienfeld `med_alt`, ist es leer, der Titel des Mediums. Der Haken `med_decorative` am Medium erzwingt `alt=""` (wie beim Hero und den Karten); gibt es die Felder nicht, gilt der Titel. Titel, Beschreibung und Copyright stehen außerdem als `data-photoswipe-*`-Attribute am Lightbox-Link.

## Verhalten

- **Frontend:** Die Positionen werden wie beim Modul per JavaScript berechnet (Höhe des Containers, Parallax). Das Skript wird je Seite nur einmal ausgegeben. Bei `prefers-reduced-motion: reduce` ist Parallax aus.
- **Backend-Vorschau:** Statische Darstellung ohne JavaScript (Seitenverhältnis des Containers per CSS), ohne Lightbox und ohne Parallax.
- **Medientypen:** Es werden `web_600w`, `web_900w`, `web_1200w`, `web_1600w` und `web_2000w` genutzt. Fehlt ein Typ im Media Manager, wird er ausgelassen (bzw. das Original verwendet).
- **Hintergrundbild-Div:** `$outputBGImage` am Anfang der `output.php` gibt zusätzlich ein `<div style="background-image: ...">` je Bild aus (wie beim Modul). Auf `false` setzen, wenn das Projekt es nicht braucht (spart eine Bildanfrage je Bild).
