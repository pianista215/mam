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
        ];
    }

    public function openSelectAircraftCharterNonActivatedPilot(\FunctionalTester $I){
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[ 'arrival' => 'gclp' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEBL-LEMD');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftCharterVisitor(\FunctionalTester $I){
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[ 'arrival' => 'gclp' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openSelectAircraftCharterWithoutEnoughRegularRatio(\FunctionalTester $I){
        $I->amLoggedInAs(10);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[ 'arrival' => 'gclp' ]);
        $I->seeResponseCodeIs(200);

        $I->see('Your charter flights ratio is too high. Please complete more regular or tour flights before booking another charter.');
        $I->seeInCurrentUrl('submitted-flight-plan/select-flight');
        $I->dontSee('LEAL-GCLP');
    }

    public function openSelectAircraftCharterWithoutAirport(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[]);

        $I->see('Bad Request: Missing required parameters: arrival');
        $I->seeResponseCodeIs(400);
        $I->dontSee('EC-BBB');
    }

    public function openSelectAircraftCharterWithInvalidAirport(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',['arrival' => 'zzzz']);

        $I->see('Bad Request: Arrival airport (ICAO) is invalid.');
        $I->seeResponseCodeIs(400);
        $I->dontSee('EC-BBB');
    }

    public function openSelectAircraftCharterValidAirport(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[ 'arrival' => 'LEMD' ]);

        $I->see('LEBL-LEMD');
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-charter?arrival=LEMD&aircraft_id=2"]');
        $I->see('EC-UUU');
        $I->see('C172 Std');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-charter?arrival=LEMD&aircraft_id=3"]');

        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

    public function openSelectAircraftCharterFiltersPlanesBasedOnRange(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[ 'arrival' => 'gclp' ]);

        $I->see('LEBL-GCLP');

        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-charter?arrival=GCLP&aircraft_id=2"]');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
        $I->dontSeeElement('a[href*="/submitted-flight-plan/prepare-fpl-charter?arrival=gclp&aircraft_id=3"]');

        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

}