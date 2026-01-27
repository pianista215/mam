<?php

namespace tests\unit\models;

use Yii;
use tests\unit\BaseUnitTest;
use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftType;
use app\models\Airport;
use app\models\CharterRoute;
use app\models\Country;
use app\models\Pilot;
use app\models\Route;
use app\models\Tour;
use app\models\TourStage;
use app\models\SubmittedFlightPlan;
use app\models\LiveFlightPosition;


class SubmittedFlightPlanTest extends BaseUnitTest
{

    protected Aircraft $lemdAircraft;
    protected Aircraft $lemd2Aircraft;
    protected Aircraft $leblAircraft;
    protected CharterRoute $charterRoute;
    protected Route $routeMadBcn;
    protected Route $routeVllBcn;
    protected TourStage $stageMadBcn;
    protected Pilot $pilotMad;
    protected Pilot $pilot2Mad;
    protected Pilot $pilotBcn;

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
            'icao_code' => 'LEVD',
            'name' => 'Valladolid Villanubla',
            'latitude' => 41.7114,
            'longitude' => -4.84472,
            'city' => 'Valladolid',
            'country_id' => $country->id,
        ]);
        $airport3->save();

        $this->routeMadBcn = new Route([
            'code' => 'MAD-BCN',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
        ]);
        $this->routeMadBcn->save();

        $this->routeVllBcn = new Route([
            'code' => 'VLL-BCN',
            'departure' => 'LEVD',
            'arrival' => 'LEBL',
        ]);
        $this->routeVllBcn->save();

        $tour = new Tour([
            'name' => 'Test',
            'description' => 'blabla',
            'start' => date('Y-m-d', strtotime('-100 day')),
            'end' => date('Y-m-d', strtotime('+100 day'))
        ]);
        $tour->save();

        $this->stageMadBcn = new TourStage([
            'tour_id' => $tour->id,
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'sequence' => 1
        ]);
        $this->stageMadBcn->save();

        $this->pilotMad = new Pilot([
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
        $this->pilotMad->save();

        $this->charterRoute = new CharterRoute([
            'pilot_id' => $this->pilotMad->id,
            'departure' => 'LEMD',
            'arrival' => 'LEVD'
        ]);

        $this->charterRoute->save();

        $this->pilot2Mad = new Pilot([
            'license' => 'MAD34567',
            'name' => 'John2',
            'surname' => 'Doe2',
            'email' => 'john.doe2@example.com',
            'password' => Yii::$app->security->generatePasswordHash('SecurePass123!'),
            'country_id' => $country->id,
            'city' => 'Madrid',
            'location' => 'LEMD',
            'date_of_birth' => '1992-01-01',
        ]);
        $this->pilot2Mad->save();

        $this->pilotBcn = new Pilot([
            'license' => 'BCL12345',
            'name' => 'Bcl',
            'surname' => 'Doe',
            'email' => 'john.bcl@example.com',
            'password' => Yii::$app->security->generatePasswordHash('SecurePass123!'),
            'country_id' => $country->id,
            'city' => 'Barcelona',
            'location' => 'LEBL',
            'date_of_birth' => '1988-01-01',
        ]);
        $this->pilotBcn->save();

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

        $this->lemdAircraft = new Aircraft([
            'aircraft_configuration_id' => $config->id,
            'registration' => 'EC-MAD',
            'name' => '737-800 Mad',
            'location' => 'LEMD',
            'hours_flown' => 1000.5,
        ]);
        $this->lemdAircraft->save();

        $this->lemd2Aircraft = new Aircraft([
            'aircraft_configuration_id' => $config->id,
            'registration' => 'EC-MAD2',
            'name' => '737-800 Mad2',
            'location' => 'LEMD',
            'hours_flown' => 1000.5,
        ]);
        $this->lemd2Aircraft->save();

        $this->leblAircraft = new Aircraft([
            'aircraft_configuration_id' => $config->id,
            'registration' => 'EC-BCL',
            'name' => '737-800 Bcn',
            'location' => 'LEBL',
            'hours_flown' => 5000.1,
        ]);
        $this->leblAircraft->save();
    }

    public function testValidSubmittedFlightPlanWithRoute()
    {

        $flightPlan = new SubmittedFlightPlan([
            'aircraft_id' => $this->lemdAircraft->id,
            'flight_rules' => 'V',
            'alternative1_icao' => 'LEVD',
            'alternative2_icao' => 'LEMD',
            'cruise_speed_value' => 400,
            'cruise_speed_unit' => 'N',
            'flight_level_value' => 350,
            'flight_level_unit' => 'F',
            'route' => 'NAND UM871 MINGU/N0419F320 UM871 GODOX',
            'estimated_time' => '0031',
            'other_information' => 'PBN/A1B1D1L1O1S2 COM/TCAS DOF/20241214 REG/ECSSS EET/LECB0024 CODE/BXXXX RVR/200 OPR/XXX PER/C RMK/TCAS RMK/IFPS REROUTE ACCEPTED',
            'endurance_time' => '0500',
            'route_id' => $this->routeMadBcn->id,
            'pilot_id' => $this->pilotMad->id,
        ]);

        $this->assertTrue($flightPlan->save());
    }

    public function testValidSubmittedFlightPlanWithTour()
    {

        $flightPlan = new SubmittedFlightPlan([
            'aircraft_id' => $this->lemdAircraft->id,
            'flight_rules' => 'V',
            'alternative1_icao' => 'LEVD',
            'alternative2_icao' => 'LEMD',
            'cruise_speed_value' => 400,
            'cruise_speed_unit' => 'N',
            'flight_level_value' => 350,
            'flight_level_unit' => 'F',
            'route' => 'NAND UM871 MINGU/N0419F320 UM871 GODOX',
            'estimated_time' => '0031',
            'other_information' => 'PBN/A1B1D1L1O1S2 COM/TCAS DOF/20241214 REG/ECSSS EET/LECB0024 CODE/BXXXX RVR/200 OPR/XXX PER/C RMK/TCAS RMK/IFPS REROUTE ACCEPTED',
            'endurance_time' => '0500',
            'tour_stage_id' => $this->stageMadBcn->id,
            'pilot_id' => $this->pilotMad->id,
        ]);

        $this->assertTrue($flightPlan->save());
    }

    public function testValidSubmittedFlightPlanWithCharter()
    {

        $flightPlan = new SubmittedFlightPlan([
            'aircraft_id' => $this->lemdAircraft->id,
            'flight_rules' => 'V',
            'alternative1_icao' => 'LEVD',
            'alternative2_icao' => 'LEMD',
            'cruise_speed_value' => 400,
            'cruise_speed_unit' => 'N',
            'flight_level_value' => 350,
            'flight_level_unit' => 'F',
            'route' => 'NAND UM871 MINGU/N0419F320 UM871 GODOX',
            'estimated_time' => '0031',
            'other_information' => 'PBN/A1B1D1L1O1S2 COM/TCAS DOF/20241214 REG/ECSSS EET/LECB0024 CODE/BXXXX RVR/200 OPR/XXX PER/C RMK/TCAS RMK/IFPS REROUTE ACCEPTED',
            'endurance_time' => '0500',
            'charter_route_id' => $this->charterRoute->id,
            'pilot_id' => $this->pilotMad->id,
        ]);

        $this->assertTrue($flightPlan->save());
    }

    public function testValidSubmittedFlightPlanOnlyOneAlternative()
    {
        $flightPlan = new SubmittedFlightPlan([
            'aircraft_id' => $this->lemdAircraft->id,
            'flight_rules' => 'V',
            'alternative1_icao' => 'LEVD',
            'cruise_speed_value' => 400,
            'cruise_speed_unit' => 'N',
            'flight_level_value' => 350,
            'flight_level_unit' => 'F',
            'route' => 'NAND UM871 MINGU/N0419F320 UM871 GODOX',
            'estimated_time' => '0031',
            'other_information' => 'PBN/A1B1D1L1O1S2 COM/TCAS DOF/20241214 REG/ECSSS EET/LECB0024 CODE/BXXXX RVR/200 OPR/XXX PER/C RMK/TCAS RMK/IFPS REROUTE ACCEPTED',
            'endurance_time' => '0500',
            'route_id' => $this->routeMadBcn->id,
            'pilot_id' => $this->pilotMad->id,
        ]);

        $this->assertTrue($flightPlan->save());
    }

    protected function createBaseFlightPlanData(): array
    {
        return [
            'aircraft_id' => $this->lemdAircraft->id,
            'flight_rules' => 'V',
            'alternative1_icao' => 'LEVD',
            'alternative2_icao' => 'LEMD',
            'cruise_speed_value' => 400,
            'cruise_speed_unit' => 'N',
            'flight_level_value' => 350,
            'flight_level_unit' => 'F',
            'route' => 'NAND UM871 MINGU/N0419F320 UM871 GODOX',
            'estimated_time' => '0031',
            'other_information' => 'PBN/A1B1D1L1O1S2 COM/TCAS DOF/20241214 REG/ECSSS EET/LECB0024 CODE/BXXXX RVR/200 OPR/XXX PER/C RMK/TCAS RMK/IFPS REROUTE ACCEPTED',
            'endurance_time' => '0500',
            'route_id' => $this->routeMadBcn->id,
            'pilot_id' => $this->pilotMad->id,
        ];
    }

    public function testFlightRulesCombinations()
    {
        $validFlightRules = ['I', 'V', 'Y', 'Z'];

        foreach ($validFlightRules as $flightRule) {
            $data = $this->createBaseFlightPlanData();
            $data['flight_rules'] = $flightRule;

            $flightPlan = new SubmittedFlightPlan($data);
            $this->assertTrue($flightPlan->save(), "Failed for flight_rule: $flightRule");
            $flightPlan->delete();
        }
    }

    public function testInvalidFlightRule()
    {
        $data = $this->createBaseFlightPlanData();
        $data['flight_rules'] = 'X'; // Invalid flight rule

        $flightPlan = new SubmittedFlightPlan($data);
        $this->assertFalse($flightPlan->save(), "Flight plan should not save with invalid flight_rule");
        $this->assertNotEmpty($flightPlan->getErrors('flight_rules'));
    }

    public function testCruiseSpeedUnitCombinations()
    {
        $validSpeedUnits = ['N', 'M', 'K'];

        foreach ($validSpeedUnits as $speedUnit) {
            $data = $this->createBaseFlightPlanData();
            $data['cruise_speed_unit'] = $speedUnit;

            $flightPlan = new SubmittedFlightPlan($data);
            $this->assertTrue($flightPlan->save(), "Failed for cruise_speed_unit: $speedUnit");
            $flightPlan->delete();
        }
    }

    public function testInvalidCruiseSpeedUnit()
    {
        $data = $this->createBaseFlightPlanData();
        $data['cruise_speed_unit'] = 'Z'; // Invalid cruise speed unit

        $flightPlan = new SubmittedFlightPlan($data);
        $this->assertFalse($flightPlan->save(), "Flight plan should not save with invalid cruise_speed_unit");
        $this->assertNotEmpty($flightPlan->getErrors('cruise_speed_unit'));
    }

    public function testFlightLevelUnitCombinations()
    {
        $validLevelUnits = ['F', 'A', 'S', 'M', 'VFR'];

        foreach ($validLevelUnits as $levelUnit) {
            $data = $this->createBaseFlightPlanData();
            $data['flight_level_unit'] = $levelUnit;

            // Specific case for VFR
            if ($levelUnit === 'VFR') {
                $data['flight_level_value'] = null;
            }

            $flightPlan = new SubmittedFlightPlan($data);
            $this->assertTrue($flightPlan->save(), "Failed for flight_level_unit: $levelUnit");
            $flightPlan->delete();
        }
    }

    public function testInvalidFlightLevelUnit()
    {
        $data = $this->createBaseFlightPlanData();
        $data['flight_level_unit'] = 'X'; // Invalid flight level unit

        $flightPlan = new SubmittedFlightPlan($data);
        $this->assertFalse($flightPlan->save(), "Flight plan should not save with invalid flight_level_unit");
        $this->assertNotEmpty($flightPlan->getErrors('flight_level_unit'));
    }

    public function testVFRFlightLevelWithValue()
    {
        $data = $this->createBaseFlightPlanData();
        $data['flight_level_unit'] = 'VFR';
        $data['flight_level_value'] = 100; // Should be null for VFR

        $flightPlan = new SubmittedFlightPlan($data);
        $this->assertFalse($flightPlan->save(), "Flight plan should not save with flight_level_value set for VFR");
        $this->assertNotEmpty($flightPlan->getErrors('flight_level_value'));
    }

    public function testInvalidAlternative()
    {
        $data = $this->createBaseFlightPlanData();
        $data['alternative1_icao'] = 'JAJA';
        $flightPlan = new SubmittedFlightPlan($data);

        $this->assertFalse($flightPlan->save(), "Flight plan should not save with invalid alternative1");
        $this->assertNotEmpty($flightPlan->getErrors('alternative1_icao'));

        $data['alternative2_icao'] = 'JAJA';
        $flightPlan2 = new SubmittedFlightPlan($data);

        $this->assertFalse($flightPlan2->save(), "Flight plan should not save with invalid alternative2");
        $this->assertNotEmpty($flightPlan2->getErrors('alternative2_icao'));
    }

    public function testInvalidSubmittedFlightPlanWithoutRequiredFields()
    {
        $flightPlan = new SubmittedFlightPlan([]);

        $this->assertFalse($flightPlan->save());
        $this->assertArrayHasKey('aircraft_id', $flightPlan->errors);
        $this->assertArrayHasKey('flight_rules', $flightPlan->errors);
        $this->assertArrayHasKey('alternative1_icao', $flightPlan->errors);
        $this->assertArrayHasKey('cruise_speed_value', $flightPlan->errors);
        $this->assertArrayHasKey('route', $flightPlan->errors);
        $this->assertArrayHasKey('estimated_time', $flightPlan->errors);
        $this->assertArrayHasKey('other_information', $flightPlan->errors);
        $this->assertArrayHasKey('endurance_time', $flightPlan->errors);
        $this->assertArrayHasKey('route_id', $flightPlan->errors);
        $this->assertArrayHasKey('tour_stage_id', $flightPlan->errors);
        $this->assertArrayHasKey('pilot_id', $flightPlan->errors);
        $this->assertArrayHasKey('cruise_speed_unit', $flightPlan->errors);
        $this->assertArrayHasKey('flight_level_unit', $flightPlan->errors);
    }

    public function testInvalidSubmittedFlightPlanRouteAndTour()
    {
        $flightPlan = new SubmittedFlightPlan([
            'aircraft_id' => $this->lemdAircraft->id,
            'flight_rules' => 'V',
            'alternative1_icao' => 'LEVD',
            'cruise_speed_value' => 400,
            'cruise_speed_unit' => 'N',
            'flight_level_value' => 350,
            'flight_level_unit' => 'F',
            'route' => 'NAND UM871 MINGU/N0419F320 UM871 GODOX',
            'estimated_time' => '0031',
            'other_information' => 'PBN/A1B1D1L1O1S2 COM/TCAS DOF/20241214 REG/ECSSS EET/LECB0024 CODE/BXXXX RVR/200 OPR/XXX PER/C RMK/TCAS RMK/IFPS REROUTE ACCEPTED',
            'endurance_time' => '0500',
            'route_id' => $this->routeMadBcn->id,
            'tour_stage_id' => $this->stageMadBcn->id,
            'pilot_id' => $this->pilotMad->id,
        ]);

        $this->assertFalse($flightPlan->save());
        $this->assertArrayHasKey('route_id', $flightPlan->errors);
        $this->assertArrayHasKey('tour_stage_id', $flightPlan->errors);
    }

    public function testNegativeValuesNotAllowed()
    {
        $fieldsToTest = ['cruise_speed_value', 'flight_level_value', 'estimated_time', 'endurance_time'];
        foreach ($fieldsToTest as $field) {
            $data = $this->createBaseFlightPlanData();
            $data[$field] = -1; // Assign a negative value

            $flightPlan = new SubmittedFlightPlan($data);
            $this->assertFalse($flightPlan->save(), "Flight plan should not save with negative value for $field".json_encode($flightPlan->errors));
            $this->assertNotEmpty($flightPlan->getErrors($field), "Expected errors for $field with negative value");
        }
    }

    // TODO: CHECK IF THE ERRORS ARE SHOWN IN THE VIEW WHEN TESTS OF THAT ARE CREATED, EXAMPLE AICRAFT RESERVED BY OTHER PILOT
    public function testOnlyOneFPLPerPilot()
    {
        $data1 = $this->createBaseFlightPlanData();
        $data1['pilot_id'] = $this->pilotMad->id;
        $flightPlan1 = new SubmittedFlightPlan($data1);
        $flightPlan1->save();

        $data2 = $this->createBaseFlightPlanData();
        $data2['pilot_id'] = $this->pilotMad->id;
        $data2['aircraft_id'] = $this->lemdAircraft->id;
        $flightPlan2 = new SubmittedFlightPlan($data2);

        $this->assertFalse($flightPlan2->save());
        $this->assertArrayHasKey('pilot_id', $flightPlan2->errors);
    }

    public function testOnlyOneFPLPerAircraft()
    {
        $data1 = $this->createBaseFlightPlanData();
        $data1['aircraft_id'] = $this->lemdAircraft->id;
        $data1['pilot_id'] = $this->pilotMad->id;
        $flightPlan1 = new SubmittedFlightPlan($data1);
        $flightPlan1->save();

        $data2 = $this->createBaseFlightPlanData();
        $data2['aircraft_id'] = $this->lemdAircraft->id;
        $data2['pilot_id'] = $this->pilot2Mad->id;
        $flightPlan2 = new SubmittedFlightPlan($data2);

        $this->assertFalse($flightPlan2->save());
        $this->assertArrayHasKey('aircraft_id', $flightPlan2->errors);
    }

    public function testInvalidPilotIsInOtherLocation()
    {
        $data1 = $this->createBaseFlightPlanData();
        $data1['aircraft_id'] = $this->lemdAircraft->id;
        $data1['pilot_id'] = $this->pilotBcn->id;
        $flightPlan1 = new SubmittedFlightPlan($data1);

        $this->assertFalse($flightPlan1->save());
        $this->assertArrayHasKey('pilot_id', $flightPlan1->errors);
    }

    public function testInvalidAircraftIsInOtherLocation()
    {
        $data1 = $this->createBaseFlightPlanData();
        $data1['aircraft_id'] = $this->leblAircraft->id;
        $data1['pilot_id'] = $this->pilotMad->id;
        $flightPlan1 = new SubmittedFlightPlan($data1);

        $this->assertFalse($flightPlan1->save());
        $this->assertArrayHasKey('aircraft_id', $flightPlan1->errors);
    }

    public function testInvalidRouteLocationWithPilotAndAircraft()
    {
        $data1 = $this->createBaseFlightPlanData();
        $data1['aircraft_id'] = $this->lemdAircraft->id;
        $data1['pilot_id'] = $this->pilotMad->id;
        $data1['route_id'] = $this->routeVllBcn->id;
        $flightPlan1 = new SubmittedFlightPlan($data1);

        $this->assertFalse($flightPlan1->save());
        $this->assertArrayHasKey('pilot_id', $flightPlan1->errors);
        $this->assertArrayHasKey('aircraft_id', $flightPlan1->errors);
    }

    // ACARS / LiveFlightPosition tests

    public function testHasLiveFlightPositionReturnsFalseWhenNoPosition()
    {
        $data = $this->createBaseFlightPlanData();
        $flightPlan = new SubmittedFlightPlan($data);
        $flightPlan->save();

        $this->assertFalse($flightPlan->hasLiveFlightPosition());
    }

    public function testHasLiveFlightPositionReturnsTrueWhenPositionExists()
    {
        $data = $this->createBaseFlightPlanData();
        $flightPlan = new SubmittedFlightPlan($data);
        $flightPlan->save();

        $position = new LiveFlightPosition([
            'submitted_flight_plan_id' => $flightPlan->id,
            'latitude' => 40.4168,
            'longitude' => -3.7038,
            'altitude' => 35000,
            'heading' => 270,
            'ground_speed' => 450,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $position->save();

        // Refresh to reload relations
        $flightPlan->refresh();

        $this->assertTrue($flightPlan->hasLiveFlightPosition());
    }

    public function testIsAcarsActiveReturnsFalseWhenNoPosition()
    {
        $data = $this->createBaseFlightPlanData();
        $flightPlan = new SubmittedFlightPlan($data);
        $flightPlan->save();

        $this->assertFalse($flightPlan->isAcarsActive());
    }

    public function testIsAcarsActiveReturnsTrueWhenPositionIsRecent()
    {
        $data = $this->createBaseFlightPlanData();
        $flightPlan = new SubmittedFlightPlan($data);
        $flightPlan->save();

        $position = new LiveFlightPosition([
            'submitted_flight_plan_id' => $flightPlan->id,
            'latitude' => 40.4168,
            'longitude' => -3.7038,
            'altitude' => 35000,
            'heading' => 270,
            'ground_speed' => 450,
            'updated_at' => date('Y-m-d H:i:s'), // Now
        ]);
        $position->save();

        $flightPlan->refresh();

        $this->assertTrue($flightPlan->isAcarsActive());
    }

    public function testIsAcarsActiveReturnsFalseWhenPositionIsStale()
    {
        $data = $this->createBaseFlightPlanData();
        $flightPlan = new SubmittedFlightPlan($data);
        $flightPlan->save();

        $position = new LiveFlightPosition([
            'submitted_flight_plan_id' => $flightPlan->id,
            'latitude' => 40.4168,
            'longitude' => -3.7038,
            'altitude' => 35000,
            'heading' => 270,
            'ground_speed' => 450,
            'updated_at' => date('Y-m-d H:i:s', strtotime('-5 minutes')), // 5 min ago
        ]);
        $position->save();

        $flightPlan->refresh();

        $this->assertFalse($flightPlan->isAcarsActive());
    }

    public function testIsAcarsActiveRespectsCustomMinutesParameter()
    {
        $data = $this->createBaseFlightPlanData();
        $flightPlan = new SubmittedFlightPlan($data);
        $flightPlan->save();

        $position = new LiveFlightPosition([
            'submitted_flight_plan_id' => $flightPlan->id,
            'latitude' => 40.4168,
            'longitude' => -3.7038,
            'altitude' => 35000,
            'heading' => 270,
            'ground_speed' => 450,
            'updated_at' => date('Y-m-d H:i:s', strtotime('-3 minutes')), // 3 min ago
        ]);
        $position->save();

        $flightPlan->refresh();

        // With default 2 minutes, should be inactive
        $this->assertFalse($flightPlan->isAcarsActive(2));

        // With 5 minutes threshold, should be active
        $this->assertTrue($flightPlan->isAcarsActive(5));
    }

}
