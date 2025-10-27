<?php

namespace tests\functional\airport;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\TourStageFixture;
use Yii;

class TourDeleteCest
{

    // TODO: Create acceptance tests to delete (lack of JS Support, POST not available in codeception API)

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'tourStage' => TourStageFixture::class,
        ];
    }

    public function deleteTourAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/view', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(200);

        $I->see('Delete');
    }

    public function deleteTourAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);
        $I->amOnRoute('tour/view', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(200);

        $I->see('Delete');
    }

    public function cantDeleteTourWithFlightsAssociated(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/view', [ 'id' => '3' ]);
        $I->seeResponseCodeIs(200);

        $I->dontSee('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Tour::find()->count();
        $I->assertEquals(4, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('tour/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Tour::find()->count();
        $I->assertEquals(4, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour/delete', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

}