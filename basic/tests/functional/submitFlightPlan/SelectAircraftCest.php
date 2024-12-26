<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use Yii;

class SelectAircraftCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    // TODO: SELECT AIRCRAFT WHEN THE AIRCRAFT IS SELECTED BY OTHER FLIGHT PLAN

    public function openSelectAircraftRouteNotAvailableLocation(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/select-aircraft',[ 'route_id' => '1' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEBL');
        $I->dontSee('LEVC');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftRouteNonActivatedPilot(\FunctionalTester $I){
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/select-aircraft',[ 'route_id' => '1' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEBL');
        $I->dontSee('LEVC');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftVisitor(\FunctionalTester $I){
        $I->amOnRoute('submitted-flight-plan/select-aircraft',[ 'route_id' => '1' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEBL');
        $I->dontSee('LEVC');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftRouteValidLocationAsPilot(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft',[ 'route_id' => '1' ]);

        $I->see('LEBL');
        $I->see('LEVC');

        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->see('EC-UUU');
        $I->see('C172 Std');

        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

    public function openSelectAircraftRouteFiltersPlanesBasedOnRange(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft',[ 'route_id' => '3' ]);

        $I->see('LEBL');
        $I->see('GCLP');

        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');

        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

}