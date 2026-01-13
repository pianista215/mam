<?php

namespace tests\functional\pilot;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightFixture;
use Yii;

class PilotIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flight' => FlightFixture::class,
        ];
    }

    private function checkPilotIndexCommon(\FunctionalTester $I){
        $I->amOnRoute('pilot/index');

        $I->see('Pilots');
        $I->see('Total 12 items.');
        $I->see('AB1234');
        $I->see('John');
        $I->see('Doe');
        $I->see('Rank 1');

        $I->dontSee('nonactivated');
    }

    public function openPilotIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkPilotIndexCommon($I);

        $I->see('Create Pilot', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openPilotIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkPilotIndexCommon($I);

        $I->dontSee('Create Pilot', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openPilotIndexAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot/index');

        $I->see('Pilots');
        $I->see('Total 12 items.');
        $I->see('AB1234');
        $I->see('John');
        $I->dontSee('Doe');
        $I->see('D.');
        $I->see('Rank 1');

        $I->dontSee('nonactivated');

        $I->dontSee('Create Pilot', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    private function checkPilotViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('pilot/view', [ 'id' => '5' ]);

        $I->see('Ifr Validator');
        $I->see('Rank 2');
        $I->see('AB3456');
        $I->see('2020-07-12');
        $I->see('LEBL');

        $I->see('Recent flights');
        $I->see('3 flights');

        $I->see('Creation Date');
        $I->see('Departure');
        $I->see('Arrival');
        $I->see('Flight time');


        $I->see('LEMD');
        $I->see('LEBL');
        $I->see('B738');
    }

    public function openPilotViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkPilotViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openPilotViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkPilotViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openPilotViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('route/view', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }


}