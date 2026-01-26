<?php

namespace tests\functional\aircraft;

use app\models\Aircraft;
use tests\fixtures\AircraftFixture;
use tests\fixtures\AuthAssignmentFixture;

class AircraftDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
        ];
    }

    public function deleteAircraftAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/view', ['id' => '1']);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Aircraft::find()->count();
        $I->assertEquals(10, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Aircraft::find()->count();
        $I->assertEquals(10, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft/delete', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function adminCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/aircraft/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = Aircraft::find()->where(['id' => 1])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/aircraft/delete?id=1');

        $I->seeResponseCodeIs(403);
        $count = Aircraft::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/aircraft/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = Aircraft::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }
}