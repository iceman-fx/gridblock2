<?php
/*
	Redaxo-Addon Gridblock²
	Grid SQL-Erweiterung
	v1.0
	by Falko Müller @ 2026 (based gridblock 1.x)
*/

class rex_sql_gridblock2 extends rex_sql {

    private $fakeResult = false;
    private $fakeStrict = false;


    public function __construct(int $DBID = 1)
    {
        if (rex::hasProperty('sql_fake_result')):
            $this->fakeResult = rex::getProperty('sql_fake_result');
            $this->fakeStrict = (bool) rex::getProperty('sql_fake_strict', false);
        endif;

        parent::__construct($DBID);
    }


    public function getValue($colName)
    {
        if ($this->fakeResult):
			//normale SQL-Abfrage des Feldes umgehen, sofern sql_fake_result gesetzt ist
            if (isset($this->fakeResult[$colName])):
				//gib den GB-gespeicherten Wert zurück, anstatt den aus der originalen SQL-Abfrage
                if (is_array($this->fakeResult[$colName])):
                    return json_encode($this->fakeResult[$colName]);
                endif;

                return $this->fakeResult[$colName];
			endif;

            if ($this->fakeStrict && preg_match('/^(value|media|medialist|link|linklist)[0-9]+$/', (string) $colName)):
                return '';
            endif;
        endif;

        return parent::getValue($colName);
    }


}
?>
