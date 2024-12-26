<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\AircraftFixture;
use tests\fixtures\RouteFixture;
use Yii;

class SubmitFplCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
            'route' => RouteFixture::class,
        ];
    }

    public function openPrepareFplAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('submitted-flight-plan/prepare-fpl', [ 'route_id' => '1', 'aircraft_id' => '3' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    public function openPrepareFplAsNonActivatedUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl', [ 'route_id' => '1', 'aircraft_id' => '3' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    public function openPrepareFplRouteDepartureDifferentFromUserLocation(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl', [ 'route_id' => '1', 'aircraft_id' => '3' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    public function openPrepareFplAircraftInDifferentLocation(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl', [ 'route_id' => '1', 'aircraft_id' => '1' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    public function openPrepareFplAircraftBadRangeForRoute(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl', [ 'route_id' => '3', 'aircraft_id' => '3' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    public function openPrepareFplEmptyFields(\FunctionalTester $I)
    {
    }

    public function openPrepareFplInvalidAlternatives(\FunctionalTester $I)
    {
    }

    public function openPrepareFplInvalidIntegerFields(\FunctionalTester $I)
    {
    }

    public function openPrepareFplValidVFRPlan(\FunctionalTester $I)
    {
    }

    public function openPrepareFplValidIFRPlan(\FunctionalTester $I)
    {
    }

    public function openPrepareFplValidIFRToVFRPlan(\FunctionalTester $I)
    {
    }

    public function openPrepareFplValidVFRtoIFRPlan(\FunctionalTester $I)
    {
    }

}