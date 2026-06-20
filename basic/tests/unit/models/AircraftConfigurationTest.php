<?php
namespace tests\unit\models;

use app\models\AircraftConfiguration;
use app\models\AircraftType;
use tests\unit\BaseUnitTest;

class AircraftConfigurationTest extends BaseUnitTest
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
            'crew' => 5,
            'mtow' => 79016,
            'bew' => 41413,
        ]);
        $this->assertTrue($standardConfig->save());

        $cargoConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Cargo',
            'pax_capacity' => 0,
            'cargo_capacity' => 18000,
            'crew' => 3,
            'mtow' => 79016,
            'bew' => 41413,
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
            'crew' => 5,
            'mtow' => 79016,
            'bew' => 41413,
        ]);
        $this->assertFalse($invalidConfig->save());
        $this->assertArrayHasKey('pax_capacity', $invalidConfig->errors);

        $invalidCrewConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
            'crew' => 0, // Invalid crew (must be >= 1)
            'mtow' => 79016,
            'bew' => 41413,
        ]);
        $this->assertFalse($invalidCrewConfig->save());
        $this->assertArrayHasKey('crew', $invalidCrewConfig->errors);

        $invalidMtowConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
            'crew' => 5,
            'mtow' => 40000, // Invalid: mtow <= bew
            'bew' => 79016,
        ]);
        $this->assertFalse($invalidMtowConfig->save());
        $this->assertArrayHasKey('mtow', $invalidMtowConfig->errors);

        $invalidCargoConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 90000, // Invalid: cargo_capacity >= mtow
            'crew' => 5,
            'mtow' => 79016,
            'bew' => 41413,
        ]);
        $this->assertFalse($invalidCargoConfig->save());
        $this->assertArrayHasKey('cargo_capacity', $invalidCargoConfig->errors);

        $duplicateConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
            'crew' => 5,
            'mtow' => 79016,
            'bew' => 41413,
        ]);
        $this->assertTrue($duplicateConfig->save());

        $duplicateConfig2 = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard', // Duplicate name for same aircraft type
            'pax_capacity' => 150,
            'cargo_capacity' => 1500,
            'crew' => 4,
            'mtow' => 75000,
            'bew' => 40000,
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
            'crew' => 2,
            'mtow' => 5000,
            'bew' => 3000,
        ]);
        $this->assertFalse($nonExistentConfig->save());
        $this->assertArrayHasKey('aircraft_type_id', $nonExistentConfig->errors);
    }

    public function testRegressionFieldsNotAssignableViaAdminFormScenario()
    {
        $config = new AircraftConfiguration(['scenario' => AircraftConfiguration::SCENARIO_ADMIN_FORM]);
        $config->load([
            'AircraftConfiguration' => [
                'aircraft_type_id' => 1,
                'name' => 'Test',
                'pax_capacity' => 100,
                'cargo_capacity' => 1000,
                'crew' => 2,
                'mtow' => 50000,
                'bew' => 30000,
                'fuel_regression_a' => 999.99,
                'fuel_regression_b' => 5.1234,
                'fuel_regression_n' => 100,
                'fuel_avg_kg_per_min' => 99.9999,
                'fuel_regression_updated_at' => '2026-01-01 00:00:00',
            ],
        ]);

        $this->assertNull($config->fuel_regression_a);
        $this->assertNull($config->fuel_regression_b);
        $this->assertNull($config->fuel_regression_n);
        $this->assertNull($config->fuel_avg_kg_per_min);
        $this->assertNull($config->fuel_regression_updated_at);
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
            'cargo_capacity' => 200,
            'crew' => 2,
            'mtow' => 1100,
            'bew' => 767,
        ]);
        $this->assertTrue($standardConfig->save());
        $this->assertEquals($standardConfig->name, 'Trimmed');
    }
}