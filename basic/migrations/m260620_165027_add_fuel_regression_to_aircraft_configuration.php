<?php

use yii\db\Migration;

class m260620_165027_add_fuel_regression_to_aircraft_configuration extends Migration
{
    public function safeUp()
    {
        $this->addColumn('aircraft_configuration', 'fuel_regression_a', $this->decimal(10, 2)->null()->after('bew'));
        $this->addColumn('aircraft_configuration', 'fuel_regression_b', $this->decimal(10, 4)->null()->after('fuel_regression_a'));
        $this->addColumn('aircraft_configuration', 'fuel_regression_n', $this->integer()->unsigned()->null()->after('fuel_regression_b'));
        $this->addColumn('aircraft_configuration', 'fuel_avg_kg_per_min', $this->decimal(10, 4)->null()->after('fuel_regression_n'));
        $this->addColumn('aircraft_configuration', 'fuel_regression_updated_at', $this->dateTime()->null()->after('fuel_avg_kg_per_min'));
    }

    public function safeDown()
    {
        $this->dropColumn('aircraft_configuration', 'fuel_regression_updated_at');
        $this->dropColumn('aircraft_configuration', 'fuel_avg_kg_per_min');
        $this->dropColumn('aircraft_configuration', 'fuel_regression_n');
        $this->dropColumn('aircraft_configuration', 'fuel_regression_b');
        $this->dropColumn('aircraft_configuration', 'fuel_regression_a');
    }
}
