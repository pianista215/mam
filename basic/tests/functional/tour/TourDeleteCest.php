<?php

namespace tests\functional\tour;

use app\models\Tour;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\TourStageFixture;

class TourDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'tourStage' => TourStageFixture::class,
        ];
    }

    public function deleteTourAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/view', ['id' => '1']);
        $I->seeResponseCodeIs(200);

        $I->see('Delete');
    }

    public function deleteTourAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);
        $I->amOnRoute('tour/view', ['id' => '1']);
        $I->seeResponseCodeIs(200);

        $I->see('Delete');
    }

    public function cantDeleteTourWithFlightsAssociated(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/view', ['id' => '3']);
        $I->seeResponseCodeIs(200);

        $I->dontSee('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Tour::find()->count();
        $I->assertEquals(5, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('tour/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Tour::find()->count();
        $I->assertEquals(5, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour/delete', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    // Use tour id=2 which has no stages associated
    public function adminCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/tour/delete?id=2');

        $I->seeResponseCodeIsRedirection();
        $count = Tour::find()->where(['id' => 2])->count();
        $I->assertEquals(0, $count);
    }

    public function tourManagerCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);
        $I->sendAjaxPostRequest('/tour/delete?id=2');

        $I->seeResponseCodeIsRedirection();
        $count = Tour::find()->where(['id' => 2])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/tour/delete?id=2');

        $I->seeResponseCodeIs(403);
        $count = Tour::find()->where(['id' => 2])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/tour/delete?id=2');

        $I->seeResponseCodeIsRedirection();
        $count = Tour::find()->where(['id' => 2])->count();
        $I->assertEquals(1, $count);
    }
}