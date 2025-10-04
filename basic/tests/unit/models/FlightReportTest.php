<?php

namespace tests\unit\models;

use Yii;
use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftType;
use app\models\Airport;
use app\models\Country;
use app\models\Flight;
use app\models\FlightReport;
use app\models\Pilot;
use tests\unit\BaseUnitTest;

class FlightReportTest extends BaseUnitTest
{

    protected Flight $flight;

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

        $this->flight = new Flight([
            'pilot_id' => $pilot->id,
            'aircraft_id' => $aircraft->id,
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'alternative1_icao' => 'LEVC',
            'alternative2_icao' => 'LEAL',
            'flight_rules' => 'I',
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

        $this->flight->save();
    }

    public function testValidFlightReport()
    {
        $model = new FlightReport([
            'flight_id' => $this->flight->id,
            'flight_time_minutes' => 120,
            'block_time_minutes' => 130,
            'total_fuel_burn_kg' => 5000,
            'distance_nm' => 300,
            'initial_fuel_on_board' => 7000,
            'zero_fuel_weight' => 50000,
            'crash' => 0,
            'start_time' => '2025-01-01 10:00:00',
            'end_time' => '2025-01-01 12:00:00',
            'landing_airport' => 'LEVC',
            'pilot_comments' => 'Good flight.',
            'sim_aircraft_name' => 'B738 Simulator',
        ]);

        $this->assertTrue($model->validate(), 'Model should validate with correct data.');
    }

    public function testValidFlightReportMinimum()
    {
        $model = new FlightReport([
            'flight_id' => $this->flight->id,
        ]);

        $this->assertTrue($model->validate(), 'Model should validate with minimum data.');
    }

    public function testInvalidFlightId()
    {
        $model = new FlightReport([
            'flight_id' => 9999,
            'start_time' => '2025-01-01 10:00:00',
            'end_time' => '2025-01-01 12:00:00',
        ]);

        $this->assertFalse($model->save(), 'Model should not validate with a non-existing flight_id.');
        $this->assertArrayHasKey('flight_id', $model->getErrors(), 'Error for invalid flight_id should be present.');
    }

    public function testInvalidDateTimeFormat()
    {
        $model = new FlightReport([
            'flight_id' => $this->flight->id,
            'start_time' => '01-01-2025 10:00:00',
            'end_time' => '2025/01/01 12:00:00',
        ]);

        $this->assertFalse($model->save(), 'Model should not validate with incorrect datetime formats.');
        $this->assertArrayHasKey('start_time', $model->getErrors(), 'Error for start_time with invalid format should be present.');
        $this->assertArrayHasKey('end_time', $model->getErrors(), 'Error for end_time with invalid format should be present.');
    }

    public function testUniqueFlightId()
    {
        $model1 = new FlightReport([
            'flight_id' => $this->flight->id,
            'start_time' => '2025-01-01 10:00:00',
            'end_time' => '2025-01-01 12:00:00',
        ]);
        $this->assertTrue($model1->save(), 'First FlightReport should save successfully.');

        $model2 = new FlightReport([
            'flight_id' => $this->flight->id, // Repetido
            'start_time' => '2025-01-02 10:00:00',
            'end_time' => '2025-01-02 12:00:00',
        ]);
        $this->assertFalse($model2->save(), 'Model should not validate with duplicate flight_id.');
        $this->assertArrayHasKey('flight_id', $model2->getErrors(), 'Error for duplicate flight_id should be present.');
    }

}
