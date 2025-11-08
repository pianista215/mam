<?php

namespace tests\functional\rank;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class RankCreateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
        ];
    }

    public function openRankCreateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('rank/create');

        $I->see('Create Rank');
        $I->see('Save', 'button');
    }

    public function openRankCreateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('rank/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Rank');
        $I->dontSee('Save', 'button');
    }

    public function openRankCreateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('rank/create');
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function submitEmptyRank(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);

       $I->amOnRoute('rank/create');
       $I->click('Save', 'button');

       $I->expectTo('see validations errors');
       $I->see('Name cannot be blank.');

       $count = \app\models\Rank::find()->count();
       $I->assertEquals(3, $count);
    }

    public function submitValidRank(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('rank/create');

       $I->fillField('#rank-name','New Rank');
       $I->fillField('#rank-position','6');
       $I->click('Save', 'button');

       $I->seeResponseCodeIs(200);
       $I->see('New Rank');
       $I->see('Pilots with this rank');
       $I->see('No pilots currently have this rank.');
       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Rank::find()->where(['position' => 6])->one();
       $I->assertNotNull($model);
       $I->assertEquals('New Rank', $model->name);
       $I->assertEquals(6, $model->position);

       $count = \app\models\Rank::find()->count();
       $I->assertEquals(4, $count);
    }

    public function CantHaveTwoRankWithSamePosition(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);

       $I->amOnRoute('rank/create');

       $I->fillField('#rank-name','New Rank');
       $I->fillField('#rank-position','3');
       $I->click('Save', 'button');

       $I->expectTo('see validations errors');
       $I->see('Position "3" has already been taken.');

       $count = \app\models\Rank::find()->count();
       $I->assertEquals(3, $count);
    }

    public function ByDefaultLastPositionIsOffered(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);

       $I->amOnRoute('rank/create');
       $I->seeInField('#rank-position','4');
    }

}