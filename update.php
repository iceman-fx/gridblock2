<?php
/*
	Redaxo-Addon Gridblock²
	Updateprozeduren
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** RexStan: Vars vom Check ausschließen */
/** @var rex_addon $this */


//Variablen deklarieren
$mypage = $this->getProperty('package');
$error = "";


//Datenbank-Spalten anlegen, sofern noch nicht verfügbar
?>