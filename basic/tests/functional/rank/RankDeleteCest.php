<?php

namespace tests\functional\rank;

use app\models\Rank;
use tests\fixtures\AuthAssignmentFixture;

class RankDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
        ];
    }

    public function deleteRankAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('rank/view', ['id' => '1']);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('rank/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Rank::find()->count();
        $I->assertEquals(4, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('rank/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);
        $count = Rank::find()->count();
        $I->assertEquals(4, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('rank/delete', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    // Use rank id=4 which has no pilots associated
    public function adminCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/rank/delete?id=4');

        $I->seeResponseCodeIsRedirection();
        $count = Rank::find()->where(['id' => 4])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/rank/delete?id=4');

        $I->seeResponseCodeIs(403);
        $count = Rank::find()->where(['id' => 4])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/rank/delete?id=4');

        $I->seeResponseCodeIsRedirection();
        $count = Rank::find()->where(['id' => 4])->count();
        $I->assertEquals(1, $count);
    }
}