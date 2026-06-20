<?php

use yii\db\Migration;

class m260619_175732_add_crew_mtow_bew_to_aircraft_configuration extends Migration
{
    public function up()
    {
        $this->addColumn('aircraft_configuration', 'crew',
            $this->tinyInteger(3)->unsigned()->notNull()->after('cargo_capacity'));
        $this->addColumn('aircraft_configuration', 'mtow',
            $this->integer(10)->unsigned()->notNull()->after('crew'));
        $this->addColumn('aircraft_configuration', 'bew',
            $this->integer(10)->unsigned()->notNull()->after('mtow'));
    }

    public function down()
    {
        $this->dropColumn('aircraft_configuration', 'crew');
        $this->dropColumn('aircraft_configuration', 'mtow');
        $this->dropColumn('aircraft_configuration', 'bew');
    }
}
