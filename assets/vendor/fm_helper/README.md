# Vendor-Kopie: FM-Helper-Werkzeuge

Diese Dateien (Datumswähler, MultiDevice-Input/-Select, Multi-Link, Repeater, Farbwähler) sind **ausgelieferte Kopien** aus dem Addon `_fm_helper`.

**Nicht direkt bearbeiten.** Die Quelle ist `_fm_helper/assets/<Werkzeug>/` (lesbare `.js`/`.css`). Änderungen werden dort gemacht und mit

```
node _fm_helper/build/fm-tools.js deploy
```

gebaut und verteilt (`check` prüft nur). Jede `.min`-Datei trägt dafür einen Kopf mit Version und Hash der Quelle.

Das zugehörige PHP und die Templates liegen als eigene Kopien in `lib/Gridblock2Builder_VendorMultilink.php`, `lib/Gridblock2Builder_VendorRepeater.php` und `lib/templates/{multilink,repeater}.php` (Gridblock² läuft auch ohne `_fm_helper`). Sie werden bei Änderungen an `fmHelper_multilink`/`fmHelper_repeater` von Hand gespiegelt; `check` meldet, wenn sie auseinanderlaufen.
