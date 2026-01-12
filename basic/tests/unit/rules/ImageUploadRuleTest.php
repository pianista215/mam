<?php

namespace tests\unit\rbac;

use app\models\Airport;
use app\models\Country;
use app\models\Image;
use app\models\Pilot;
use app\rbac\constants\Roles;
use app\rbac\rules\ImageUploadRule;
use tests\unit\BaseUnitTest;
use Yii;

class ImageUploadRuleTest extends BaseUnitTest
{

    protected function _before()
    {
        parent::_before();

        $auth = Yii::$app->authManager;
        $auth->removeAll();

        $adminRole = $auth->createRole(Roles::ADMIN);
        $auth->add($adminRole);

        foreach (['rankCrud', 'tourCrud', 'countryCrud', 'aircraftTypeCrud', 'userCrud'] as $perm) {
            $p = $auth->createPermission($perm);
            $auth->add($p);
        }

        $rule = new ImageUploadRule();
        $auth->add($rule);

        $perm = $auth->createPermission('imageUpload');
        $perm->ruleName = $rule->name;
        $auth->add($perm);

        $country = new Country([
            'id' => 1,
            'name' => 'Spain',
            'iso2_code' => 'ES'
        ]);
        $country->save(false);
        $airport = new Airport([
            'id' => 1,
            'icao_code' => 'LEMD',
            'name' => 'Madrid',
            'latitude' => 0.0,
            'longitude' => 0.0,
            'city' => 'Madrid',
            'country_id' => 1
        ]);
        $airport->save(false);
    }

    private function createPilot($idOverride = null)
    {
        $pilotData = [
            'license' => 'TEST' . rand(1000, 9999),
            'name' => 'John',
            'surname' => 'Doe',
            'email' => uniqid('p') . '@example.com',
            'password' => Yii::$app->security->generatePasswordHash('pass123'),
            'country_id' => 1,
            'city' => 'Madrid',
            'location' => 'LEMD',
            'date_of_birth' => '1990-01-01',
        ];

        if ($idOverride !== null) {
            $pilotData['id'] = $idOverride;
        }

        $pilot = new Pilot($pilotData);
        $pilot->save(false);

        // Need base permission imageUpload to work
        $this->assign('imageUpload', $pilot->id);

        return $pilot;
    }

    private function login(Pilot $pilot)
    {
        Yii::$app->user->logout(false);
        Yii::$app->user->login($pilot);
    }

    private function assign($perm, $userId)
    {
        Yii::$app->authManager->assign(
            Yii::$app->authManager->getPermission($perm),
            $userId
        );
    }

    private function img($type, $relatedId)
    {
        $img = new Image();
        $img->type = $type;
        $img->related_id = $relatedId;
        return $img;
    }

    public function testGuestCannotUpload()
    {
        Yii::$app->user->logout(false);
        $img = $this->img('rank_icon', 1);

        $this->assertFalse(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testUnknownTypeReturnsFalse()
    {
        $pilot = $this->createPilot(1);
        $this->login($pilot);

        $img = $this->img('nonexistent_type', 1);

        $this->assertFalse(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }


    public function testRankIconWithoutPermission()
    {
        $pilot = $this->createPilot(5);
        $this->login($pilot);

        $img = $this->img('rank_icon', 1);

        $this->assertFalse(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testRankIconWithPermission()
    {
        $pilot = $this->createPilot(5);
        $this->login($pilot);
        $this->assign('rankCrud', 5);

        $img = $this->img('rank_icon', 1);

        $this->assertTrue(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }


    public function testTourImageWithoutPermission()
    {
        $pilot = $this->createPilot(7);
        $this->login($pilot);

        $img = $this->img('tour_image', 55);

        $this->assertFalse(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testTourImageWithPermission()
    {
        $pilot = $this->createPilot(7);
        $this->login($pilot);
        $this->assign('tourCrud', 7);

        $img = $this->img('tour_image', 55);

        $this->assertTrue(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }


    public function testCountryIconWithoutPermission()
    {
        $pilot = $this->createPilot(9);
        $this->login($pilot);

        $img = $this->img('country_icon', 34);

        $this->assertFalse(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testCountryIconWithPermission()
    {
        $pilot = $this->createPilot(9);
        $this->login($pilot);
        $this->assign('countryCrud', 9);

        $img = $this->img('country_icon', 34);

        $this->assertTrue(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testAircraftTypeWithoutPermission()
    {
        $pilot = $this->createPilot(10);
        $this->login($pilot);

        $img = $this->img('aircraftType_image', 99);

        $this->assertFalse(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testAircraftTypeWithPermission()
    {
        $pilot = $this->createPilot(10);
        $this->login($pilot);
        $this->assign('aircraftTypeCrud', 10);

        $img = $this->img('aircraftType_image', 99);

        $this->assertTrue(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testPageWithoutAdmin()
    {
        $pilot = $this->createPilot(11);
        $this->login($pilot);

        $img = $this->img('page_image', 1);

        $this->assertFalse(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testPageWithAdminRole()
    {
        $pilot = $this->createPilot(11);
        $this->login($pilot);

        Yii::$app->authManager->assign(
            Yii::$app->authManager->getRole(Roles::ADMIN),
            11
        );

        $img = $this->img('page_image', 1);

        $this->assertTrue(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testPilotCanEditOwnProfile()
    {
        $pilot = $this->createPilot(20);
        $this->login($pilot);

        $img = $this->img('pilot_profile', 20);

        $this->assertTrue(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testPilotCannotEditOtherProfile()
    {
        $pilot = $this->createPilot(20);
        $this->login($pilot);

        $img = $this->img('pilot_profile', 999);

        $this->assertFalse(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testPilotWithUserCrudCanEditAnyProfile()
    {
        $pilot = $this->createPilot(20);
        $this->login($pilot);
        $this->assign('userCrud', 20);

        $img = $this->img('pilot_profile', 999);

        $this->assertTrue(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }

    public function testArrayImageMustFail()
    {
        $pilot = $this->createPilot(33);
        $this->login($pilot);
        $this->assign('rankCrud', 33);

        $img = [
            'type' => 'rank_icon',
            'related_id' => 123
        ];

        $this->assertFalse(Yii::$app->user->can('imageUpload', ['image' => $img]));
    }
}
