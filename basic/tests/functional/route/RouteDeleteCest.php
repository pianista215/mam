<?php

namespace tests\functional\route;

use app\models\Route;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\RouteFixture;

class RouteDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'route' => RouteFixture::class,
        ];
    }

    public function deleteRouteAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('route/view', ['id' => '1']);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('route/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Route::find()->count();
        $I->assertEquals(3, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('route/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Route::find()->count();
        $I->assertEquals(3, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('route/delete', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function adminCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/route/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = Route::find()->where(['id' => 1])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/route/delete?id=1');

        $I->seeResponseCodeIs(403);
        $count = Route::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/route/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = Route::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }
}