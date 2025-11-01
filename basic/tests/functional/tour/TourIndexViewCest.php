<?php

namespace tests\functional\TourConfiguration;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightFixture;
use Yii;

class TourIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flight' => FlightFixture::class,
        ];
    }

    private function checkTourIndexCommon(\FunctionalTester $I){
        $I->amOnRoute('tour/index');

        $I->see('Tours');
        $I->see('Showing 1-4 of 4 items.');
        $I->see('Tour previous');
        $I->see('Tour actual empty');
        $I->see('Tour actual reported');
        $I->see('Tour not started');
    }

    public function openTourIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkTourIndexCommon($I);

        $I->see('Create Tour', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openTourIndexAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);

        $this->checkTourIndexCommon($I);

        $I->see('Create Tour', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openTourIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkTourIndexCommon($I);

        $I->dontSee('Create Tour', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openTourIndexAsVisitor(\FunctionalTester $I)
    {
        $this->checkTourIndexCommon($I);

        $I->dontSee('Create Tour', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    private function checkTourPastViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('tour/view', [ 'id' => '1' ]);

        $I->see('Tour previous');
        $I->see('Tour already ended without flights associated');
        $I->see('Tour Stages');
        $I->see('LEBL');
        $I->see('LEMD');
        $I->see('Desc');
    }

    public function openTourPastViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkTourPastViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=1"]');
        $I->seeElement('a[href*="/tour-stage/update?id=1"]');
        $I->seeElement('a[href*="/tour-stage/update?id=1"]');
    }

    public function openTourPastViewAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);

        $this->checkTourPastViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=1"]');
        $I->seeElement('a[href*="/tour-stage/update?id=1"]');
        $I->seeElement('a[href*="/tour-stage/update?id=1"]');
    }

    public function openTourPastViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkTourPastViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=1"]');
        $I->dontSeeElement('a[href*="/tour-stage/update?id=1"]');
        $I->dontSeeElement('a[href*="/tour-stage/update?id=1"]');
    }

    public function openTourPastViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour/view', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }


}