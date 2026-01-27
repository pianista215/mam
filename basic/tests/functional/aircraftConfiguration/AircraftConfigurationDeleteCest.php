<?php

namespace tests\functional\aircraftConfiguration;

use app\models\AircraftConfiguration;
use tests\fixtures\AircraftFixture;
use tests\fixtures\AuthAssignmentFixture;

class AircraftConfigurationDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
        ];
    }

    public function deleteAircraftConfigurationAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-configuration/view', ['id' => '1']);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-configuration/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = AircraftConfiguration::find()->count();
        $I->assertEquals(4, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft-configuration/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = AircraftConfiguration::find()->count();
        $I->assertEquals(4, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-configuration/delete', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
        $count = AircraftConfiguration::find()->count();
        $I->assertEquals(4, $count);
    }

    // Use configuration id=4 (VIP) which has no aircraft associated
    public function adminCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/aircraft-configuration/delete?id=4');

        $I->seeResponseCodeIsRedirection();
        $count = AircraftConfiguration::find()->where(['id' => 4])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/aircraft-configuration/delete?id=4');

        $I->seeResponseCodeIs(403);
        $count = AircraftConfiguration::find()->where(['id' => 4])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/aircraft-configuration/delete?id=4');

        $I->seeResponseCodeIsRedirection();
        $count = AircraftConfiguration::find()->where(['id' => 4])->count();
        $I->assertEquals(1, $count);
    }
}