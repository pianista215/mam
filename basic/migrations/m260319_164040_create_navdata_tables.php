<?php

use yii\db\Migration;

/**
 * Creates nav_point, navaid and airway_segment tables for storing navdata
 * (fixes, NDBs, VORs, DMEs, ILS components and airways).
 */
class m260319_164040_create_navdata_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // nav_point: a geographic point used in navigation (fix, navaid location, etc.)
        $this->createTable('nav_point', [
            'id'         => $this->primaryKey()->unsigned(),
            'latitude'   => $this->double()->notNull(),
            'longitude'  => $this->double()->notNull(),
            'identifier' => $this->string(10)->notNull(),
            'name'       => $this->string(60)->notNull(),
            'point_type' => "enum('FIX','NDB','VOR','DME','ILS-LOC','LOC','GS','OM','MM','IM') NOT NULL",
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('nav_point_identifier_type', 'nav_point', ['identifier', 'point_type']);
        $this->createIndex('nav_point_location', 'nav_point', ['latitude', 'longitude']);

        // navaid: frequency and operational data for radio navigation aids
        $this->createTable('navaid', [
            'id'                => $this->primaryKey()->unsigned(),
            'nav_point_id'      => $this->integer()->unsigned()->notNull(),
            'frequency'         => $this->integer()->unsigned()->notNull()
                                     ->comment('kHz for NDB; MHz*100 for VOR/ILS/DME (e.g. 11680 = 116.80 MHz)'),
            'range_nm'          => $this->smallInteger()->unsigned()->null()->defaultValue(null),
            'true_bearing_deg'  => 'decimal(6,3) DEFAULT NULL',
            'glideslope_deg'    => 'decimal(4,2) DEFAULT NULL',
            'airport_icao'      => $this->char(4)->null()->defaultValue(null),
            'runway_designator' => $this->string(6)->null()->defaultValue(null),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('navaid_nav_point_FK', 'navaid', 'nav_point_id');
        $this->createIndex('navaid_airport', 'navaid', 'airport_icao');
        $this->addForeignKey(
            'navaid_nav_point_FK',
            'navaid',
            'nav_point_id',
            'nav_point',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'navaid_airport_FK',
            'navaid',
            'airport_icao',
            'airport',
            'icao_code',
            'SET NULL',
            'CASCADE'
        );

        // airway_segment: directed segment between two nav points belonging to one or more airways
        $this->createTable('airway_segment', [
            'id'                => $this->primaryKey()->unsigned(),
            'from_nav_point_id' => $this->integer()->unsigned()->notNull(),
            'to_nav_point_id'   => $this->integer()->unsigned()->notNull(),
            'direction'         => "enum('BOTH','FORWARD') NOT NULL DEFAULT 'BOTH'",
            'airway_type'       => "enum('LOW','HIGH') NOT NULL",
            'base_alt_ft'       => $this->integer()->unsigned()->notNull(),
            'top_alt_ft'        => $this->integer()->unsigned()->notNull(),
            'airway_names'      => $this->string(100)->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('airway_segment_from_FK', 'airway_segment', 'from_nav_point_id');
        $this->createIndex('airway_segment_to_FK', 'airway_segment', 'to_nav_point_id');
        $this->createIndex('airway_segment_names', 'airway_segment', 'airway_names(20)');
        $this->addForeignKey(
            'airway_segment_from_FK',
            'airway_segment',
            'from_nav_point_id',
            'nav_point',
            'id',
            null,
            'CASCADE'
        );
        $this->addForeignKey(
            'airway_segment_to_FK',
            'airway_segment',
            'to_nav_point_id',
            'nav_point',
            'id',
            null,
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('airway_segment');
        $this->dropTable('navaid');
        $this->dropTable('nav_point');
    }
}
