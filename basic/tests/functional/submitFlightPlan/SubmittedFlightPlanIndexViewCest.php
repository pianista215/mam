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

    private function cantSeeSubmittedFlightPlanIndex(\FunctionalTester $I)
    {
        $I->amOnRoute('submitted-flight-plan/index');
        $I->seeResponseCodeIs(403);
        $I->see('Forbidden');
        $I->dontSee('Submmited Flight Plans');
        $I->dontSee('LEBL');
        $I->dontSee('GCLP');
        $I->dontSee('Flight Rules');
    }

    private function canSeeTheSubmittedFlightPlanIndex(\FunctionalTester $I)
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


    public function openSubmittedFlightPlanIndexAsVisitor(\FunctionalTester $I)
    {
        $this->cantSeeSubmittedFlightPlanIndex($I);
    }

    public function openSubmittedFlightPlanIndexAsPilot(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $this->cantSeeSubmittedFlightPlanIndex($I);
    }

    public function openSubmittedFlightPlanIndexAsVfrValidator(\FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $this->canSeeTheSubmittedFlightPlanIndex($I);
        $I->see('Departure');
        $I->see('Arrival');

        $I->see('Vfr School');
        $I->dontSee('Ifr School');
        $I->dontSee('Other Ifr School');
        $I->dontSee('Ifr Validator');
        $this->canSeeOnlyViewButton($I);
    }

    public function openSubmittedFlightPlanIndexAsIfrValidator(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $this->canSeeTheSubmittedFlightPlanIndex($I);
        $I->see('Departure');
        $I->see('Arrival');
        $I->dontSee('Vfr School');
        $I->see('Ifr School');
        $I->see('Other Ifr School');
        $I->see('Ifr Validator');
        $this->canSeeOnlyViewButton($I);
    }

    public function openSubmittedFlightPlanIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $this->canSeeTheSubmittedFlightPlanIndex($I);
        $I->see('Departure');
        $I->see('Arrival');
        $I->see('Vfr School');
        $I->see('Ifr School');
        $I->see('Other Ifr School');
        $I->see('Ifr Validator');
        $this->canSeeOnlyViewButton($I);
    }

    private function cantViewSubmittedFlightPlan($I, $id)
    {
        $I->amOnRoute('submitted-flight-plan/view',[ 'id' => $id ]);
        $I->seeResponseCodeIs(403);
        $I->see('Forbidden');
        $I->dontSee('Current Flight Plan');
    }

    private function canSeeUpdateDeleteBtns($I)
    {
        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    private function cantSeeUpdateDeleteBtns($I)
    {
        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    private function canSeeSubmittedFlightPlan($I, $id)
    {
        $I->amOnRoute('submitted-flight-plan/view',[ 'id' => $id ]);
        $I->seeResponseCodeIs(200);
        $I->see('Current Flight Plan');
    }

    public function openSubmittedFlightPlanViewAsVisitor(\FunctionalTester $I)
    {
        $this->cantViewSubmittedFlightPlan($I, '1');
        $this->cantViewSubmittedFlightPlan($I, '2');
        $this->cantViewSubmittedFlightPlan($I, '3');
        $this->cantViewSubmittedFlightPlan($I, '4');
    }



    public function openSubmittedFlightPlanViewOwnFpl(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $this->cantViewSubmittedFlightPlan($I, '1');
        $this->cantViewSubmittedFlightPlan($I, '2');
        $this->cantViewSubmittedFlightPlan($I, '4');

        $this->canSeeSubmittedFlightPlan($I, '3');

        $I->seeInField('input[name=aircraftRegistration]', 'EC-DOS');
        $I->seeInField('input[name=aircraftType]', 'B738');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'GCLP');
        $I->seeInField('input[name=pilot]', 'Ifr School');
        $I->seeInField('textarea[name="SubmittedFlightPlan[route]"]', 'POINT1 L322 POINT2 VFR');
        $I->seeInField('select[name="SubmittedFlightPlan[flight_rules]"]', 'Y - IFR/VFR (IFR changing to VFR)');

        $this->canSeeUpdateDeleteBtns($I);
    }

    public function openSubmittedFlightPlanViewAsAuthorisedValidator(\FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $this->cantViewSubmittedFlightPlan($I, '1');
        $this->cantViewSubmittedFlightPlan($I, '3');
        $this->cantViewSubmittedFlightPlan($I, '4');

        $this->canSeeSubmittedFlightPlan($I, '2');

        $I->seeInField('input[name=aircraftRegistration]', 'EC-COS');
        $I->seeInField('input[name=aircraftType]', 'C172');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'LEVC');
        $I->seeInField('input[name=pilot]', 'Vfr School');
        $I->seeInField('textarea[name="SubmittedFlightPlan[route]"]', 'S\\N');
        $I->seeInField('select[name="SubmittedFlightPlan[flight_rules]"]', 'V - VFR (Visual Flight)');

        $this->cantSeeUpdateDeleteBtns($I);

        $I->amLoggedInAs(5);
        $this->cantViewSubmittedFlightPlan($I, '2');
        $this->canSeeSubmittedFlightPlan($I, '1');
        $this->canSeeSubmittedFlightPlan($I, '3');
        $this->canSeeSubmittedFlightPlan($I, '4');

        $I->seeInField('input[name=aircraftRegistration]', 'EC-FOS');
        $I->seeInField('input[name=aircraftType]', 'B738');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'GCLP');
        $I->seeInField('input[name=pilot]', 'Other Ifr School');
        $I->seeInField('textarea[name="SubmittedFlightPlan[route]"]', 'DCT EXAMPLE L444 EXAMPLE2');
        $I->seeInField('select[name="SubmittedFlightPlan[flight_rules]"]', 'Z - VFR/IFR (VFR changing to IFR)');

        $this->cantSeeUpdateDeleteBtns($I);
    }

    public function openSubmittedFlightPlanViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->canSeeSubmittedFlightPlan($I, '1');
        $this->canSeeSubmittedFlightPlan($I, '2');
        $this->canSeeSubmittedFlightPlan($I, '3');
        $this->canSeeSubmittedFlightPlan($I, '4');

        $I->seeInField('input[name=aircraftRegistration]', 'EC-FOS');
        $I->seeInField('input[name=aircraftType]', 'B738');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'GCLP');
        $I->seeInField('input[name=pilot]', 'Other Ifr School');
        $I->seeInField('textarea[name="SubmittedFlightPlan[route]"]', 'DCT EXAMPLE L444 EXAMPLE2');
        $I->seeInField('select[name="SubmittedFlightPlan[flight_rules]"]', 'Z - VFR/IFR (VFR changing to IFR)');

        $this->cantSeeUpdateDeleteBtns($I);
    }

}