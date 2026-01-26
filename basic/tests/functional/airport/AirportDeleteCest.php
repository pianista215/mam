<?php

namespace tests\functional\airport;

use app\models\Airport;
use tests\fixtures\AuthAssignmentFixture;

class AirportDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function deleteAirportAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('airport/view', ['id' => '1']);

        $I->see('Delete');
    }

    public function deleteAirportAsAirportManager(\FunctionalTester $I)
    {
        $I->amLoggedInAs(12);
        $I->amOnRoute('airport/view', ['id' => '1']);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('airport/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Airport::find()->count();
        $I->assertEquals(6, $count);
    }

    public function deleteOnlyPostAsAirportManager(\FunctionalTester $I)
    {
        $I->amLoggedInAs(12);
        $I->amOnRoute('airport/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Airport::find()->count();
        $I->assertEquals(6, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('airport/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Airport::find()->count();
        $I->assertEquals(6, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('airport/delete', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    // Use airport id=6 (Zaragoza) which has no pilots or routes associated
    public function adminCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/airport/delete?id=6');

        $I->seeResponseCodeIsRedirection();
        $count = Airport::find()->where(['id' => 6])->count();
        $I->assertEquals(0, $count);
    }

    public function airportManagerCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(12);
        $I->sendAjaxPostRequest('/airport/delete?id=6');

        $I->seeResponseCodeIsRedirection();
        $count = Airport::find()->where(['id' => 6])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/airport/delete?id=6');

        $I->seeResponseCodeIs(403);
        $count = Airport::find()->where(['id' => 6])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/airport/delete?id=6');

        $I->seeResponseCodeIsRedirection();
        $count = Airport::find()->where(['id' => 6])->count();
        $I->assertEquals(1, $count);
    }
}