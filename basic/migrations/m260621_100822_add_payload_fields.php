<?php

use yii\db\Migration;

class m260621_100822_add_payload_fields extends Migration
{
    public function up()
    {
        $this->addColumn('submitted_flight_plan', 'pax_adults',    $this->smallInteger()->unsigned()->null());
        $this->addColumn('submitted_flight_plan', 'pax_children',  $this->smallInteger()->unsigned()->null());
        $this->addColumn('submitted_flight_plan', 'cargo_bags',    $this->smallInteger()->unsigned()->null());
        $this->addColumn('submitted_flight_plan', 'cargo_paid_kg', $this->integer()->unsigned()->null());

        $this->addColumn('flight', 'pax_adults',    $this->smallInteger()->unsigned()->null());
        $this->addColumn('flight', 'pax_children',  $this->smallInteger()->unsigned()->null());
        $this->addColumn('flight', 'cargo_bags',    $this->smallInteger()->unsigned()->null());
        $this->addColumn('flight', 'cargo_paid_kg', $this->integer()->unsigned()->null());
    }

    public function down()
    {
        $this->dropColumn('submitted_flight_plan', 'pax_adults');
        $this->dropColumn('submitted_flight_plan', 'pax_children');
        $this->dropColumn('submitted_flight_plan', 'cargo_bags');
        $this->dropColumn('submitted_flight_plan', 'cargo_paid_kg');

        $this->dropColumn('flight', 'pax_adults');
        $this->dropColumn('flight', 'pax_children');
        $this->dropColumn('flight', 'cargo_bags');
        $this->dropColumn('flight', 'cargo_paid_kg');
    }
}
