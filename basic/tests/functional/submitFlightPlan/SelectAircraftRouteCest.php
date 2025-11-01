<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use Yii;

class SelectAircraftRouteCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    public function openSelectAircraftRouteNotAvailableLocation(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route',[ 'route_id' => '1' ]);

        $I->see('Forbidden: Pilot location is not at LEBL');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEVC');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftRouteNonActivatedPilot(\FunctionalTester $I){
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route',[ 'route_id' => '1' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEBL');
        $I->dontSee('LEVC');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftRouteVisitor(\FunctionalTester $I){
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route',[ 'route_id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openSelectAircraftRouteValidLocationAsPilot(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route',[ 'route_id' => '1' ]);

        $I->see('LEBL');
        $I->see('LEVC');

        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-route?route_id=1&aircraft_id=2"]');
        $I->see('EC-UUU');
        $I->see('C172 Std');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-route?route_id=1&aircraft_id=3"]');


        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

    public function openSelectAircraftRouteFiltersPlanesBasedOnRange(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route',[ 'route_id' => '3' ]);

        $I->see('LEBL');
        $I->see('GCLP');

        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-route?route_id=3&aircraft_id=2"]');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
        $I->dontSeeElement('a[href*="/submitted-flight-plan/prepare-fpl-route?route_id=3&aircraft_id=3"]');

        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

}