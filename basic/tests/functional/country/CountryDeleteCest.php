<?php

namespace tests\functional\country;

use app\models\Country;
use tests\fixtures\AuthAssignmentFixture;

class CountryDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function deleteCountryAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('country/view', ['id' => '1']);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('country/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Country::find()->count();
        $I->assertEquals(2, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('country/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);

        $count = Country::find()->count();
        $I->assertEquals(2, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('country/delete', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    // Use country id=2 (Portugal) which has no airports associated
    public function adminCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/country/delete?id=2');

        $I->seeResponseCodeIsRedirection();
        $count = Country::find()->where(['id' => 2])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/country/delete?id=2');

        $I->seeResponseCodeIs(403);
        $count = Country::find()->where(['id' => 2])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/country/delete?id=2');

        $I->seeResponseCodeIsRedirection();
        $count = Country::find()->where(['id' => 2])->count();
        $I->assertEquals(1, $count);
    }
}