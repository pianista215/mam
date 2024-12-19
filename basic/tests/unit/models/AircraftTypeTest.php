<?php

namespace tests\unit\models;

use app\models\AircraftType;
use tests\unit\DbTestCase;

class AircraftTypeTest extends DbTestCase
{
    public function testCreateValidAircraftType()
    {
        $aircraftType = new AircraftType([
            'icao_type_code' => 'B738',
            'name' => 'Boeing 737-800',
            'max_nm_range' => 2900,
        ]);

        $this->assertTrue($aircraftType->save());
        $this->assertNotEmpty($aircraftType->id);
    }

    public function testTrimAndToUpperAircraftType()
    {
        $aircraftType = new AircraftType([
            'icao_type_code' => ' b7 3 8',
            'name' => '   Boeing 737-800   ',
            'max_nm_range' => 2900,
        ]);

        $this->assertTrue($aircraftType->save());
        $this->assertEquals($aircraftType->icao_type_code, 'B738');
        $this->assertEquals($aircraftType->name, 'Boeing 737-800');
    }

    public function testCreateAircraftTypeWithoutRequiredFields()
    {
        $aircraftType = new AircraftType([]);
        $this->assertFalse($aircraftType->save());
        $this->assertArrayHasKey('icao_type_code', $aircraftType->errors);
        $this->assertArrayHasKey('name', $aircraftType->errors);
        $this->assertArrayHasKey('max_nm_range', $aircraftType->errors);
    }

    public function testCreateAircraftTypeWithInvalidIcaoTypeCodeLength()
    {
        $aircraftType = new AircraftType([
            'icao_type_code' => 'ABCDE',
            'name' => 'Invalid Aircraft',
            'max_nm_range' => 1500,
        ]);

        $this->assertFalse($aircraftType->save());
        $this->assertArrayHasKey('icao_type_code', $aircraftType->errors);
    }

    public function testCreateAircraftTypeWithDuplicateIcaoTypeCode()
    {
        $aircraftType1 = new AircraftType([
            'icao_type_code' => 'A320',
            'name' => 'Airbus A320',
            'max_nm_range' => 3100,
        ]);
        $aircraftType1->save();

        $aircraftType2 = new AircraftType([
            'icao_type_code' => 'A320',
            'name' => 'Duplicate Airbus A320',
            'max_nm_range' => 3200,
        ]);

        $this->assertFalse($aircraftType2->save());
        $this->assertArrayHasKey('icao_type_code', $aircraftType2->errors);
    }
}