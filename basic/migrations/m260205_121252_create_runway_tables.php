<?php

use yii\db\Migration;

/**
 * Creates the runway and runway_end tables for storing airport runway information.
 */
class m260205_121252_create_runway_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Runway: physical runway strip (shared properties for both ends)
        $this->createTable('runway', [
            'id' => $this->primaryKey()->unsigned(),
            'airport_icao' => $this->char(4)->notNull(),
            'designators' => $this->string(7)->notNull(), // "14L/32R", "09/27"
            'width_m' => $this->decimal(4, 1)->notNull(),
            'length_m' => $this->decimal(6, 1)->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('runway_unique', 'runway', ['airport_icao', 'designators'], true);
        $this->addForeignKey(
            'runway_airport_FK',
            'runway',
            'airport_icao',
            'airport',
            'icao_code',
            'CASCADE',
            'CASCADE'
        );

        // Runway end: each threshold of a runway with its specific properties
        $this->createTable('runway_end', [
            'id' => $this->primaryKey()->unsigned(),
            'runway_id' => $this->integer()->unsigned()->notNull(),
            'designator' => $this->string(3)->notNull(),
            'latitude' => $this->double()->notNull(),
            'longitude' => $this->double()->notNull(),
            'true_heading_deg' => $this->decimal(5, 2)->notNull(),
            'displaced_threshold_m' => $this->decimal(5, 1)->notNull()->defaultValue(0),
            'stopway_m' => $this->decimal(5, 1)->notNull()->defaultValue(0),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('runway_end_unique', 'runway_end', ['runway_id', 'designator'], true);
        $this->addForeignKey(
            'runway_end_runway_FK',
            'runway_end',
            'runway_id',
            'runway',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('runway_end');
        $this->dropTable('runway');
    }
}
