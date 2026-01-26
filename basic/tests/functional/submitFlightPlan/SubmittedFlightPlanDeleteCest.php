<?php

namespace tests\functional\submitFlightPlan;

use app\models\SubmittedFlightPlan;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;

class SubmittedFlightPlanDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    private function cantDeleteSubmittedFpl(\FunctionalTester $I)
    {
        $I->amOnRoute('submitted-flight-plan/delete', ['id' => '3']);
        $I->seeResponseCodeIs(405);
        $count = SubmittedFlightPlan::find()->count();
        $I->assertEquals(5, $count);
    }

    public function noOneCantDeleteOthersSubmittedFpl(\FunctionalTester $I)
    {
        // Visitor
        $I->amOnRoute('submitted-flight-plan/delete', ['id' => '3']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');

        // Others
        $users = [1, 2, 3, 4, 5, 6, 8];
        $length = count($users);

        for ($i = 0; $i < $length; $i++) {
            $I->amLoggedInAs($users[$i]);
            $this->cantDeleteSubmittedFpl($I);
        }
    }

    // Flight plan id=3 is owned by pilot_id=7
    public function ownerCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->sendAjaxPostRequest('/submitted-flight-plan/delete?id=3');

        $I->seeResponseCodeIsRedirection();
        $count = SubmittedFlightPlan::find()->where(['id' => 3])->count();
        $I->assertEquals(0, $count);
    }

    public function nonOwnerCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $I->sendAjaxPostRequest('/submitted-flight-plan/delete?id=3');

        $I->seeResponseCodeIs(403);
        $count = SubmittedFlightPlan::find()->where(['id' => 3])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/submitted-flight-plan/delete?id=3');

        $I->seeResponseCodeIsRedirection();
        $count = SubmittedFlightPlan::find()->where(['id' => 3])->count();
        $I->assertEquals(1, $count);
    }
}