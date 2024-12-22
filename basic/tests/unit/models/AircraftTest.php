<?php

namespace tests\unit\models;

use Yii;
use tests\unit\BaseUnitTest;
use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftType;
use app\models\Airport;
use app\models\Country;

class AircraftTest extends BaseUnitTest
{
    protected AircraftConfiguration $standardConfig;
    protected AircraftConfiguration $cargoConfig;

    protected function _before(){
        parent::_before();

        $country = new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']);
        $country->save();

        $airport = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'Madrid-Barajas',
            'latitude' => 40.471926,
            'longitude' => -3.56264,
            'city' => 'Madrid',
            'country_id' => 1,
        ]);
        $airport->save();

        $aircraftType = new AircraftType([
            'icao_type_code' => 'B738',
            'name' => 'Boeing 737-800',
            'max_nm_range' => 2900,
        ]);
        $aircraftType->save();

        $this->standardConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
        ]);
        $this->standardConfig->save();

        $this->cargoConfig = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Cargo',
            'pax_capacity' => 0,
            'cargo_capacity' => 18000,
        ]);
        $this->cargoConfig->save();
    }

    public function testValidAircraft()
    {
        $standard = new Aircraft([
            'aircraft_configuration_id' => $this->standardConfig->id,
            'registration' => 'STD123',
            'name' => 'Boeing 737 Std',
            'location' => 'LEMD',
            'hours_flown' => 1000.5,
        ]);
        $this->assertTrue($standard->save());

        $cargo = new Aircraft([
            'aircraft_configuration_id' => $this->cargoConfig->id,
            'registration' => 'CARGO123',
            'name' => 'Boeing 737 Car',
            'location' => 'LEMD',
            'hours_flown' => 1000.5,
        ]);
        $this->assertTrue($cargo->save());
    }

    public function testInvalidAircraftWithoutRequiredFields()
    {
        $aircraft = new Aircraft([]);

        $this->assertFalse($aircraft->save());

        $this->assertArrayHasKey('aircraft_configuration_id', $aircraft->errors);
        $this->assertArrayHasKey('registration', $aircraft->errors);
        $this->assertArrayHasKey('name', $aircraft->errors);
        $this->assertArrayHasKey('location', $aircraft->errors);
    }

    public function testInvalidAircraftWithExceedingLengthFields()
    {
        $aircraft = new Aircraft([
            'aircraft_configuration_id' => $this->standardConfig->id,
            'registration' => str_repeat('A', 11),
            'name' => str_repeat('B', 21),
            'location' => 'LEMD',
            'hours_flown' => 500.5,
        ]);

        $this->assertFalse($aircraft->save());

        $this->assertArrayHasKey('registration', $aircraft->errors);
        $this->assertArrayHasKey('name', $aircraft->errors);
    }

    public function testAircraftConfigurationNotFound()
    {
        $aircraft = new Aircraft([
            'aircraft_configuration_id' => 1000000,
            'registration' => 'XYZ987',
            'name' => 'Airbus A320',
            'location' => 'LEMD',
            'hours_flown' => 2000.0,
        ]);

        $this->assertFalse($aircraft->save());
        $this->assertArrayHasKey('aircraft_configuration_id', $aircraft->errors);
    }

    public function testAircraftLocationNotFound()
    {
        $aircraft = new Aircraft([
            'aircraft_configuration_id' => $this->standardConfig->id,
            'registration' => 'XYZ123',
            'name' => 'Airbus A380',
            'location' => 'JAJA',
            'hours_flown' => 3000.0,
        ]);

        $this->assertFalse($aircraft->save());
        $this->assertArrayHasKey('location', $aircraft->errors);
    }

    public function testRegistrationToUpperNameTrim()
    {
        $aircraft1 = new Aircraft([
            'aircraft_configuration_id' => $this->standardConfig->id,
            'registration' => 'unam 123',
            'name' => ' Boeing repeated  ',
            'location' => 'LEMD',
            'hours_flown' => 1000.0,
        ]);
        $this->assertTrue($aircraft1->save());
        $this->assertEquals($aircraft1->registration, 'UNAM123');
        $this->assertEquals($aircraft1->name, 'Boeing repeated');
    }

    public function testUniqueRegistration()
    {
        $aircraft1 = new Aircraft([
            'aircraft_configuration_id' => $this->standardConfig->id,
            'registration' => 'UNIQ123',
            'name' => 'Unique 737',
            'location' => 'LEMD',
            'hours_flown' => 1500.0,
        ]);
        $this->assertTrue($aircraft1->save());

        $aircraft2 = new Aircraft([
            'aircraft_configuration_id' => $this->cargoConfig->id,
            'registration' => 'UNIQ123',
            'name' => 'Unique 737-2',
            'location' => 'LEMD',
            'hours_flown' => 1200.0,
        ]);

        $this->assertFalse($aircraft2->save());
        $this->assertArrayHasKey('registration', $aircraft2->errors);

        $aircraft3 = new Aircraft([
            'aircraft_configuration_id' => $this->cargoConfig->id,
            'registration' => 'u n i q 123',
            'name' => 'Unique 737-2',
            'location' => 'LEMD',
            'hours_flown' => 1200.0,
        ]);
        $this->assertFalse($aircraft3->save());
        $this->assertArrayHasKey('registration', $aircraft3->errors);
    }

    public function testUniqueName()
    {
        $aircraft1 = new Aircraft([
            'aircraft_configuration_id' => $this->standardConfig->id,
            'registration' => 'UNAM123',
            'name' => 'Boeing repeated',
            'location' => 'LEMD',
            'hours_flown' => 1000.0,
        ]);
        $this->assertTrue($aircraft1->save());

        $aircraft2 = new Aircraft([
            'aircraft_configuration_id' => $this->cargoConfig->id,
            'registration' => 'UNAM456',
            'name' => 'Boeing repeated',
            'location' => 'LEMD',
            'hours_flown' => 1200.0,
        ]);

        $this->assertFalse($aircraft2->validate());
        $this->assertArrayHasKey('name', $aircraft2->errors);
    }

    public function testHoursFlownPositiveAndZero()
    {
        $nohours = new Aircraft([
            'aircraft_configuration_id' => $this->standardConfig->id,
            'registration' => 'UNAM123',
            'name' => 'Boeing nohours',
            'location' => 'LEMD',
        ]);
        $this->assertTrue($nohours->save());
        $this->assertEquals($nohours->hours_flown, 0);

        $zero = new Aircraft([
            'aircraft_configuration_id' => $this->standardConfig->id,
            'registration' => 'UNAM456',
            'name' => 'Boeing zero',
            'location' => 'LEMD',
            'hours_flown' => 0.0,
        ]);
        $this->assertTrue($zero->save());

        $negative = new Aircraft([
            'aircraft_configuration_id' => $this->cargoConfig->id,
            'registration' => 'UNAM789',
            'name' => 'Boeing negative',
            'location' => 'LEMD',
            'hours_flown' => -1.0,
        ]);

        $this->assertFalse($negative->save());
        $this->assertArrayHasKey('hours_flown', $negative->errors);
    }
}
