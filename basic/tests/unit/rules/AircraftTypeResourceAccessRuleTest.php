<?php

namespace tests\unit\rbac;

use app\models\Airport;
use app\models\AircraftType;
use app\models\Country;
use app\models\CredentialType;
use app\models\CredentialTypeAircraftType;
use app\models\Pilot;
use app\models\PilotCredential;
use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use app\rbac\rules\AircraftTypeResourceAccessRule;
use tests\unit\BaseUnitTest;
use Yii;

class AircraftTypeResourceAccessRuleTest extends BaseUnitTest
{
    private AircraftType $aircraftType;

    protected function _before()
    {
        parent::_before();

        $this->clearCredentialData();

        $auth = Yii::$app->authManager;
        $auth->removeAll();

        $rule = new AircraftTypeResourceAccessRule();
        $auth->add($rule);

        $accessPerm = $auth->createPermission(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES);
        $accessPerm->ruleName = $rule->name;
        $auth->add($accessPerm);

        $crudPerm = $auth->createPermission(Permissions::AIRCRAFT_TYPE_RESOURCE_CRUD);
        $auth->add($crudPerm);

        $country = new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']);
        $country->save(false);

        $airport = new Airport([
            'id' => 1, 'icao_code' => 'LEMD', 'name' => 'Madrid',
            'latitude' => 0.0, 'longitude' => 0.0, 'city' => 'Madrid', 'country_id' => 1,
        ]);
        $airport->save(false);

        $this->aircraftType = new AircraftType(['icao_type_code' => 'B738', 'name' => 'B737-800', 'max_nm_range' => 2900]);
        $this->aircraftType->save(false);
    }

    private function clearCredentialData()
    {
        $db = Yii::$app->db;
        $db->createCommand()->delete('pilot_credential')->execute();
        $db->createCommand()->delete('credential_type_aircraft_type')->execute();
        $db->createCommand()->delete('credential_type_prerequisite')->execute();
        $db->createCommand()->delete('credential_type')->execute();
    }

    private function createPilot(int $id): Pilot
    {
        $pilot = new Pilot([
            'id'          => $id,
            'license'     => 'TST' . $id,
            'name'        => 'Test',
            'surname'     => 'Pilot',
            'email'       => "p{$id}@test.com",
            'password'    => Yii::$app->security->generatePasswordHash('pass'),
            'country_id'  => 1,
            'city'        => 'Madrid',
            'location'    => 'LEMD',
            'date_of_birth' => '1990-01-01',
        ]);
        $pilot->save(false);
        return $pilot;
    }

    private function createCredentialType(): CredentialType
    {
        $ct = new CredentialType(['code' => 'B738', 'name' => 'B738 Type Rating', 'type' => CredentialType::TYPE_RATING]);
        $ct->save(false);
        return $ct;
    }

    private function login(Pilot $pilot): void
    {
        Yii::$app->user->logout(false);
        Yii::$app->user->login($pilot);
    }

    private function assignPermission(string $perm, int $userId): void
    {
        Yii::$app->authManager->assign(Yii::$app->authManager->getPermission($perm), $userId);
    }

    public function testGuestCannotAccess()
    {
        Yii::$app->user->logout(false);

        $this->assertFalse(Yii::$app->user->can(
            Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES,
            ['aircraft_type_id' => $this->aircraftType->id]
        ));
    }

    public function testResourceManagerCanAccessWithoutCredential()
    {
        $pilot = $this->createPilot(1);
        $this->login($pilot);
        $this->assignPermission(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES, $pilot->id);
        $this->assignPermission(Permissions::AIRCRAFT_TYPE_RESOURCE_CRUD, $pilot->id);

        $this->assertTrue(Yii::$app->user->can(
            Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES,
            ['aircraft_type_id' => $this->aircraftType->id]
        ));
    }

    public function testPilotWithCredentialCanAccess()
    {
        $pilot = $this->createPilot(2);
        $this->login($pilot);
        $this->assignPermission(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES, $pilot->id);

        $ct = $this->createCredentialType();

        $link = new CredentialTypeAircraftType([
            'credential_type_id' => $ct->id,
            'aircraft_type_id'   => $this->aircraftType->id,
        ]);
        $link->save(false);

        $cred = new PilotCredential([
            'pilot_id'           => $pilot->id,
            'credential_type_id' => $ct->id,
            'status'             => PilotCredential::STATUS_ACTIVE,
            'issued_date'        => '2025-01-01',
            'issued_by'          => $pilot->id,
        ]);
        $cred->save(false);

        $this->assertTrue(Yii::$app->user->can(
            Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES,
            ['aircraft_type_id' => $this->aircraftType->id]
        ));
    }

    public function testPilotWithoutCredentialCannotAccess()
    {
        $pilot = $this->createPilot(3);
        $this->login($pilot);
        $this->assignPermission(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES, $pilot->id);

        $ct = $this->createCredentialType();
        $link = new CredentialTypeAircraftType([
            'credential_type_id' => $ct->id,
            'aircraft_type_id'   => $this->aircraftType->id,
        ]);
        $link->save(false);

        $this->assertFalse(Yii::$app->user->can(
            Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES,
            ['aircraft_type_id' => $this->aircraftType->id]
        ));
    }

    public function testStudentCredentialGrantsAccess()
    {
        $pilot = $this->createPilot(4);
        $this->login($pilot);
        $this->assignPermission(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES, $pilot->id);

        $ct   = $this->createCredentialType();
        $link = new CredentialTypeAircraftType([
            'credential_type_id' => $ct->id,
            'aircraft_type_id'   => $this->aircraftType->id,
        ]);
        $link->save(false);

        $cred = new PilotCredential([
            'pilot_id'           => $pilot->id,
            'credential_type_id' => $ct->id,
            'status'             => PilotCredential::STATUS_STUDENT,
            'issued_date'        => '2025-01-01',
            'issued_by'          => $pilot->id,
        ]);
        $cred->save(false);

        $this->assertTrue(Yii::$app->user->can(
            Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES,
            ['aircraft_type_id' => $this->aircraftType->id]
        ));
    }

    public function testMissingAircraftTypeIdParamReturnsFalse()
    {
        $pilot = $this->createPilot(5);
        $this->login($pilot);
        $this->assignPermission(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES, $pilot->id);

        $this->assertFalse(Yii::$app->user->can(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES, []));
    }

    public function testNoCredentialTypesLinkedReturnsFalse()
    {
        $pilot = $this->createPilot(6);
        $this->login($pilot);
        $this->assignPermission(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES, $pilot->id);

        $this->assertFalse(Yii::$app->user->can(
            Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES,
            ['aircraft_type_id' => $this->aircraftType->id]
        ));
    }
}
