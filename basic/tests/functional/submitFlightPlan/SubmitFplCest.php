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

    public function openPrepareFplAircraftValidRangeForRoute(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl', [ 'route_id' => '1', 'aircraft_id' => '3' ]);

        $I->see('Flight Plan Submission');
        $I->see('Submit FPL', 'button');
    }

    public function openPrepareFplEmptyFields(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl', [ 'route_id' => '3', 'aircraft_id' => '2' ]);

        $I->see('Flight Plan Submission');
        $I->see('Submit FPL', 'button');
        $I->click('Submit FPL', 'button');

        $I->expectTo('see validations errors');
        $I->see('Cruise Speed Value cannot be blank.');
        $I->see('Flight Level Value cannot be blank if VFR is not selected.');
        $I->see('Route cannot be blank.');
        $I->see('Total EET cannot be blank.');
        $I->see('Altn Aerodrome cannot be blank.');
        $I->see('Other Information cannot be blank.');
        $I->see('Endurance cannot be blank.');

        $count = \app\models\SubmittedFlightPlan::find()->count();
        $I->assertEquals(0, $count);
    }

    public function openPrepareFplInvalidAlternatives(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl', [ 'route_id' => '3', 'aircraft_id' => '2' ]);

        $I->see('Flight Plan Submission');
        $I->see('Submit FPL', 'button');

        $I->fillField('#submittedflightplan-cruise_speed_value','350');
        $I->fillField('#flight_level_value','340');
        $I->fillField('#submittedflightplan-route','LOTOS M985 SOPET');
        $I->fillField('#submittedflightplan-estimated_time','0049');
        $I->fillField('#submittedflightplan-other_information','DOF/20241205 REG/ECBBB OPR/XXX PER/B NAV/TCAS');
        $I->fillField('#submittedflightplan-endurance_time','0320');

        $I->fillField('#submittedflightplan-alternative1_icao','XXXX');
        $I->fillField('#submittedflightplan-alternative2_icao','YYYY');

        $I->click('Submit FPL', 'button');

        $I->expectTo('see validations errors');
        $I->dontSee('Cruise Speed Value cannot be blank.');
        $I->dontSee('Flight Level Value cannot be blank if VFR is not selected.');
        $I->dontSee('Route cannot be blank.');
        $I->dontSee('Total EET cannot be blank.');
        $I->dontSee('Other Information cannot be blank.');
        $I->dontSee('Endurance cannot be blank.');

        $I->see('Altn Aerodrome is invalid.');
        $I->see('2nd Altn Aerodrome is invalid.');

        $count = \app\models\SubmittedFlightPlan::find()->count();
        $I->assertEquals(0, $count);
    }

    public function openPrepareFplInvalidIntegerFields(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl', [ 'route_id' => '3', 'aircraft_id' => '2' ]);

        $I->see('Flight Plan Submission');
        $I->see('Submit FPL', 'button');

        $I->fillField('#submittedflightplan-route','LOTOS M985 SOPET');

        $I->fillField('#submittedflightplan-other_information','DOF/20241205 REG/ECBBB OPR/XXX PER/B NAV/TCAS');
        $I->fillField('#submittedflightplan-alternative1_icao','LEMD');


        $I->fillField('#submittedflightplan-endurance_time','aaaa');
        $I->fillField('#submittedflightplan-estimated_time','bbbb');
        $I->fillField('#submittedflightplan-cruise_speed_value','cccc');
        $I->fillField('#flight_level_value','dddd');

        $I->click('Submit FPL', 'button');

        $I->expectTo('see validations errors');

        $I->see('Endurance must be an integer.');
        $I->see('Total EET must be an integer.');
        $I->see('Cruise Speed Value must be an integer.');
        $I->see('Flight Level Value must be an integer.');

        $count = \app\models\SubmittedFlightPlan::find()->count();
        $I->assertEquals(0, $count);
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