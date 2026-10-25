<?php

use yii\db\Migration;

/**
 * Creates the client tables: client (with mandatory headquarters airport) and client_hub.
 */
class m261021_180200_create_client_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Clients that request jobs; headquarters is a mandatory airport
        $this->createTable('client', [
            'id'                => $this->primaryKey()->unsigned(),
            'name'              => $this->string(100)->notNull(),
            'headquarters_icao' => $this->char(4)->notNull(),
            'created_at'        => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'        => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('client_name_unique', 'client', 'name', true);
        $this->createIndex('client_headquarters_FK', 'client', 'headquarters_icao');
        $this->addForeignKey('client_headquarters_FK', 'client', 'headquarters_icao', 'airport', 'icao_code', 'RESTRICT', 'CASCADE');

        // Secondary airports where the client operates
        $this->createTable('client_hub', [
            'client_id'    => $this->integer()->unsigned()->notNull(),
            'airport_icao' => $this->char(4)->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->addPrimaryKey('client_hub_PK', 'client_hub', ['client_id', 'airport_icao']);
        $this->createIndex('client_hub_airport_FK', 'client_hub', 'airport_icao');
        $this->addForeignKey('client_hub_client_FK', 'client_hub', 'client_id', 'client', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('client_hub_airport_FK', 'client_hub', 'airport_icao', 'airport', 'icao_code', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('client_hub');
        $this->dropTable('client');
    }
}
