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
rex_sql_table::get(rex::getTable('gridblock2_presets'))
	->ensureColumn(new rex_sql_column('id', 'int(11)', false, null, 'auto_increment'))
	->ensureColumn(new rex_sql_column('title', 'varchar(255)'))
	->ensureColumn(new rex_sql_column('kind', 'varchar(20)'))
	->ensureColumn(new rex_sql_column('payload', 'mediumtext'))
	->ensureColumn(new rex_sql_column('summary', 'varchar(255)', true))
	->ensureColumn(new rex_sql_column('domain_id', 'int(11)', false, '0'))
	->ensureColumn(new rex_sql_column('private', 'tinyint(1)', false, '0'))
	->ensureColumn(new rex_sql_column('created_by', 'int(11)', false, '0'))
	->ensureColumn(new rex_sql_column('used_count', 'int(11)', false, '0'))
	->ensureColumn(new rex_sql_column('last_used', 'datetime', true))
	->ensureGlobalColumns()
	->setPrimaryKey('id')
	->ensure();

?>