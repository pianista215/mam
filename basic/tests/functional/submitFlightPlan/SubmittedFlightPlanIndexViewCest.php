<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use Yii;

class SubmittedFlightPlanIndexViewCest
{

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    private function cantSeeAircraftIndex(\FunctionalTester $I)
    {
        $I->amOnRoute('submitted-flight-plan/index');
        $I->seeResponseCodeIs(403);
        $I->see('Forbidden');
        $I->dontSee('Submmited Flight Plans');
        $I->dontSee('LEBL');
        $I->dontSee('GCLP');
        $I->dontSee('Flight Rules');
    }

    private function canSeeTheAircraftIndex(\FunctionalTester $I)
    {
        $I->amOnRoute('submitted-flight-plan/index');
        $I->seeResponseCodeIs(200);
    }

    private function canSeeOnlyViewButton(\FunctionalTester $I)
    {
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }


    public function openAircraftIndexVisitor(\FunctionalTester $I)
    {
        $this->cantSeeAircraftIndex($I);
    }

    public function openAircraftIndexPilot(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $this->cantSeeAircraftIndex($I);
    }

    public function openAircraftIndexAsVfrValidator(\FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $this->canSeeTheAircraftIndex($I);
        $I->see('Departure');
        $I->see('Arrival');

        $I->see('Vfr School');
        $I->dontSee('Ifr School');
        $I->dontSee('Other Ifr School');
        $I->dontSee('Ifr Validator');
        $this->canSeeOnlyViewButton($I);
    }

    public function openAircraftIndexAsIfrValidator(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $this->canSeeTheAircraftIndex($I);
        $I->see('Departure');
        $I->see('Arrival');
        $I->dontSee('Vfr School');
        $I->see('Ifr School');
        $I->see('Other Ifr School');
        $I->see('Ifr Validator');
        $this->canSeeOnlyViewButton($I);
    }

    public function openAircraftIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $this->canSeeTheAircraftIndex($I);
        $I->see('Departure');
        $I->see('Arrival');
        $I->see('Vfr School');
        $I->see('Ifr School');
        $I->see('Other Ifr School');
        $I->see('Ifr Validator');
        $this->canSeeOnlyViewButton($I);
    }

}