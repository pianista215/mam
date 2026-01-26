<?php

namespace tests\functional\tourStage;

use app\models\TourStage;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightFixture;

class TourStageDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flight' => FlightFixture::class,
        ];
    }

    public function deleteTourStageAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/view', ['id' => '1']);
        $I->seeResponseCodeIs(200);

        $I->seeElement('a[href*="/tour-stage/delete?id=1"]');
    }

    public function deleteTourStageAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);
        $I->amOnRoute('tour/view', ['id' => '1']);
        $I->seeResponseCodeIs(200);

        $I->seeElement('a[href*="/tour-stage/delete?id=1"]');
    }

    public function cantDeleteTourStageWithFlightsAssociated(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/view', ['id' => '3']);
        $I->seeResponseCodeIs(200);

        $I->dontSee('a[href*="/tour-stage/delete?id=2"]');
        $I->dontSee('a[href*="/tour-stage/delete?id=3"]');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour-stage/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = TourStage::find()->count();
        $I->assertEquals(5, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('tour-stage/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = TourStage::find()->count();
        $I->assertEquals(5, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour-stage/delete', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function adminCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/tour-stage/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = TourStage::find()->where(['id' => 1])->count();
        $I->assertEquals(0, $count);
    }

    public function tourManagerCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);
        $I->sendAjaxPostRequest('/tour-stage/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = TourStage::find()->where(['id' => 1])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/tour-stage/delete?id=1');

        $I->seeResponseCodeIs(403);
        $count = TourStage::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/tour-stage/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = TourStage::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }
}