<?php

use yii\db\Migration;

class m260205_095523_change_icao_type_code_to_varchar extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('aircraft_type', 'icao_type_code', $this->string(4)->notNull());
    }

    public function safeDown()
    {
        $this->alterColumn('aircraft_type', 'icao_type_code', 'CHAR(4) NOT NULL');
    }
}
