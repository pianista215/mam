<?php
namespace tests\unit\models;

use Yii;
use app\models\AcarsFile;
use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftType;
use app\models\Airport;
use app\models\Country;
use app\models\Flight;
use app\models\FlightReport;
use app\models\Pilot;
use tests\unit\BaseUnitTest;

class AcarsFileTest extends BaseUnitTest
{
    protected FlightReport $report;

    protected function _before(){
        parent::_before();

        $country = new Country(['name' => 'Spain', 'iso2_code' => 'ES']);
        $country->save();

        $airport1 = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'Madrid-Barajas',
            'latitude' => 40.471926,
            'longitude' => -3.56264,
            'city' => 'Madrid',
            'country_id' => $country->id,
        ]);
        $airport1->save();

        $airport2 = new Airport([
            'icao_code' => 'LEBL',
            'name' => 'Barcelona-El Prat',
            'latitude' => 41.2971,
            'longitude' => 2.0785,
            'city' => 'Barcelona',
            'country_id' => $country->id,
        ]);
        $airport2->save();

        $airport3 = new Airport([
            'icao_code' => 'LEVC',
            'name' => 'Valencia-Manises',
            'latitude' => 39.489444,
            'longitude' => -0.48166,
            'city' => 'Valencia',
            'country_id' => $country->id,
        ]);
        $airport3->save();

        $airport4 = new Airport([
            'icao_code' => 'LEAL',
            'name' => 'Alicante',
            'latitude' => 38.28222,
            'longitude' => 0.55805,
            'city' => 'Alicante',
            'country_id' => $country->id,
        ]);
        $airport4->save();

        $pilot = new Pilot([
            'license' => 'MAD12345',
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => Yii::$app->security->generatePasswordHash('SecurePass123!'),
            'country_id' => $country->id,
            'city' => 'Madrid',
            'location' => 'LEMD',
            'date_of_birth' => '1990-01-01',
        ]);
        $pilot->save();

        $aircraftType = new AircraftType([
            'icao_type_code' => 'B738',
            'name' => 'Boeing 737-800',
            'max_nm_range' => 2900,
        ]);
        $aircraftType->save();

        $config = new AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
        ]);
        $config->save();

        $aircraft = new Aircraft([
            'aircraft_configuration_id' => $config->id,
            'registration' => 'EC-MAD',
            'name' => '737-800 Mad',
            'location' => 'LEMD',
            'hours_flown' => 1000.5,
        ]);
        $aircraft->save();

        $flight = new Flight([
            'pilot_id' => $pilot->id,
            'aircraft_id' => $aircraft->id,
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'alternative1_icao' => 'LEVC',
            'alternative2_icao' => 'LEAL',
            'code' => 'FLT001',
            'cruise_speed_value' => '450',
            'cruise_speed_unit' => 'N',
            'flight_level_value' => '360',
            'flight_level_unit' => 'F',
            'route' => 'ROUTE',
            'estimated_time' => '0200',
            'other_information' => 'Other flight details',
            'endurance_time' => '0500',
            'report_tool' => 'Tool name'
        ]);

        $flight->save();

        $this->report = new FlightReport([
            'flight_id' => $flight->id,
        ]);

        $this->report->save();
    }


    public function testValidationRules()
    {
        $model = new AcarsFile();

        // Empty model
        $this->assertFalse($model->save(), 'Model should not validate when empty.');
        $this->assertArrayHasKey('chunk_id', $model->getErrors(), 'chunk_id is required.');
        $this->assertArrayHasKey('flight_report_id', $model->getErrors(), 'flight_report_id is required.');
        $this->assertArrayHasKey('sha256sum', $model->getErrors(), 'sha256sum is required.');

        // Valid
        $model->chunk_id = 1;
        $model->flight_report_id = $this->report->id;
        $model->sha256sum = base64_decode('YTNjMjU2NGYyM2U3MThkODFkNjM4OWI3YTdkZjc3ZWE=');
        $this->assertTrue($model->save(), 'Model should validate with correct data.');

        // Wrong sha256
        $model->sha256sum = 'short';
        $this->assertFalse($model->save(), 'Model should not validate if sha256sum is too short.');
        $this->assertArrayHasKey('sha256sum', $model->getErrors(), 'sha256sum should have a length of 32 bytes.');

        $model->sha256sum = str_repeat('a', 33);
        $this->assertFalse($model->save(), 'Model should not validate if sha256sum is too long.');
        $this->assertArrayHasKey('sha256sum', $model->getErrors(), 'sha256sum should have a length of 32 bytes.');
    }

    public function testUniqueConstraint()
    {
        $model1 = new AcarsFile([
            'chunk_id' => 1,
            'flight_report_id' => $this->report->id,
            'sha256sum' => base64_decode('YTNjMjU2NGYyM2U3MThkODFkNjM4OWI3YTdkZjc3ZWE='),
        ]);
        $this->assertTrue($model1->save(), 'First model should save successfully.');

        $model2 = new AcarsFile([
            'chunk_id' => 1,
            'flight_report_id' => $this->report->id,
            'sha256sum' => base64_decode('YTNjMjU2NGYyM2U3MThUODFkNjM4OWI3YTdkZjc3ZWE='),
        ]);

        $this->assertFalse($model2->save(), 'Second model collides with PK');
    }

}