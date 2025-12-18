<?php

namespace tests\unit\models;

use Yii;
use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftType;
use app\models\Airport;
use app\models\Country;
use app\models\Flight;
use app\models\Pilot;
use tests\unit\BaseUnitTest;

class FlightTest extends BaseUnitTest
{

    protected Aircraft $aircraft;
    protected Pilot $pilot;

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

        $this->pilot = new Pilot([
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
        $this->pilot->save();

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

        $this->aircraft = new Aircraft([
            'aircraft_configuration_id' => $config->id,
            'registration' => 'EC-MAD',
            'name' => '737-800 Mad',
            'location' => 'LEMD',
            'hours_flown' => 1000.5,
        ]);
        $this->aircraft->save();
    }

    public function testFlightCreationWithMissingRequiredFields()
    {
        $model = new Flight();

        $this->assertFalse($model->validate(), 'Model should not validate with all required fields missing.');
        $this->assertArrayHasKey('pilot_id', $model->getErrors(), 'Missing error for pilot_id.');
        $this->assertArrayHasKey('aircraft_id', $model->getErrors(), 'Missing error for aircraft_id.');
        $this->assertArrayHasKey('code', $model->getErrors(), 'Missing error for code.');
        $this->assertArrayHasKey('departure', $model->getErrors(), 'Missing error for departure.');
        $this->assertArrayHasKey('arrival', $model->getErrors(), 'Missing error for arrival.');
        $this->assertArrayHasKey('alternative1_icao', $model->getErrors(), 'Missing error for alternative1_icao.');
        $this->assertArrayHasKey('flight_rules', $model->getErrors(), 'Missing error for flight_rules.');
        $this->assertArrayHasKey('cruise_speed_value', $model->getErrors(), 'Missing error for cruise_speed_value.');
        $this->assertArrayHasKey('cruise_speed_unit', $model->getErrors(), 'Missing error for cruise_speed_unit.');
        $this->assertArrayHasKey('flight_level_unit', $model->getErrors(), 'Missing error for flight_level_unit.');
        $this->assertArrayHasKey('route', $model->getErrors(), 'Missing error for route.');
        $this->assertArrayHasKey('estimated_time', $model->getErrors(), 'Missing error for estimated_time.');
        $this->assertArrayHasKey('other_information', $model->getErrors(), 'Missing error for other_information.');
        $this->assertArrayHasKey('endurance_time', $model->getErrors(), 'Missing error for endurance_time.');
        $this->assertArrayHasKey('report_tool', $model->getErrors(), 'Missing error for report_tool.');

        $model->pilot_id = $this->pilot->id;
        $model->aircraft_id = $this->aircraft->id;
        $model->departure = 'LEMD';
        $model->arrival = 'LEBL';
        $model->alternative1_icao = 'LEVC';
        $model->flight_rules = 'I';
        $model->flight_type = 'R';
        $this->assertFalse($model->save(), 'Model should not validate with some required fields missing.');
        $this->assertArrayHasKey('code', $model->getErrors(), 'Missing error for code with partial data.');

        // Valid
        $model->code = 'FLT001';
        $model->cruise_speed_value = '450';
        $model->cruise_speed_unit = 'N';
        $model->flight_level_value = '360';
        $model->flight_level_unit = 'F';
        $model->route = 'ROUTE';
        $model->estimated_time = '0200';
        $model->other_information = 'Other flight details';
        $model->endurance_time = '0500';
        $model->report_tool = 'ToolName';

        $this->assertTrue($model->save(), 'Model should validate with all required fields provided.');
    }

    public function testFlightCreationTwoAlternatives()
    {
        $model = new Flight();
        $model->pilot_id = $this->pilot->id;
        $model->aircraft_id = $this->aircraft->id;
        $model->departure = 'LEMD';
        $model->arrival = 'LEBL';
        $model->alternative1_icao = 'LEVC';
        $model->alternative1_icao = 'LEAL';
        $model->flight_rules = 'V';
        $model->code = 'FLT001';
        $model->cruise_speed_value = '450';
        $model->cruise_speed_unit = 'N';
        $model->flight_level_value = '360';
        $model->flight_level_unit = 'F';
        $model->route = 'ROUTE';
        $model->estimated_time = '0200';
        $model->other_information = 'Other flight details';
        $model->endurance_time = '0500';
        $model->report_tool = 'ToolName';
        $model->flight_type = 'R';


        $this->assertTrue($model->save(), 'Model should validate with two alternatives.');
    }

    public function testFlightCreationInvalidPilotAircraft()
    {
        $model = new Flight();
        $model->pilot_id = 3;
        $model->aircraft_id = 3;
        $model->departure = 'LEMD';
        $model->arrival = 'LEBL';
        $model->alternative1_icao = 'LEVC';
        $model->flight_rules = 'Y';
        $model->code = 'FLT001';
        $model->cruise_speed_value = '450';
        $model->cruise_speed_unit = 'N';
        $model->flight_level_value = '360';
        $model->flight_level_unit = 'F';
        $model->route = 'ROUTE';
        $model->estimated_time = '0200';
        $model->other_information = 'Other flight details';
        $model->endurance_time = '0500';
        $model->report_tool = 'ToolName';
        $model->flight_type = 'R';


        $this->assertFalse($model->save(), 'Model should not validate with invalid aircraft/pilot.');
        $this->assertArrayHasKey('pilot_id', $model->getErrors());
        $this->assertArrayHasKey('aircraft_id', $model->getErrors());
    }

    public function testFlightCreationInvalidAirports()
    {
        $model = new Flight();
        $model->pilot_id = $this->pilot->id;
        $model->aircraft_id = $this->aircraft->id;
        $model->departure = 'LEAA';
        $model->arrival = 'LEEE';
        $model->alternative1_icao = 'LEEC';
        $model->alternative2_icao = 'LEED';
        $model->flight_rules = 'Z';
        $model->code = 'FLT001';
        $model->cruise_speed_value = '450';
        $model->cruise_speed_unit = 'N';
        $model->flight_level_value = '360';
        $model->flight_level_unit = 'F';
        $model->route = 'ROUTE';
        $model->estimated_time = '0200';
        $model->other_information = 'Other flight details';
        $model->endurance_time = '0500';
        $model->report_tool = 'ToolName';
        $model->flight_type = 'R';


        $this->assertFalse($model->save(), 'Model should not validate with invalid airports.');
        $this->assertArrayHasKey('departure', $model->getErrors());
        $this->assertArrayHasKey('arrival', $model->getErrors());
        $this->assertArrayHasKey('alternative1_icao', $model->getErrors());
        $this->assertArrayHasKey('alternative2_icao', $model->getErrors());
    }

    public function testIsProcessed()
    {
        $flight = new Flight(['status' => 'C']);
        $this->assertFalse($flight->isProcessed(), 'Status C should not be processed');

        $flight->status = 'V';
        $this->assertTrue($flight->isProcessed(), 'Status V should be processed');

        $flight->status = 'F';
        $this->assertTrue($flight->isProcessed(), 'Status F should be processed');

        $flight->status = 'R';
        $this->assertTrue($flight->isProcessed(), 'Status R should be processed');
    }

    public function testHasAcarsInfo()
    {
        $flight = new Flight();

        $this->assertFalse($flight->hasAcarsInfo(), 'Flight without report should not have ACARS info');

        $flight->populateRelation('flightReport', (object)['flightPhases' => null]);
        $this->assertFalse($flight->hasAcarsInfo(), 'Flight report without phases should not have ACARS info');

        $flight->populateRelation('flightReport', (object)['flightPhases' => ['phase1']]);
        $this->assertTrue($flight->hasAcarsInfo(), 'Flight report with phases should have ACARS info');
    }

    public function testIsValidated()
    {
        $flight = new Flight(['status' => 'C']);
        $this->assertFalse($flight->isValidated(), 'Status C should not be validated');

        $flight->status = 'V';
        $this->assertFalse($flight->isValidated(), 'Status V should not be validated');

        $flight->status = 'F';
        $this->assertTrue($flight->isValidated(), 'Status F should be validated');

        $flight->status = 'R';
        $this->assertTrue($flight->isValidated(), 'Status R should be validated');
    }

    public function testIsPendingValidation()
    {
        $flight = new Flight(['status' => 'V']);
        $this->assertTrue($flight->isPendingValidation(), 'Status V should be pending validation');

        $flight = new Flight([
            'status' => 'C',
            'creation_date' => (new \DateTimeImmutable())->modify('-48 hours')->format('Y-m-d H:i:s'),
        ]);
        $this->assertFalse($flight->isPendingValidation(), 'Status C with less than 72h should not be pending validation');

        $flight->creation_date = (new \DateTimeImmutable())->modify('-80 hours')->format('Y-m-d H:i:s');
        $this->assertTrue($flight->isPendingValidation(), 'Status C with more than 72h should be pending validation');

        $flight = new Flight(['status' => 'F']);
        $this->assertFalse($flight->isPendingValidation(), 'Status F should not be pending validation');
    }

    public function testFlightStatusValidation()
    {
        $flight = new Flight([
            'pilot_id' => $this->pilot->id,
            'aircraft_id' => $this->aircraft->id,
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'alternative1_icao' => 'LEVC',
            'flight_rules' => 'I',
            'code' => 'FLTSTATUS',
            'cruise_speed_value' => '450',
            'cruise_speed_unit' => 'N',
            'flight_level_value' => '360',
            'flight_level_unit' => 'F',
            'route' => 'ROUTE',
            'estimated_time' => '0200',
            'other_information' => 'Other flight details',
            'endurance_time' => '0500',
            'report_tool' => 'ToolName',
        ]);

        // Valid status
        foreach (['C', 'S', 'V', 'F', 'R'] as $status) {
            $flight->status = $status;
            $this->assertTrue($flight->validate(['status']), "Status $status should be valid");
        }

        // Invalid status
        $flight->status = 'X';
        $this->assertFalse($flight->validate(['status']), 'Invalid status should not validate');
        $this->assertArrayHasKey('status', $flight->getErrors(), 'Missing error for invalid status');
    }

}
