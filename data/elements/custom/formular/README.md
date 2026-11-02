# Custom-Element "Formular"

Anfrageformular mit frei wählbaren Feldern, mehreren Schritten und E-Mail-Versand. Die Ausgabe wird mit YForm
(4.x und 5.x) erzeugt, der Versand läuft über `rex_mailer` (Absenderadresse aus den PHPMailer-Einstellungen).
Es wird nichts in der Datenbank gespeichert und kein YForm-E-Mail-Template benötigt.

Diese Datei ist nur eine Hilfe für Admins und wird vom Addon nicht ausgewertet.


## Voraussetzungen

- Addon `yform` (Pflicht, steht deshalb in `element.yml` unter `requires`) und das PHPMailer-Addon (für den Versand).
- Optional `yform_spam_protection`: Ist das Addon aktiv, wird automatisch ein Honeypot-Feld eingebaut.
- Der Versand läuft über die PHPMailer-Einstellungen. Ist dort ein Umleitungsmodus aktiv, gehen auch die Mails dieses
  Formulars an die Test-Adresse.


## Dateien dieses Elements

| Datei | Aufgabe |
|---|---|
| `element.yml` | Name, Symbol, Beschreibung, `requires: yform` |
| `input.php` | Editor im Builder (Felderliste und Einstellungen) |
| `output.php` | Ausgabe, ruft nur `GbFormKit::render()` auf |
| `helpers.php` | Logik (Aufbau, Prüfung, E-Mail-Versand), wird von `input.php` und `output.php` eingebunden |
| `style.css` | Gestaltung der Backend-Vorschau (siehe Abschnitt Styling) |
| `README.md` | diese Hilfe |

Die Logik (Aufbau des Formulars, Prüfung, E-Mail-Versand, Platzhalter) steckt in `helpers.php`. Das Element ist damit
autark: Der Ordner lässt sich kopieren, und Anpassungen an der Logik bleiben bei Addon-Updates erhalten.


## Felderliste

Jede Zeile der Liste ist ein Feld. Die Zeilen lassen sich verschieben, kopieren, ausschalten (Auge) und einklappen. Die
Titelzeile zeigt die Bezeichnung des Feldes.

### Feldarten

| Art | Ergebnis |
|---|---|
| Textfeld, E-Mail, Telefon, Website | einzeilige Eingabe (E-Mail und Website werden zusätzlich auf das Format geprüft) |
| Mehrzeiliger Text | Textarea |
| Zahl | Zahlenfeld (Format wird geprüft) |
| Datum, Uhrzeit | Auswahl über den Browser |
| Auswahlliste | Select mit Leer-Option "Bitte wählen" |
| Einzelauswahl (Radio) | Radio-Buttons |
| Mehrfachauswahl (Checkboxen) | mehrere Checkboxen, mehrere Werte wählbar |
| Einzelne Checkbox | ein Haken, z. B. für die Datenschutz-Einwilligung |
| Datei-Upload (als E-Mail-Anhang) | eine Datei pro Feld, wird geprüft und als Anhang an die E-Mail gehängt (siehe Abschnitt Datei-Upload) |
| -- Infotext (kein Eingabefeld) -- | Hinweistext im Formular, kein Eingabefeld, nicht in der E-Mail |
| -- Neuer Schritt (kein Eingabefeld) -- | beginnt eine neue Seite (siehe Schritte) |

### Optionen je Zeile

| Feld | Bedeutung |
|---|---|
| Art | Pflichtangabe, bestimmt, welche weiteren Felder erscheinen |
| Pflichtfeld | Browser- und Server-Prüfung auf Eingabe |
| Bezeichnung | Label des Feldes. Beim Schritt der Titel, beim Infotext die fette Überschrift (optional) |
| Feldname | optional, für Platzhalter (siehe unten). Erlaubt sind Kleinbuchstaben, Ziffern und Unterstrich, der Name muss mit einem Buchstaben beginnen. Ohne eigenen Namen heißt das Feld `f1`, `f2` usw. in der Reihenfolge der Eingabefelder. Reserviert sind `send`, `submit` und `honeypot` |
| Auswahl | siehe unten |
| Platzhalter | Beispieltext im Feld. Bei der Auswahlliste der Text der Leer-Option |
| Minimum, Maximum, Schrittweite | nur bei Zahl, als Browser-Prüfung (`min`, `max`, `step`) |
| Min. Zeichen, Max. Zeichen | bei Textfeld, E-Mail, Telefon, Website und mehrzeiligem Text, als Browser-Prüfung (`minlength`, `maxlength`) |
| Erlaubte Dateitypen, Höchstgröße in MB | nur bei Datei-Upload (siehe unten) |
| Hilfetext | kleiner Text direkt unter dem Feld |

Serverseitig geprüft werden nur Pflichtfeld, E-Mail-Format, Website-Format und Zahlformat. Alles andere
(Minimum, Maximum, Schritt, Zeichenlänge) prüft nur der Browser.

### Datei-Upload

Die Feldart "Datei-Upload (als E-Mail-Anhang)" erzeugt ein Dateifeld (eine Datei pro Feld, mehrere Upload-Felder sind möglich).

- **Nur als Anhang:** Die Datei wird nicht auf dem Server gespeichert, nicht in der Datenbank abgelegt und nicht im
  Medienpool angelegt. Sie hängt als Anhang an der E-Mail an den Empfänger (nicht an der Bestätigungsmail). Nach dem
  Request löscht PHP die temporäre Datei von selbst. In der E-Mail-Liste und über `{feldname}` erscheint der Dateiname mit Größe.
- **Erlaubte Dateitypen:** Liste mit Komma, Voreinstellung `pdf, jpg, jpeg, png`. Möglich sind nur: pdf, jpg, jpeg, png, gif, webp,
  txt, csv, doc, docx, xls, xlsx, ppt, pptx, odt, ods. Andere Endungen im Feld werden ignoriert, Ausführbares oder Archive
  lassen sich nicht freischalten. Der Browser bekommt die Liste als `accept`, der Server prüft Endung **und** echten Dateityp (finfo).
- **Höchstgröße:** in MB (Voreinstellung 5, höchstens 25; beim Wechsel der Art auf "Datei-Upload" wird 5 vorbelegt, ein leeres Feld gilt ebenfalls als 5). Es gilt nie mehr, als PHP auf dem Server erlaubt
  (`upload_max_filesize` und `post_max_size`). Der Hilfetext des Feldes (bei neuen Upload-Feldern mit "Erlaubt: {types}, höchstens {max}." vorbelegt) kann die Platzhalter `{types}` (Liste der Dateitypen) und `{max}` (Höchstgröße) enthalten. Ohne Hilfetext erscheint kein Hinweis unter dem Feld.
- **Pflichtfeld:** Browser- und Server-Prüfung.
- **Fehler:** Bei einer ungeeigneten Datei bleibt das Formular stehen und nennt den Grund. Die Datei muss dann erneut
  gewählt werden (Browser-Einschränkung bei Dateifeldern). Ist die Datei größer als `post_max_size`, verwirft PHP die gesamte
  Anfrage, dann erscheint die Meldung "Das Formular ist abgelaufen".
- **Dateiname in der Mail:** wird bereinigt (nur Buchstaben, Ziffern, Unterstrich, Bindestrich).
- **Mehrere Schritte:** funktioniert, das Formular wird in einem Rutsch abgeschickt.
- **Backend-Vorschau:** zeigt das Dateifeld, abschicken ist dort nicht möglich.
- **Hinweis Datenschutz:** Dateien können personenbezogene Daten enthalten. Das Formular selbst speichert nichts, der
  Mailserver und das Postfach des Empfängers aber schon. Gegebenenfalls in der Datenschutzerklärung erwähnen.

### Meldungen anpassen

Alle Meldungen des Formulars (Pflichtfeld, ungültiger Wert, Spam, abgelaufenes Formular, Versandfehler und die Upload-Meldungen)
stehen in der `output.php` dieses Elements als auskommentierte Liste `$messages`. Zum Ändern den Eintrag einkommentieren und den
Text anpassen, fehlende Einträge bleiben Standard. Platzhalter: `{label}` (Bezeichnung des Feldes), `{max}` (z. B. 5 MB),
`{types}` (erlaubte Dateitypen), `{ext}` (Endung der Datei). Der Text nach dem Absenden und die Bestätigungsmail stehen im Editor
unter "Button & Bestätigung" bzw. "E-Mail-Versand".

### Das Feld "Auswahl"

Je nach Art hat dieses Feld eine andere Aufgabe.

- **Auswahlliste, Radio, Checkboxen:** eine Option pro Zeile. Eine einzelne Textangabe ist zugleich Anzeigetext und Wert.
  Mit eigenem Wert: `Anzeigetext|Wert` (Trennzeichen senkrechter Strich). Der Anzeigetext erscheint im Formular und in
  der E-Mail-Liste, der Wert ist über `{feldname}` abrufbar. Leere und doppelte Zeilen werden ignoriert.
- **Einzelne Checkbox:** hier steht der Wert, der in der E-Mail erscheint, wenn die Box angehakt ist (nur die erste
  Zeile zählt, Standard "Ja"). Die Bezeichnung (z. B. der lange Datenschutz-Satz) steht im Formular.
- **Infotext:** der Text unter der Überschrift. Zeilenumbrüche bleiben erhalten. Besteht der Text nur aus Leerzeilen
  und gibt es keine Überschrift, wird daraus ein reiner Abstand (jede Leerzeile eine Zeile Höhe).


## Schritte

Ein Feld der Art "Neuer Schritt" beginnt eine neue Seite des Formulars. Der Titel der ersten Seite steht unter
"Sonstiges" ("Titel des ersten Schritts", Standard "Schritt 1").

Im Frontend zeigt ein kleines Skript die Seiten nacheinander mit "Zurück" und "Weiter", dazu eine Fortschrittsanzeige.
Vor dem Weiterklicken werden die Pflichtfelder der Seite geprüft. Gibt es nach dem Absenden einen Serverfehler, springt
das Formular zu der Seite mit dem Fehler. Ohne JavaScript stehen alle Seiten untereinander.

In der Backend-Vorschau laufen die Schritte nur in iframe-Vorschauen (Slice-Übersicht, Element-Vorschau im iframe-Modus).
Die Shadow-DOM-Vorschau im Builder kann kein JavaScript und zeigt alle Schritte untereinander.


## E-Mail-Versand (Block im Editor)

| Feld | Bedeutung |
|---|---|
| E-Mail an | Empfänger, mehrere mit Komma. Ungültige Adressen werden ignoriert |
| Betreff der E-Mail | leer = "Neue Formularanfrage (Servername)" |
| Text der E-Mail an den Empfänger | leer = Liste aller Angaben mit Datum und Seite. Neue Formulare sind mit `{all-fields}` vorbelegt |
| Absendername | optional. Leer = Name aus den PHPMailer-Einstellungen. Die Absenderadresse kommt immer von dort |
| Bestätigungsmail an den Absender | Haken, schaltet die Felder darunter frei |
| Empfänger der Bestätigung | optional. Leer = erstes Feld der Art E-Mail mit gültiger Adresse. Sonst der Feldplatzhalter, z. B. `{email}` |
| Betreff / Text der Bestätigung | frei, mit Platzhaltern |

Die Admin-Mail trägt die Adresse des Absenders als Antwortadresse (Reply-To), sofern sie vorhanden und gültig ist. Die
Bestätigung geht nur raus, wenn eine gültige Adresse da ist. Fehlt sie, geht die Admin-Mail trotzdem raus und der
Besucher bekommt keinen Hinweis darauf.

### Platzhalter

Gelten in Betreff, Text und Bestätigung. Unbekannte Platzhalter bleiben unverändert im Text stehen.

| Platzhalter | Inhalt |
|---|---|
| `{feldname}` | Wert des Feldes. Bei Auswahlfeldern der Wert, bei der einzelnen Checkbox der Wert aus "Auswahl" |
| `{feldname_label}` | bei Auswahlfeldern der Anzeigetext |
| `{all-fields}` | Liste aller Angaben (Bezeichnung: Wert), Schritte als Zwischenüberschriften |
| `{site}` | URL der Seite mit dem Formular |
| `{date}` | Datum und Uhrzeit des Absendens |


## Button, Weiterleitung, Sonstiges

- **Beschriftung des Buttons:** Standard "Absenden".
- **Text nach dem Absenden:** Meldung nach erfolgreichem Versand. Neue Formulare sind vorbelegt.
- **Weiterleitung nach dem Absenden:** optional, interner Artikel oder URL. Sie ersetzt den Text.
- **Hinweis unter dem Formular:** kleiner Text unter dem Button.
- Schlägt der Versand fehl, bleibt das Formular stehen und zeigt eine Fehlermeldung.


## Styling der Ausgabe

Im Frontend gilt primär das Projekt-CSS. Das Element bringt im Frontend kein eigenes CSS mit. Das Markup besteht aus dem
YForm-Standardmarkup (Theme "bootstrap" der YForm-Einstellungen) und diesen Klassen:

| Klasse | Element |
|---|---|
| `.gb-el-form` | Umschlag um das ganze Formular |
| `.gb-el-form-fieldset` mit `legend` | Gruppe aus Radio-Buttons oder Checkboxen mit ihrer Überschrift |
| `.gb-el-form-step` mit `.gb-el-form-step-title` | eine Seite (Fieldset) und ihr Titel |
| `.gb-el-form-progress` | Fortschrittsanzeige ("Schritt 1 von 2: …") |
| `.gb-el-form-nav`, `.gb-el-form-prev`, `.gb-el-form-next` | Zeile mit den Buttons Zurück und Weiter |
| `.gb-el-form-info` mit `.gb-el-form-info-title`, `.gb-el-form-info-text` | Infotext |
| `.gb-el-form-spacer` (zusätzlich auf `.gb-el-form-info`) | reiner Abstand |
| `.gb-el-form-note` | Hinweis unter dem Formular |
| `.gb-el-form-success` | Meldung nach erfolgreichem Absenden, ein normaler Absatz (`<p>`) ohne eigene Gestaltung |
| `.gb-el-form-error` | Fehlermeldung nach dem Absenden |

### style.css dieses Elements

Die `style.css` neben dieser Datei wirkt in der Backend-Vorschau. Im Frontend wirkt sie nur, wenn in den Einstellungen
"Element-CSS im Frontend" eingeschaltet ist (dann wird die `style.css` aller Custom-Elemente gesammelt eingebunden) oder
wenn das Template die URL aus `Gridblock2Builder_Tree::getElementsCssUrl()` selbst einbindet. Die Datei enthält schlichte
Standardregeln, die sich vor dem Einsatz beliebig kürzen oder ersetzen lassen. Sie ist bewusst nicht Teil der Ausgabe.

Die Kopie im Datenordner der Installation (`redaxo/data/addons/gridblock2/elements/custom/formular/`) ist die gültige.
Änderungen an der mitgelieferten Kopie im Addon-Ordner wirken sich erst bei einer neuen Installation aus.

### Wrapper-Klasse der Vorschau

Begrenzt das Theme seine Regeln auf einen Container, z. B. `[class^="content"]`, sieht die Backend-Vorschau
(Slice-Übersicht und Builder) das Formular ungestylt. In den Einstellungen unter "Builder-Vorschau (CSS & JavaScripte)"
trägt man dazu die Klasse ein, z. B. `content`. Sie wird in der Vorschau der vorhandenen Hülle `.gb2-preview-content`
vorangestellt. Je Domain kann sie bei Multisite abweichen.


## Hinweise

- **E-Mail-Adressen in Platzhaltern:** Ein E-Mail-Verschleierer im Frontend kann ein Beispiel wie `name@example.com` im
  Platzhalter eines Feldes verändern. Für Platzhalter besser ohne @ formulieren.
- **Datenschutz-Checkbox:** nie vorausgewählt. Es gibt bewusst keine Vorauswahl.
- **Vorschau im Builder:** Das Formular lässt sich dort ausfüllen, aber nicht absenden.
- **Mehrere Formulare auf einer Seite:** Jedes Formular bekommt eine Kennung aus seinem Inhalt, verschiedene Formulare
  stören sich nicht. Zwei komplett identische Formulare auf derselben Seite sollte man vermeiden.
- **Spam-Schutz:** Mit `yform_spam_protection` greifen dessen Zeitprüfung und Sperren. Wird das Formular zu schnell
  nach dem Laden abgeschickt, kann es abgelehnt werden.
