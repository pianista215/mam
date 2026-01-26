<?php

namespace tests\unit\rules;

use app\models\Airport;
use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftType;
use app\models\Country;
use app\models\Flight;
use app\models\Pilot;
use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use app\rbac\rules\FlightDeletionRule;
use tests\unit\BaseUnitTest;
use Yii;

class FlightDeletionRuleTest extends BaseUnitTest
{
    private Country $country;
    private Airport $airport;
    private Aircraft $aircraft;

    protected function _before()
    {
        parent::_before();

        $auth = Yii::$app->authManager;
        $auth->removeAll();

        $pilotRole = $auth->createRole(Roles::PILOT);
        $auth->add($pilotRole);

        $rule = new FlightDeletionRule();
        $auth->add($rule);

        $perm = $auth->createPermission(Permissions::DELETE_OWN_FLIGHT);
        $perm->ruleName = $rule->name;
        $auth->add($perm);

        $auth->addChild($pilotRole, $perm);

        $this->country = new Country([
            'id' => 1,
            'name' => 'Spain',
            'iso2_code' => 'ES'
        ]);
        $this->country->save(false);

        $this->airport = new Airport([
            'id' => 1,
            'icao_code' => 'LEMD',
            'name' => 'Madrid',
            'latitude' => 40.0,
            'longitude' => -3.0,
            'city' => 'Madrid',
            'country_id' => 1
        ]);
        $this->airport->save(false);

        $aircraftType = new AircraftType([
            'id' => 1,
            'icao_type_code' => 'A320',
            'name' => 'Airbus A320'
        ]);
        $aircraftType->save(false);

        $aircraftConfig = new AircraftConfiguration([
            'id' => 1,
            'aircraft_type_id' => 1,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 1000
        ]);
        $aircraftConfig->save(false);

        $this->aircraft = new Aircraft([
            'id' => 1,
            'registration' => 'EC-ABC',
            'aircraft_configuration_id' => 1,
            'location' => 'LEMD'
        ]);
        $this->aircraft->save(false);
    }

    private function createPilot(int $id): Pilot
    {
        $pilot = new Pilot([
            'id' => $id,
            'license' => 'TEST' . $id,
            'name' => 'Pilot',
            'surname' => 'Test',
            'email' => "pilot{$id}@example.com",
            'password' => Yii::$app->security->generatePasswordHash('pass123'),
            'country_id' => 1,
            'city' => 'Madrid',
            'location' => 'LEMD',
            'date_of_birth' => '1990-01-01',
        ]);
        $pilot->save(false);

        Yii::$app->authManager->assign(
            Yii::$app->authManager->getRole(Roles::PILOT),
            $id
        );

        return $pilot;
    }

    private function createFlight(int $id, int $pilotId, string $status, ?string $creationDate = null): Flight
    {
        $flight = new Flight([
            'id' => $id,
            'pilot_id' => $pilotId,
            'aircraft_id' => 1,
            'code' => 'F' . str_pad($id, 3, '0', STR_PAD_LEFT),
            'departure' => 'LEMD',
            'arrival' => 'LEMD',
            'alternative1_icao' => 'LEMD',
            'flight_rules' => 'I',
            'cruise_speed_unit' => 'N',
            'cruise_speed_value' => '350',
            'flight_level_unit' => 'F',
            'flight_level_value' => '320',
            'route' => 'DCT',
            'estimated_time' => '0100',
            'other_information' => 'TEST',
            'endurance_time' => '0200',
            'report_tool' => 'Test',
            'status' => $status,
            'creation_date' => $creationDate ?? date('Y-m-d H:i:s'),
            'flight_type' => 'R'
        ]);
        $flight->save(false);
        return $flight;
    }

    private function login(Pilot $pilot): void
    {
        Yii::$app->user->logout(false);
        Yii::$app->user->login($pilot);
    }

    public function testGuestCannotDeleteFlight()
    {
        Yii::$app->user->logout(false);
        $pilot = $this->createPilot(1);
        $flight = $this->createFlight(1, 1, Flight::STATUS_PENDING_VALIDATION);

        $this->assertFalse(Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => $flight]));
    }

    public function testOwnerCanDeletePendingValidationFlight()
    {
        $pilot = $this->createPilot(1);
        $this->login($pilot);
        $flight = $this->createFlight(1, 1, Flight::STATUS_PENDING_VALIDATION);

        $this->assertTrue(Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => $flight]));
    }

    public function testOwnerCannotDeleteFinishedFlight()
    {
        $pilot = $this->createPilot(1);
        $this->login($pilot);
        $flight = $this->createFlight(1, 1, Flight::STATUS_FINISHED);

        $this->assertFalse(Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => $flight]));
    }

    public function testOwnerCannotDeleteRejectedFlight()
    {
        $pilot = $this->createPilot(1);
        $this->login($pilot);
        $flight = $this->createFlight(1, 1, Flight::STATUS_REJECTED);

        $this->assertFalse(Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => $flight]));
    }

    public function testOwnerCannotDeleteSubmittedFlight()
    {
        $pilot = $this->createPilot(1);
        $this->login($pilot);
        $flight = $this->createFlight(1, 1, Flight::STATUS_SUBMITTED);

        $this->assertFalse(Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => $flight]));
    }

    public function testOwnerCannotDeleteRecentCreatedFlight()
    {
        $pilot = $this->createPilot(1);
        $this->login($pilot);
        $flight = $this->createFlight(1, 1, Flight::STATUS_CREATED, date('Y-m-d H:i:s'));

        $this->assertFalse(Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => $flight]));
    }

    public function testOwnerCanDeleteOldCreatedFlight()
    {
        $pilot = $this->createPilot(1);
        $this->login($pilot);
        $oldDate = (new \DateTime())->modify('-4 days')->format('Y-m-d H:i:s');
        $flight = $this->createFlight(1, 1, Flight::STATUS_CREATED, $oldDate);

        $this->assertTrue(Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => $flight]));
    }

    public function testNonOwnerCannotDeletePendingValidationFlight()
    {
        $owner = $this->createPilot(1);
        $other = $this->createPilot(2);
        $this->login($other);
        $flight = $this->createFlight(1, 1, Flight::STATUS_PENDING_VALIDATION);

        $this->assertFalse(Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => $flight]));
    }

    public function testNullFlightReturnsFalse()
    {
        $pilot = $this->createPilot(1);
        $this->login($pilot);

        $this->assertFalse(Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => null]));
    }

    public function testMissingFlightParamReturnsFalse()
    {
        $pilot = $this->createPilot(1);
        $this->login($pilot);

        $this->assertFalse(Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, []));
    }
}
