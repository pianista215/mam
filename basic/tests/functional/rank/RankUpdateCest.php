<?php

namespace tests\functional\rank;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class RankUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
        ];
    }

    public function openRankUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('rank/update', [ 'id' => '1' ]);

        $I->see('Update Rank: Rank 1');
        $I->see('Save', 'button');
    }

    public function openRankUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('rank/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Update Rank: Rank 1');
        $I->dontSee('Save', 'button');
    }

    public function openRankUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('rank/update', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function updateEmptyRank(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('rank/update', [ 'id' => '1' ]);

       $I->fillField('#rank-name','');
       $I->fillField('#rank-position','');
       $I->click('Save');

       $I->expectTo('see validations errors');
       $I->see('Name cannot be blank.');
       $I->see('Position cannot be blank.');

       $count = \app\models\Rank::find()->count();
       $I->assertEquals(3, $count);
    }

    public function updateValidRank(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('rank/update', [ 'id' => '1' ]);

       $I->fillField('#rank-name', 'Other Rank');
       $I->fillField('#rank-position', '5');
       $I->click('Save');

       $I->seeResponseCodeIs(200);
       $I->see('Other Rank');
       $I->see('Pilots with this rank');
       $I->see('AB1234');
       $I->see('John Doe');
       $I->see('AB5678');
       $I->see('Ifr School');
       $I->see('AB6789');
       $I->see('Other Ifr School');

       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Rank::find()->where(['position' => 5])->one();
       $I->assertNotNull($model);
       $I->assertEquals('Other Rank', $model->name);
       $I->assertEquals(5, $model->position);

       $count = \app\models\Rank::find()->count();
       $I->assertEquals(3, $count);
    }

}