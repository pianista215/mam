<?php

namespace tests\functional\submitFlightPlan;

use app\models\SubmittedFlightPlan;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\LiveFlightPositionFixture;
use tests\fixtures\SubmittedFlightPlanFixture;

class SubmittedFlightPlanDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
            'liveFlightPosition' => LiveFlightPositionFixture::class,
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

    // ACARS protection tests

    /**
     * FPL 1 has active ACARS (updated_at = now), owner is pilot 5
     * Should NOT be able to delete
     */
    public function ownerCannotDeleteWhenAcarsIsActive(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $I->sendAjaxPostRequest('/submitted-flight-plan/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $I->followRedirect();
        $I->see('security reasons');

        $count = SubmittedFlightPlan::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }

    /**
     * FPL 3 has stale ACARS (updated_at = 5 min ago), owner is pilot 7
     * Should be able to delete
     */
    public function ownerCanDeleteWhenAcarsIsStale(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->sendAjaxPostRequest('/submitted-flight-plan/delete?id=3');

        $I->seeResponseCodeIsRedirection();
        $count = SubmittedFlightPlan::find()->where(['id' => 3])->count();
        $I->assertEquals(0, $count);
    }
}