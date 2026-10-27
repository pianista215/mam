<?php

namespace tests\unit\models;

use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftType;
use app\models\Airport;
use app\models\Assignment;
use app\models\Client;
use app\models\Country;
use app\models\Flight;
use app\models\Pilot;
use app\models\Route;
use app\models\SubmittedFlightPlan;
use tests\unit\BaseUnitTest;

class AssignmentTest extends BaseUnitTest
{
    protected Pilot $pilot;
    protected Pilot $otherPilot;
    protected Aircraft $aircraft;
    protected Aircraft $otherAircraft;
    protected Aircraft $farAircraft;
    protected Client $client;
    protected Client $otherClient;

    protected function _before()
    {
        parent::_before();
        $this->clearDatabase();

        $country = new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']);
        $this->assertTrue($country->save());

        foreach ([
            'LEMD' => [40.471926, -3.56264],
            'LEBL' => [41.2971, 2.0785],
            'LEVC' => [39.489444, -0.48166],
            'LEAL' => [38.28222, -0.55805],
        ] as $icao => [$lat, $lon]) {
            $this->assertTrue((new Airport([
                'icao_code' => $icao,
                'name' => "Airport $icao",
                'latitude' => $lat,
                'longitude' => $lon,
                'city' => $icao,
                'country_id' => $country->id,
            ]))->save());
        }

        $this->pilot = $this->createPilot('MAD12345', 'john.doe@example.com', 'LEMD');
        $this->otherPilot = $this->createPilot('MAD34567', 'john.doe2@example.com', 'LEMD');

        $type = new AircraftType(['icao_type_code' => 'B738', 'name' => 'Boeing 737-800', 'max_nm_range' => 2900]);
        $this->assertTrue($type->save());
        $config = new AircraftConfiguration([
            'aircraft_type_id' => $type->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
            'crew' => 5,
            'mtow' => 79016,
            'oew' => 41413,
        ]);
        $this->assertTrue($config->save());

        $this->aircraft = $this->createAircraft($config, 'EC-MAD', 'LEMD');
        $this->otherAircraft = $this->createAircraft($config, 'EC-MA2', 'LEMD');
        $this->farAircraft = $this->createAircraft($config, 'EC-BCN', 'LEBL');

        $this->client = new Client(['name' => 'Iberian Logistics', 'headquarters_icao' => 'LEMD']);
        $this->assertTrue($this->client->save());
        $this->otherClient = new Client(['name' => 'Levante Cargo', 'headquarters_icao' => 'LEVC']);
        $this->assertTrue($this->otherClient->save());
    }

    private function createPilot(string $license, string $email, string $location): Pilot
    {
        $pilot = new Pilot([
            'license' => $license,
            'name' => 'John',
            'surname' => 'Doe',
            'email' => $email,
            'password' => '$2y$10$Y1cO2xBAETW/UtvLwWcNkO0UgQTbuisG1n65xgc8kMgwfIv.TayjW', // SecurePass123!
            'country_id' => 1,
            'city' => 'Madrid',
            'location' => $location,
            'date_of_birth' => '1990-01-01',
        ]);
        $this->assertTrue($pilot->save(), json_encode($pilot->errors));
        return $pilot;
    }

    private function createAircraft(AircraftConfiguration $config, string $registration, string $location): Aircraft
    {
        $aircraft = new Aircraft([
            'aircraft_configuration_id' => $config->id,
            'registration' => $registration,
            'name' => $registration,
            'location' => $location,
            'hours_flown' => 0,
        ]);
        $this->assertTrue($aircraft->save(), json_encode($aircraft->errors));
        return $aircraft;
    }

    private function createAssignment(array $attributes = []): Assignment
    {
        $assignment = new Assignment(array_merge([
            'pilot_id' => $this->pilot->id,
            'aircraft_id' => $this->aircraft->id,
            'client_id' => $this->client->id,
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'distance_nm' => 261,
        ], $attributes));
        $this->assertTrue($assignment->save(), json_encode($assignment->errors));
        return $assignment;
    }

    private function createFlightPlan(Assignment $assignment): SubmittedFlightPlan
    {
        $fpl = new SubmittedFlightPlan([
            'aircraft_id' => $assignment->aircraft_id,
            'pilot_id' => $assignment->pilot_id,
            'assignment_id' => $assignment->id,
            'flight_rules' => 'I',
            'alternative1_icao' => 'LEVC',
            'cruise_speed_value' => '450',
            'cruise_speed_unit' => 'N',
            'flight_level_value' => '360',
            'flight_level_unit' => 'F',
            'route' => 'DCT',
            'estimated_time' => '0100',
            'other_information' => 'DOF/20261027',
            'endurance_time' => '0300',
        ]);
        $this->assertTrue($fpl->save(), json_encode($fpl->errors));
        return $fpl;
    }

    private function createAssignmentFlight(Client $client): Flight
    {
        $flight = new Flight([
            'pilot_id' => $this->pilot->id,
            'aircraft_id' => $this->aircraft->id,
            'client_id' => $client->id,
            'code' => 'ASSIGNMENT',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'alternative1_icao' => 'LEVC',
            'flight_rules' => 'I',
            'cruise_speed_value' => '450',
            'cruise_speed_unit' => 'N',
            'flight_level_value' => '360',
            'flight_level_unit' => 'F',
            'route' => 'DCT',
            'estimated_time' => '0100',
            'other_information' => 'DOF/20261027',
            'endurance_time' => '0300',
            'report_tool' => 'Mam Acars 1.0',
            'flight_type' => Flight::TYPE_ASSIGNMENT,
        ]);
        $this->assertTrue($flight->save(), json_encode($flight->errors));
        return $flight;
    }

    // Validation

    public function testValidAssignment()
    {
        $assignment = $this->createAssignment(['departure' => 'lemd', 'arrival' => 'lebl']);

        $this->assertNotEmpty($assignment->id);
        $this->assertEquals('LEMD', $assignment->departure);
        $this->assertEquals('LEBL', $assignment->arrival);
        $this->assertNotEmpty(Assignment::findOne($assignment->id)->created_at);
    }

    public function testRequiredFields()
    {
        $assignment = new Assignment();

        $this->assertFalse($assignment->validate());
        foreach (['pilot_id', 'aircraft_id', 'client_id', 'departure', 'arrival', 'distance_nm'] as $attribute) {
            $this->assertArrayHasKey($attribute, $assignment->errors, "Missing error for $attribute");
        }
    }

    public function testInvalidReferences()
    {
        $assignment = new Assignment([
            'pilot_id' => 999999,
            'aircraft_id' => 999999,
            'client_id' => 999999,
            'departure' => 'XXXX',
            'arrival' => 'YYYY',
            'distance_nm' => 100,
        ]);

        $this->assertFalse($assignment->validate());
        foreach (['pilot_id', 'aircraft_id', 'client_id', 'departure', 'arrival'] as $attribute) {
            $this->assertArrayHasKey($attribute, $assignment->errors, "Missing error for $attribute");
        }
    }

    public function testArrivalMustDifferFromDeparture()
    {
        $assignment = new Assignment([
            'pilot_id' => $this->pilot->id,
            'aircraft_id' => $this->aircraft->id,
            'client_id' => $this->client->id,
            'departure' => 'LEMD',
            'arrival' => 'LEMD',
            'distance_nm' => 0,
        ]);

        $this->assertFalse($assignment->validate());
        $this->assertArrayHasKey('arrival', $assignment->errors);
    }

    public function testNegativeDistanceIsInvalid()
    {
        $assignment = new Assignment([
            'pilot_id' => $this->pilot->id,
            'aircraft_id' => $this->aircraft->id,
            'client_id' => $this->client->id,
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'distance_nm' => -1,
        ]);

        $this->assertFalse($assignment->validate());
        $this->assertArrayHasKey('distance_nm', $assignment->errors);
    }

    // Relations and description

    public function testRelations()
    {
        $assignment = $this->createAssignment();

        $this->assertEquals($this->pilot->id, $assignment->pilot->id);
        $this->assertEquals($this->aircraft->id, $assignment->aircraft->id);
        $this->assertEquals($this->client->id, $assignment->client->id);
        $this->assertEquals('LEMD', $assignment->departure0->icao_code);
        $this->assertEquals('LEBL', $assignment->arrival0->icao_code);
        $this->assertNull($assignment->submittedFlightPlan);

        $fpl = $this->createFlightPlan($assignment);
        unset($assignment->submittedFlightPlan);
        $this->assertEquals($fpl->id, $assignment->submittedFlightPlan->id);

        $this->assertEquals([$assignment->id], array_map(fn($a) => $a->id, $this->client->assignments));
    }

    public function testFplDescription()
    {
        $assignment = $this->createAssignment();

        $this->assertEquals('Assignment: Iberian Logistics (LEMD-LEBL)', $assignment->fplDescription);
    }

    // findAvailableForPilot

    public function testFindAvailableForPilotReturnsOnlyBookableAssignments()
    {
        $far = $this->createAssignment(['arrival' => 'LEAL', 'distance_nm' => 230]);
        $near = $this->createAssignment(['arrival' => 'LEVC', 'distance_nm' => 150, 'client_id' => $this->otherClient->id]);
        $tie = $this->createAssignment(['arrival' => 'LEBL', 'distance_nm' => 230]);

        // Not available: another pilot's, aircraft elsewhere, departure elsewhere
        $this->createAssignment(['pilot_id' => $this->otherPilot->id, 'aircraft_id' => $this->otherAircraft->id]);
        $this->createAssignment(['aircraft_id' => $this->farAircraft->id]);
        $this->createAssignment(['aircraft_id' => $this->farAircraft->id, 'departure' => 'LEBL', 'arrival' => 'LEMD']);

        $available = Assignment::findAvailableForPilot($this->pilot);

        // Sorted by distance, then id
        $this->assertEquals([$near->id, $far->id, $tie->id], array_map(fn($a) => $a->id, $available));
        $this->assertTrue($available[0]->isRelationPopulated('client'));
        $this->assertTrue($available[0]->isRelationPopulated('aircraft'));
        $this->assertTrue($available[0]->isRelationPopulated('arrival0'));
    }

    public function testFindAvailableForPilotExcludesReservedAircraft()
    {
        $assignment = $this->createAssignment();

        // Another pilot books a regular route with the same aircraft
        $route = new Route(['code' => 'MAD-BCN', 'departure' => 'LEMD', 'arrival' => 'LEBL']);
        $this->assertTrue($route->save());
        $fpl = new SubmittedFlightPlan([
            'aircraft_id' => $this->aircraft->id,
            'pilot_id' => $this->otherPilot->id,
            'route_id' => $route->id,
            'flight_rules' => 'I',
            'alternative1_icao' => 'LEVC',
            'cruise_speed_value' => '450',
            'cruise_speed_unit' => 'N',
            'flight_level_value' => '360',
            'flight_level_unit' => 'F',
            'route' => 'DCT',
            'estimated_time' => '0100',
            'other_information' => 'DOF/20261027',
            'endurance_time' => '0300',
        ]);
        $this->assertTrue($fpl->save(), json_encode($fpl->errors));

        $this->assertSame([], Assignment::findAvailableForPilot($this->pilot));

        $fpl->delete();
        $this->assertEquals([$assignment->id], array_map(fn($a) => $a->id, Assignment::findAvailableForPilot($this->pilot)));
    }

    public function testFindAvailableForPilotExcludesBookedAssignment()
    {
        $booked = $this->createAssignment();
        $this->createFlightPlan($booked);

        $this->assertSame([], Assignment::findAvailableForPilot($this->pilot));
    }

    public function testFindAvailableForPilotWithoutAssignmentsOrLocation()
    {
        $this->assertSame([], Assignment::findAvailableForPilot($this->pilot));

        $this->createAssignment();
        $this->pilot->location = null;
        $this->assertSame([], Assignment::findAvailableForPilot($this->pilot));
    }

    public function testFindAvailableForPilotAfterPilotMoves()
    {
        $this->createAssignment();
        $this->pilot->location = 'LEBL';
        $this->assertTrue($this->pilot->save(false));

        $this->assertSame([], Assignment::findAvailableForPilot($this->pilot));
    }

    // Foreign keys

    public function testDeletingPilotDeletesAssignments()
    {
        $this->createAssignment();
        $this->createAssignment(['arrival' => 'LEVC']);
        $kept = $this->createAssignment(['pilot_id' => $this->otherPilot->id, 'aircraft_id' => $this->otherAircraft->id]);

        $this->pilot->delete();

        $this->assertEquals([$kept->id], Assignment::find()->select('id')->column());
    }

    public function testDeletingAircraftDeletesAssignments()
    {
        $this->createAssignment();
        $kept = $this->createAssignment(['aircraft_id' => $this->otherAircraft->id]);

        $this->aircraft->delete();

        $this->assertEquals([$kept->id], Assignment::find()->select('id')->column());
    }

    public function testDeletingClientDeletesItsAssignments()
    {
        $this->createAssignment();
        $kept = $this->createAssignment(['client_id' => $this->otherClient->id]);

        $this->assertTrue($this->client->isDeletable());
        $this->client->delete();

        $this->assertEquals([$kept->id], Assignment::find()->select('id')->column());
    }

    public function testClientWithBookedAssignmentIsNotDeletable()
    {
        $this->createFlightPlan($this->createAssignment());

        $this->assertFalse($this->client->isDeletable());
        $this->assertTrue($this->otherClient->isDeletable());

        $this->expectException(\yii\db\IntegrityException::class);
        $this->client->delete();
    }

    public function testClientWithFlightsIsNotDeletable()
    {
        $flight = $this->createAssignmentFlight($this->client);

        $this->assertFalse($this->client->isDeletable());
        $this->assertEquals([$flight->id], array_map(fn($f) => $f->id, $this->client->flights));

        $this->expectException(\yii\db\IntegrityException::class);
        $this->client->delete();
    }

    public function testBookedAssignmentCannotBeDeleted()
    {
        $assignment = $this->createAssignment();
        $this->createFlightPlan($assignment);

        $this->expectException(\yii\db\IntegrityException::class);
        $assignment->delete();
    }

    public function testCreateFromProposals()
    {
        $assignments = Assignment::createFromProposals($this->pilot->id, $this->aircraft, [
            ['destination' => 'LEBL', 'client_id' => $this->client->id, 'distance_nm' => 261],
            ['destination' => 'LEVC', 'client_id' => $this->otherClient->id, 'distance_nm' => 155],
        ]);

        $this->assertCount(2, $assignments);
        $saved = Assignment::find()->where(['pilot_id' => $this->pilot->id])->orderBy(['arrival' => SORT_ASC])->all();
        $this->assertCount(2, $saved);
        foreach ($saved as $assignment) {
            $this->assertEquals($this->aircraft->id, $assignment->aircraft_id);
            $this->assertEquals('LEMD', $assignment->departure);
        }
        $this->assertEquals('LEBL', $saved[0]->arrival);
        $this->assertEquals($this->client->id, $saved[0]->client_id);
        $this->assertEquals(261, $saved[0]->distance_nm);
        $this->assertEquals('LEVC', $saved[1]->arrival);
        $this->assertEquals($this->otherClient->id, $saved[1]->client_id);
        $this->assertEquals(155, $saved[1]->distance_nm);
    }

    public function testCreateFromProposalsDepartsFromAircraftLocation()
    {
        $assignments = Assignment::createFromProposals($this->pilot->id, $this->farAircraft, [
            ['destination' => 'LEMD', 'client_id' => $this->client->id, 'distance_nm' => 261],
        ]);

        $this->assertEquals('LEBL', $assignments[0]->departure);
    }

    public function testCreateFromProposalsWithoutProposals()
    {
        $this->assertSame([], Assignment::createFromProposals($this->pilot->id, $this->aircraft, []));
        $this->assertEquals(0, Assignment::find()->count());
    }

    public function testCreateFromProposalsThrowsOnInvalidProposal()
    {
        $this->expectException(\RuntimeException::class);
        Assignment::createFromProposals($this->pilot->id, $this->aircraft, [
            ['destination' => 'LEMD', 'client_id' => $this->client->id, 'distance_nm' => 0],
        ]);
    }

    public function testCreateFromProposalsThrowsOnUnknownClient()
    {
        $this->expectException(\RuntimeException::class);
        Assignment::createFromProposals($this->pilot->id, $this->aircraft, [
            ['destination' => 'LEBL', 'client_id' => 9999, 'distance_nm' => 261],
        ]);
    }
}
