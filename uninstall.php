<?php
/*
	Redaxo-Addon Gridblock²
	DeInstallation
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

/** RexStan: Vars vom Check ausschließen */
/** @var rex_addon $this */


//Variablen deklarieren
$mypage = $this->getProperty('package');
$error = ""; $notice = "";


//Datenbank-Einträge löschen
$db = rex_sql::factory();
$db->setQuery("DROP TABLE IF EXISTS ".rex::getTable('2034_gridtemplates'));
?>