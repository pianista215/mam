<?php

namespace tests\functional\airport;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightFixture;
use Yii;

class TourStageDeleteCest
{

    // TODO: Create acceptance tests to delete (lack of JS Support, POST not available in codeception API)

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flight' => FlightFixture::class,
        ];
    }

    public function deleteTourStageAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/view', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(200);

        $I->seeElement('a[href*="/tour-stage/delete?id=1"]');
    }

    public function deleteTourStageAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);
        $I->amOnRoute('tour/view', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(200);

        $I->seeElement('a[href*="/tour-stage/delete?id=1"]');
    }

    public function cantDeleteTourStageWithFlightsAssociated(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/view', [ 'id' => '3' ]);
        $I->seeResponseCodeIs(200);

        $I->dontSee('a[href*="/tour-stage/delete?id=2"]');
        $I->dontSee('a[href*="/tour-stage/delete?id=3"]');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour-stage/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\TourStage::find()->count();
        $I->assertEquals(5, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('tour-stage/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\TourStage::find()->count();
        $I->assertEquals(5, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour-stage/delete', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

}