<?php

namespace tests\functional\route;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\PilotFixture;
use Yii;

class RankDeleteCest
{

    // TODO: Create acceptance tests to delete (lack of JS Support, POST not available in codeception API)

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'pilot' => PilotFixture::class,
        ];
    }

    public function deleteRankAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('rank/view', [ 'id' => '1' ]);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('rank/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Rank::find()->count();
        $I->assertEquals(3, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('rank/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Rank::find()->count();
        $I->assertEquals(3, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('rank/delete', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

}