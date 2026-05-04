<?php

use yii\db\Migration;

/**
 * Creates the aircraft_type_resource table for storing documentation and configuration files per aircraft type.
 */
class m260501_111317_create_aircraft_type_resource extends Migration
{
    public function safeUp()
    {
        $this->createTable('aircraft_type_resource', [
            'id'               => $this->primaryKey()->unsigned(),
            'aircraft_type_id' => $this->integer()->unsigned()->notNull(),
            'filename'         => $this->string(255)->notNull(),
            'original_name'    => $this->string(255)->notNull(),
            'size_bytes'       => $this->bigInteger()->unsigned()->notNull(),
            'created_at'       => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('atr_aircraft_type_id_idx', 'aircraft_type_resource', 'aircraft_type_id');

        $this->addForeignKey(
            'atr_aircraft_type_FK',
            'aircraft_type_resource', 'aircraft_type_id',
            'aircraft_type', 'id',
            'CASCADE', 'CASCADE'
        );

        $this->insert('config', ['key' => \app\config\ConfigHelper::AIRCRAFT_TYPE_RESOURCES_STORAGE_PATH, 'value' => '/opt/mam/aircraftTypeResources']);
        $this->insert('config', ['key' => \app\config\ConfigHelper::AIRCRAFT_TYPE_RESOURCES_LIMIT_MB,     'value' => '10240']);
    }

    public function safeDown()
    {
        $this->delete('config', ['key' => \app\config\ConfigHelper::AIRCRAFT_TYPE_RESOURCES_STORAGE_PATH]);
        $this->delete('config', ['key' => \app\config\ConfigHelper::AIRCRAFT_TYPE_RESOURCES_LIMIT_MB]);

        $this->dropTable('aircraft_type_resource');
    }
}
