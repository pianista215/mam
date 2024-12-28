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

        $I->amOnRoute('submitted-flight-plan/index');

        $I->see('Submitted Flight Plans');
        $I->see('Showing 1-3 of 3 items.');

        $I->see('Boeing 737-800 (Standard)');
        $I->see('Boeing Name Std');
        $I->see('EC-AAA');
        $I->see('LEMD');

        $I->see('Boeing 737-800 (Cargo)');
        $I->see('Boeing Name Cargo');
        $I->see('EC-BBB');
        $I->see('LEBL');

        $I->see('Cessna 172 (Standard)');
        $I->see('C172 Std');
        $I->see('EC-UUU');
        $I->see('LEBL');

        $this->checkAircraftIndexCommon($I);

        $I->see('Create Aircraft', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

}