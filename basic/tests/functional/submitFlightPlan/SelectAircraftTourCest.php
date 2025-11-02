<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use tests\fixtures\TourStageFixture;
use Yii;

class SelectAircraftTourCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
            'tourStage' => TourStageFixture::class,
        ];
    }

    public function openSelectAircraftTourNotAvailableLocation(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-tour',[ 'tour_stage_id' => '2' ]);

        $I->see('Forbidden: Pilot location is not at LEBL');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEBL-LEMD');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftTourNotActive(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-tour',[ 'tour_stage_id' => '1' ]);

        $I->see('Forbidden: Tour is not active.');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEBL-LEMD');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftTourNonActivatedPilot(\FunctionalTester $I){
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-tour',[ 'tour_stage_id' => '2' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEBL-LEMD');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftTourVisitor(\FunctionalTester $I){
        $I->amOnRoute('submitted-flight-plan/select-aircraft-tour',[ 'tour_stage_id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openSelectAircraftTourValidLocationAsPilot(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-tour',[ 'tour_stage_id' => '2' ]);

        $I->see('LEBL-LEMD');
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-tour?tour_stage_id=2&aircraft_id=2"]');
        $I->see('EC-UUU');
        $I->see('C172 Std');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-tour?tour_stage_id=2&aircraft_id=3"]');

        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

    public function openSelectAircraftTourFiltersPlanesBasedOnRange(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-tour',[ 'tour_stage_id' => '3' ]);

        $I->see('LEBL-GCLP');

        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-tour?tour_stage_id=3&aircraft_id=2"]');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
        $I->dontSeeElement('a[href*="/submitted-flight-plan/prepare-fpl-tour?tour_stage_id=3&aircraft_id=3"]');

        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

}