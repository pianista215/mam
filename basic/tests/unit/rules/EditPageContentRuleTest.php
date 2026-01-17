<?php

namespace tests\unit\rbac;

use app\models\Airport;
use app\models\Country;
use app\models\Page;
use app\models\Pilot;
use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use app\rbac\rules\EditPageContentRule;
use tests\unit\BaseUnitTest;
use Yii;

class EditPageContentRuleTest extends BaseUnitTest
{
    protected function _before()
    {
        parent::_before();

        $auth = Yii::$app->authManager;
        $auth->removeAll();

        // Roles
        $adminRole = $auth->createRole(Roles::ADMIN);
        $auth->add($adminRole);

        // Permissions
        $tourCrud = $auth->createPermission(Permissions::TOUR_CRUD);
        $auth->add($tourCrud);

        // Rule
        $rule = new EditPageContentRule();
        $auth->add($rule);

        // Permission bound to rule
        $perm = $auth->createPermission('editPageContent');
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

        // Pilot base permission
        $this->createPilot(1);
    }

    private function createPilot(int $id): Pilot
    {
        $pilot = new Pilot([
            'id' => $id,
            'license' => 'LIC' . $id,
            'name' => 'Pilot',
            'surname' => 'Test',
            'email' => "pilot{$id}@example.com",
            'password' => Yii::$app->security->generatePasswordHash('pass'),
            'country_id' => 1,
            'city' => 'Madrid',
            'location' => 'LEMD',
            'date_of_birth' => '1990-01-01',
        ]);
        $pilot->save(false);

        Yii::$app->authManager->assign(
            Yii::$app->authManager->getPermission('editPageContent'),
            $id
        );

        return $pilot;
    }

    private function login(Pilot $pilot)
    {
        Yii::$app->user->logout(false);
        Yii::$app->user->login($pilot);
    }

    private function page(string $type): Page
    {
        return new Page([
            'code' => uniqid('page_'),
            'type' => $type,
        ]);
    }

    public function testGuestCannotEdit()
    {
        Yii::$app->user->logout(false);
        $page = $this->page(Page::TYPE_SITE);

        $this->assertFalse(
            Yii::$app->user->can('editPageContent', ['page' => $page])
        );
    }

    public function testTourPageWithoutPermission()
    {
        $pilot = $this->createPilot(2);
        $this->login($pilot);

        $page = $this->page(Page::TYPE_TOUR);

        $this->assertFalse(
            Yii::$app->user->can('editPageContent', ['page' => $page])
        );
    }

    public function testTourPageWithPermission()
    {
        $pilot = $this->createPilot(3);
        $this->login($pilot);

        Yii::$app->authManager->assign(
            Yii::$app->authManager->getPermission(Permissions::TOUR_CRUD),
            3
        );

        $page = $this->page(Page::TYPE_TOUR);

        $this->assertTrue(
            Yii::$app->user->can('editPageContent', ['page' => $page])
        );
    }

    public function testSitePageWithoutAdmin()
    {
        $pilot = $this->createPilot(4);
        $this->login($pilot);

        $page = $this->page(Page::TYPE_SITE);

        $this->assertFalse(
            Yii::$app->user->can('editPageContent', ['page' => $page])
        );
    }

    public function testSitePageWithAdmin()
    {
        $pilot = $this->createPilot(5);
        $this->login($pilot);

        Yii::$app->authManager->assign(
            Yii::$app->authManager->getRole(Roles::ADMIN),
            5
        );

        $page = $this->page(Page::TYPE_SITE);

        $this->assertTrue(
            Yii::$app->user->can('editPageContent', ['page' => $page])
        );
    }

    public function testComponentPageWithAdmin()
    {
        $pilot = $this->createPilot(6);
        $this->login($pilot);

        Yii::$app->authManager->assign(
            Yii::$app->authManager->getRole(Roles::ADMIN),
            6
        );

        $page = $this->page(Page::TYPE_COMPONENT);

        $this->assertTrue(
            Yii::$app->user->can('editPageContent', ['page' => $page])
        );
    }

    public function testInvalidPageParamFails()
    {
        $pilot = $this->createPilot(7);
        $this->login($pilot);

        $this->assertFalse(
            Yii::$app->user->can('editPageContent', ['page' => 'not_a_page'])
        );
    }
}
