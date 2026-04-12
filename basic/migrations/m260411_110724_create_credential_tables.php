<?php

use yii\db\Migration;

/**
 * Creates the credential system tables: credential_type, credential_type_prerequisite,
 * credential_type_aircraft_type, credential_type_airport_aircraft, and pilot_credential.
 */
class m260411_110724_create_credential_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Catalog of credential types (licenses, ratings, certifications)
        $this->createTable('credential_type', [
            'id'            => $this->primaryKey()->unsigned(),
            'code'          => $this->string(30)->notNull(),
            'name'          => $this->string(100)->notNull(),
            'type'          => $this->tinyInteger()->unsigned()->notNull(), // 1=license, 2=rating, 3=certification
            'description'   => $this->text()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('credential_type_code_unique', 'credential_type', 'code', true);

        // DAG edges: parent -> child prerequisite relationships
        // Semantics: to obtain child_id, pilot needs at least ONE of the parent_id entries (OR logic)
        $this->createTable('credential_type_prerequisite', [
            'parent_id' => $this->integer()->unsigned()->notNull(),
            'child_id'  => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->addPrimaryKey('ctp_PK', 'credential_type_prerequisite', ['parent_id', 'child_id']);
        $this->createIndex('ctp_child_FK', 'credential_type_prerequisite', 'child_id');
        $this->addForeignKey('ctp_parent_FK', 'credential_type_prerequisite', 'parent_id', 'credential_type', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('ctp_child_FK', 'credential_type_prerequisite', 'child_id', 'credential_type', 'id', 'CASCADE', 'CASCADE');

        // Aircraft types a credential unlocks for flying
        $this->createTable('credential_type_aircraft_type', [
            'credential_type_id' => $this->integer()->unsigned()->notNull(),
            'aircraft_type_id'   => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->addPrimaryKey('ctat_PK', 'credential_type_aircraft_type', ['credential_type_id', 'aircraft_type_id']);
        $this->createIndex('ctat_aircraft_type_FK', 'credential_type_aircraft_type', 'aircraft_type_id');
        $this->addForeignKey('ctat_credential_type_FK', 'credential_type_aircraft_type', 'credential_type_id', 'credential_type', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('ctat_aircraft_type_FK', 'credential_type_aircraft_type', 'aircraft_type_id', 'aircraft_type', 'id', 'CASCADE', 'CASCADE');

        // Airport-specific unlocks: a certification grants flying aircraft_type_id to airport_icao
        // If no row exists for a (aircraft_type, airport) pair, there is no restriction
        $this->createTable('credential_type_airport_aircraft', [
            'credential_type_id' => $this->integer()->unsigned()->notNull(),
            'aircraft_type_id'   => $this->integer()->unsigned()->notNull(),
            'airport_icao'       => $this->char(4)->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->addPrimaryKey('ctaa_PK', 'credential_type_airport_aircraft', ['credential_type_id', 'aircraft_type_id', 'airport_icao']);
        $this->createIndex('ctaa_aircraft_type_FK', 'credential_type_airport_aircraft', 'aircraft_type_id');
        $this->createIndex('ctaa_airport_FK', 'credential_type_airport_aircraft', 'airport_icao');
        $this->addForeignKey('ctaa_credential_type_FK', 'credential_type_airport_aircraft', 'credential_type_id', 'credential_type', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('ctaa_aircraft_type_FK', 'credential_type_airport_aircraft', 'aircraft_type_id', 'aircraft_type', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('ctaa_airport_FK', 'credential_type_airport_aircraft', 'airport_icao', 'airport', 'icao_code', 'CASCADE', 'CASCADE');

        // Pilot credentials with full history (superseded_at IS NULL = current record)
        $this->createTable('pilot_credential', [
            'id'                 => $this->primaryKey()->unsigned(),
            'pilot_id'           => $this->integer()->unsigned()->notNull(),
            'credential_type_id' => $this->integer()->unsigned()->notNull(),
            'status'             => $this->tinyInteger()->unsigned()->notNull()->defaultValue(1), // 1=student, 2=active
            'issued_date'        => $this->date()->notNull(),
            'expiry_date'        => $this->date()->null(),
            'superseded_at'      => $this->dateTime()->null(),
            'created_at'         => $this->timestamp()->notNull()->defaultExpression('current_timestamp()'),
            'notes'              => $this->string(255)->null(),
            'issued_by'          => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('pc_current_lookup', 'pilot_credential', ['pilot_id', 'credential_type_id', 'superseded_at']);
        $this->createIndex('pc_credential_type_FK', 'pilot_credential', 'credential_type_id');
        $this->createIndex('pc_issued_by_FK', 'pilot_credential', 'issued_by');
        $this->addForeignKey('pc_pilot_FK', 'pilot_credential', 'pilot_id', 'pilot', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('pc_credential_type_FK', 'pilot_credential', 'credential_type_id', 'credential_type', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('pc_issued_by_FK', 'pilot_credential', 'issued_by', 'pilot', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('pilot_credential');
        $this->dropTable('credential_type_airport_aircraft');
        $this->dropTable('credential_type_aircraft_type');
        $this->dropTable('credential_type_prerequisite');
        $this->dropTable('credential_type');
    }
}
