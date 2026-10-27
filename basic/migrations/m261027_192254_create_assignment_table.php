<?php

use yii\db\Migration;

/**
 * Creates the assignment table (client jobs offered to a pilot with their last flown aircraft) and links it to
 * submitted flight plans; flights keep the client of the flown assignment.
 */
class m261027_192254_create_assignment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Assignments offered to a pilot, to be flown with the given aircraft from its current airport
        $this->createTable('assignment', [
            'id'          => $this->bigPrimaryKey()->unsigned(),
            'pilot_id'    => $this->integer()->unsigned()->notNull(),
            'aircraft_id' => $this->integer()->unsigned()->notNull(),
            'client_id'   => $this->integer()->unsigned()->notNull(),
            'departure'   => $this->char(4)->notNull(),
            'arrival'     => $this->char(4)->notNull(),
            'distance_nm' => $this->integer()->unsigned()->notNull(),
            'created_at'  => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('assignment_pilot_departure_idx', 'assignment', ['pilot_id', 'departure']);
        $this->createIndex('assignment_aircraft_FK', 'assignment', 'aircraft_id');
        $this->createIndex('assignment_client_FK', 'assignment', 'client_id');
        $this->createIndex('assignment_departure_FK', 'assignment', 'departure');
        $this->createIndex('assignment_arrival_FK', 'assignment', 'arrival');
        $this->addForeignKey('assignment_pilot_FK', 'assignment', 'pilot_id', 'pilot', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('assignment_aircraft_FK', 'assignment', 'aircraft_id', 'aircraft', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('assignment_client_FK', 'assignment', 'client_id', 'client', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('assignment_departure_FK', 'assignment', 'departure', 'airport', 'icao_code', 'CASCADE', 'CASCADE');
        $this->addForeignKey('assignment_arrival_FK', 'assignment', 'arrival', 'airport', 'icao_code', 'CASCADE', 'CASCADE');

        // A booked assignment can't be deleted while its flight plan exists
        $this->addColumn('submitted_flight_plan', 'assignment_id', $this->bigInteger()->unsigned()->null());
        $this->createIndex('submitted_flightplan_unique_assignment_id', 'submitted_flight_plan', 'assignment_id', true);
        $this->addForeignKey('submitted_flightplans_assignment_FK', 'submitted_flight_plan', 'assignment_id', 'assignment', 'id', 'RESTRICT', 'CASCADE');

        // Flights keep the client of the flown assignment; clients with flights can't be deleted
        $this->addColumn('flight', 'client_id', $this->integer()->unsigned()->null());
        $this->createIndex('flight_client_FK', 'flight', 'client_id');
        $this->addForeignKey('flight_client_FK', 'flight', 'client_id', 'client', 'id', 'RESTRICT', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('flight_client_FK', 'flight');
        $this->dropIndex('flight_client_FK', 'flight');
        $this->dropColumn('flight', 'client_id');

        $this->dropForeignKey('submitted_flightplans_assignment_FK', 'submitted_flight_plan');
        $this->dropIndex('submitted_flightplan_unique_assignment_id', 'submitted_flight_plan');
        $this->dropColumn('submitted_flight_plan', 'assignment_id');

        $this->dropTable('assignment');
    }
}
