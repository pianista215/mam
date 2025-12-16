<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\AircraftFixture;
use tests\fixtures\RouteFixture;
use tests\fixtures\TourStageFixture;
use Yii;

class SelectFlightCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
            'route' => RouteFixture::class,
            'tour_stage' => TourStageFixture::class,
        ];
    }

    public function openSelectFlightNoRoutesFromLocation(\FunctionalTester $I){
        $I->amLoggedInAs(10);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->see('Select flight');
        $I->see('No results found.');

        $I->see('Charter flight');
        $I->see('Continue', 'button');
    }

    public function openSelectFlightOnlyStagesFromLocation(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->see('Select flight');
        $I->see('Tour Stages');
        $I->see('Tour present 2 #1');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=5"]');

        $I->see('Routes');
        $I->see('No results found.');
        $I->see('Charter flight');
        $I->see('Continue', 'button');
    }

    public function openSelectFlightBothStagesAndRoutes(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->see('Select flight');

        $I->see('Tour Stages');
        $I->see('Tour actual reported #1');

        // This stage is in the past
        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=1"]');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=2"]');

        $I->see('Routes');
        $I->see('Showing 1-2 of 2 items.');

        $I->see('LEBL');
        $I->see('LEVC');
        $I->see('GCLP');
        $I->see('165');
        $I->see('1173');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-route?route_id=1"]');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-route?route_id=3"]');

        $I->see('Charter flight');
        $I->see('Continue', 'button');
    }

    public function canSelectCharterFlightWithAirport(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->see('Charter flight');
        $I->see('Continue', 'button');

        $I->fillField('input[name="arrival"]', 'gclp');
        $I->click('Continue');

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('submitted-flight-plan/select-aircraft-charter');
        $I->seeInCurrentUrl('arrival=gclp');

        $I->see('Charter flight (LEMD-GCLP)');
    }

    public function cantSelectFlightIfNonActivatedUser(\FunctionalTester $I){
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('LEBL');
        $I->dontSee('LEVC');
        $I->dontSee('GCLP');
        $I->dontSee('Charter flight');
        $I->dontSee('Continue', 'button');
    }

    public function cantSelectFlightIfVisitor(\FunctionalTester $I){
        $I->amOnRoute('submitted-flight-plan/select-flight');
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

}