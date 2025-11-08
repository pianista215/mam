<?php

namespace tests\functional\rank;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class RankIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
        ];
    }

    private function checkRankIndexCommon(\FunctionalTester $I){
        $I->amOnRoute('rank/index');

        $I->see('Ranks');
        $I->see('Showing 1-3 of 3 items.');
        $I->see('Rank 1');
        $I->see('Rank 2');
        $I->see('Rank 3');
    }

    public function openRankIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkRankIndexCommon($I);

        $I->see('Create Rank', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openRankIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkRankIndexCommon($I);

        $I->dontSee('Create Rank', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openRankIndexAsVisitor(\FunctionalTester $I)
    {
        $this->checkRankIndexCommon($I);

        $I->dontSee('Create Rank', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    private function checkRankViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('rank/view', [ 'id' => '1' ]);

        $I->see('Rank 1');
        $I->see('Pilots with this rank');
        $I->see('AB1234');
        $I->see('John Doe');
        $I->see('AB5678');
        $I->see('Ifr School');
        $I->see('AB6789');
        $I->see('Other Ifr School');
    }

    public function openRankViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkRankViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openRankViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkRankViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openRankViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('route/view', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }


}