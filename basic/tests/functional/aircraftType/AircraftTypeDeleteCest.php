<?php

namespace tests\functional\aircraftType;

use app\models\AircraftType;
use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\AuthAssignmentFixture;

class AircraftTypeDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraftType' => AircraftTypeFixture::class,
        ];
    }

    public function deleteAircraftTypeAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-type/view', ['id' => '1']);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-type/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = AircraftType::find()->count();
        $I->assertEquals(4, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft-type/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = AircraftType::find()->count();
        $I->assertEquals(4, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/delete', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function adminCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/aircraft-type/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = AircraftType::find()->where(['id' => 1])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/aircraft-type/delete?id=1');

        $I->seeResponseCodeIs(403);
        $count = AircraftType::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/aircraft-type/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = AircraftType::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }
}