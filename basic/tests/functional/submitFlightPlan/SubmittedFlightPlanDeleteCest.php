<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use Yii;

class SubmittedFlightPlanDeleteCest
{

    // TODO: Create acceptance tests to delete (lack of JS Support, POST not available in codeception API)

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    private function cantDeleteSubmittedFpl(\FunctionalTester $I)
    {
        $I->amOnRoute('submitted-flight-plan/delete', [ 'id' => '3' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\SubmittedFlightPlan::find()->count();
        $I->assertEquals(4, $count);
    }

    public function cantDeleteAnyoneOthersSubmittedFpl(\FunctionalTester $I)
    {
        // Visitor
        $this->cantDeleteSubmittedFpl($I);

        $users = [1,2,3,4,5,6,8];
        $length = count($users);

        for($i = 0; $i < $length; $i++){
            $I->amLoggedInAs($users[$i]);
            $this->cantDeleteSubmittedFpl($I);
        }
    }

}