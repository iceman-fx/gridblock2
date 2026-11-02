<?php
/*
	Redaxo-Addon Gridblock²-Modul
	Ausgabe
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** @var stdClass $_gb */

require_once __DIR__.'/helpers.php';

//Meldungen des Formulars. Zum Anpassen den gewuenschten Eintrag einkommentieren und den Text aendern (Rest bleibt Standard).
//Platzhalter: {label} = Bezeichnung des Feldes, {max} = Groesse (z. B. 5 MB), {types} = erlaubte Dateitypen, {ext} = Endung.
$messages = [
	//'csrf_expired' => 'Das Formular ist abgelaufen. Bitte die Seite neu laden und erneut absenden.',
	//'spam_label' => 'Bitte nicht ausfüllen',
	//'spam_error' => 'Ihre Anfrage wurde als Spam erkannt.',
	//'select_empty' => 'Bitte wählen',
	//'required' => 'Bitte „{label}“ ausfüllen.',
	//'invalid' => 'Bitte bei „{label}“ einen gültigen Wert angeben.',
	//'send_failed' => 'Das Formular konnte leider nicht versendet werden. Bitte versuchen Sie es später erneut.',
	//'file_intro' => 'Die Datei konnte nicht übernommen werden. Bitte wählen Sie sie erneut aus:',
	//'file_required' => 'Bitte bei „{label}“ eine Datei auswählen.',
	//'file_too_big' => '„{label}“: Die Datei ist zu groß (höchstens {max}).',
	//'file_failed' => '„{label}“: Der Upload ist fehlgeschlagen.',
	//'file_empty' => '„{label}“: Die Datei ist leer.',
	//'file_type' => '„{label}“: Dieser Dateityp ist nicht erlaubt (erlaubt: {types}).',
	//'file_mismatch' => '„{label}“: Der Inhalt der Datei passt nicht zur Endung .{ext}.',
];

echo GbFormKit::render((array) ($_gb->values ?? []), rex_gridblock2::isBuilderPreview(), $messages);
