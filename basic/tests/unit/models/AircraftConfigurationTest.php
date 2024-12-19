<?php
namespace tests\unit\models;

use app\models\AircraftConfiguration;
use app\models\AircraftType;
use tests\unit\DbTestCase;

class AircraftConfigurationTest extends DbTestCase
{
    public function testValidConfiguration()
    {
        $aircraftType = new AircraftType([
            'icao_type_code' => 'B738',
            'name' => 'Boeing 737-800',
            'max_nm_range' => 2950,
        ]);
        $this->assertTrue($aircraftType->save());

        $standardConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
        ]);
        $this->assertTrue($standardConfig->save());

        $cargoConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Cargo',
            'pax_capacity' => 0,
            'cargo_capacity' => 18000,
        ]);
        $this->assertTrue($cargoConfig->save());
    }

    public function testInvalidConfiguration()
    {
        $aircraftType = new AircraftType([
            'icao_type_code' => 'B738',
            'name' => 'Boeing 737-800',
            'max_nm_range' => 2950,
        ]);
        $this->assertTrue($aircraftType->save());

        $invalidConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard',
            'pax_capacity' => -10, // Invalid pax capacity
            'cargo_capacity' => 2000,
        ]);
        $this->assertFalse($invalidConfig->save());
        $this->assertArrayHasKey('pax_capacity', $invalidConfig->errors);

        $duplicateConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
        ]);
        $this->assertTrue($duplicateConfig->save());

        $duplicateConfig2 = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard', // Duplicate name for same aircraft type
            'pax_capacity' => 150,
            'cargo_capacity' => 1500,
        ]);
        $this->assertFalse($duplicateConfig2->save());
        $this->assertArrayHasKey('name', $duplicateConfig2->errors, var_export($duplicateConfig2->errors, true));
    }

    public function testNonExistentAircraftType()
    {
        $nonExistentConfig = new AircraftConfiguration([
            'aircraft_type_id' => 9999, // Non-existent aircraft type ID
            'name' => 'Ghost',
            'pax_capacity' => 100,
            'cargo_capacity' => 500,
        ]);
        $this->assertFalse($nonExistentConfig->save());
        $this->assertArrayHasKey('aircraft_type_id', $nonExistentConfig->errors);
    }

    public function testTrimName()
    {
        $aircraftType = new AircraftType([
            'icao_type_code' => 'B738',
            'name' => '    Boeing 737-800    ',
            'max_nm_range' => 2950,
        ]);

        $this->assertTrue($aircraftType->save());

        $standardConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => '   Trimmed    ',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
        ]);
        $this->assertTrue($standardConfig->save());
        $this->assertEquals($standardConfig->name, 'Trimmed');
    }
}