<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\AircraftFixture;
use tests\fixtures\RouteFixture;
use Yii;

class SelectRouteCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
            'route' => RouteFixture::class,
        ];
    }


    public function openSelectRouteAsAdminNoRoutesFromLocation(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/select-route');

        $I->see('Select route');
        // TODO: Change message to tell the user there are not available routes from its location
        $I->see('No results found.');
    }

    public function openSelectRouteAsPilot(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-route');

        $I->see('Select route');
        $I->see('Showing 1-2 of 2 items.');

        $I->see('LEBL');
        $I->see('LEVC');
        $I->see('GCLP');
        $I->see('165');
        $I->see('1173');
    }

    public function cantSelectRouteIfNonActivatedUser(\FunctionalTester $I){
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/select-route');

        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('LEBL');
        $I->dontSee('LEVC');
        $I->dontSee('GCLP');
    }

    public function cantSelectRouteIfVisitor(\FunctionalTester $I){
        $I->amOnRoute('submitted-flight-plan/select-route');
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

}