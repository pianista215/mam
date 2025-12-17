<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use tests\fixtures\TourStageFixture;
use Yii;

class SubmitFplCharterCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    public function openPrepareFplCharterAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'gclp', 'aircraft_id' => '3' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openPrepareFplCharterAsNonActivatedUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'gclp', 'aircraft_id' => '3' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    public function openPrepareFplCharterAircraftInDifferentLocation(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'gclp', 'aircraft_id' => '9' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    public function openPrepareFplCharterAircraftBadRangeForRoute(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'gclp', 'aircraft_id' => '3' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    public function openPrepareFplCharterAircraftValidButAlreadyReserved(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'gclp', 'aircraft_id' => '4' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    // TODO UNAI: RATIO


    public function openPrepareFplCharterButAlreadyHaveOneSubmitted(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'gclp', 'aircraft_id' => '4' ]);

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('submitted-flight-plan/view');
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    public function openPrepareFplCharterAircraftValidRangeForRoute(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'lemd', 'aircraft_id' => '3' ]);

        $I->see('Flight Plan Submission');
        $I->seeInField('input[name=aircraftRegistration]', 'EC-UUU');
        $I->seeInField('input[name=aircraftType]', 'C172');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'LEMD');
        $I->seeInField('input[name=pilot]', 'John Doe');
        $I->see('Submit FPL', 'button');
    }

    public function openPrepareFplCharterEmptyFields(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'gclp', 'aircraft_id' => '2' ]);

        $I->see('Flight Plan Submission');
        $I->seeInField('input[name=aircraftRegistration]', 'EC-BBB');
        $I->seeInField('input[name=aircraftType]', 'B738');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'GCLP');
        $I->seeInField('input[name=pilot]', 'John Doe');
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
        $I->assertEquals(5, $count);
    }

    public function openPrepareFplCharterInvalidAlternatives(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'gclp', 'aircraft_id' => '2' ]);

        $I->see('Flight Plan Submission');
        $I->see('Charter flight (LEBL-GCLP)');
        $I->seeInField('input[name=aircraftRegistration]', 'EC-BBB');
        $I->seeInField('input[name=aircraftType]', 'B738');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'GCLP');
        $I->seeInField('input[name=pilot]', 'John Doe');
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
        $I->assertEquals(5, $count);
    }

    public function openPrepareFplCharterInvalidIntegerFields(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'gclp', 'aircraft_id' => '2' ]);

        $I->see('Flight Plan Submission');
        $I->see('Charter flight (LEBL-GCLP)');
        $I->seeInField('input[name=aircraftRegistration]', 'EC-BBB');
        $I->seeInField('input[name=aircraftType]', 'B738');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'GCLP');
        $I->seeInField('input[name=pilot]', 'John Doe');
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
        $I->assertEquals(5, $count);
    }

    public function openPrepareFplCharterValidVFRPlan(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'lemd', 'aircraft_id' => '3' ]);

        $I->see('Flight Plan Submission');
        $I->see('Charter flight (LEBL-LEMD)');
        $I->seeInField('input[name=aircraftRegistration]', 'EC-UUU');
        $I->seeInField('input[name=aircraftType]', 'C172');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'LEMD');
        $I->seeInField('input[name=pilot]', 'John Doe');
        $I->see('Submit FPL', 'button');

        $I->selectOption('select[name="SubmittedFlightPlan[flight_rules]"]', 'V - VFR (Visual Flight)');
        $I->selectOption('select[name="SubmittedFlightPlan[cruise_speed_unit]"]', 'K');
        $I->fillField('#submittedflightplan-cruise_speed_value','100');
        $I->selectOption('select[name="SubmittedFlightPlan[flight_level_unit]"]', 'VFR');
        $I->fillField('#flight_level_value','');
        $I->fillField('#submittedflightplan-route','S // N');
        $I->fillField('#submittedflightplan-estimated_time','0130');
        $I->fillField('#submittedflightplan-alternative1_icao','LEAL');
        $I->fillField('#submittedflightplan-alternative2_icao','LEBL');
        $I->fillField('#submittedflightplan-other_information','DOF/20241205 REG/ECUUU RMK/IFPS REROUTE ACCEPTED');
        $I->fillField('#submittedflightplan-endurance_time','0335');

        $I->click('Submit FPL', 'button');
        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('submitted-flight-plan/view');

        $I->see('Current Flight Plan');

        $I->seeInField('input[name=aircraftRegistration]', 'EC-UUU');
        $I->seeInField('input[name=aircraftType]', 'C172');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[flight_rules]"]', 'V - VFR (Visual Flight)');

        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[cruise_speed_unit]"]', 'K');
        $I->seeInField('input[name="SubmittedFlightPlan[cruise_speed_value]"]', '100');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[flight_level_unit]"]', 'VFR');
        $I->seeInField('input[name="SubmittedFlightPlan[flight_level_value]"]', '');

        $I->seeInField('textarea[name="SubmittedFlightPlan[route]"]', 'S // N');

        $I->seeInField('input[name="destination"]', 'LEMD');
        $I->seeInField('input[name="SubmittedFlightPlan[estimated_time]"]', '0130');
        $I->seeInField('input[name="SubmittedFlightPlan[alternative1_icao]"]', 'LEAL');
        $I->seeInField('input[name="SubmittedFlightPlan[alternative2_icao]"]', 'LEBL');

        $I->seeInField('textarea[name="SubmittedFlightPlan[other_information]"]', 'DOF/20241205 REG/ECUUU RMK/IFPS REROUTE ACCEPTED');

        $I->seeInField('input[name="SubmittedFlightPlan[endurance_time]"]', '0335');
        $I->seeInField('input[name=pilot]', 'John Doe');

        $model = \app\models\SubmittedFlightPlan::find()->where(['pilot_id' => '1'])->one();
        $I->assertNotNull($model);
        $I->assertEquals(3, $model->aircraft_id);
        $I->assertEquals(null, $model->route_id);
        $I->assertEquals(null, $model->tour_stage_id);
        $I->assertEquals('V', $model->flight_rules);
        $I->assertEquals('K', $model->cruise_speed_unit);
        $I->assertEquals('100', $model->cruise_speed_value);
        $I->assertEquals('VFR', $model->flight_level_unit);
        $I->assertEquals('', $model->flight_level_value);
        $I->assertEquals('S // N', $model->route);
        $I->assertEquals('0130', $model->estimated_time);
        $I->assertEquals('LEAL', $model->alternative1_icao);
        $I->assertEquals('LEBL', $model->alternative2_icao);
        $I->assertEquals('DOF/20241205 REG/ECUUU RMK/IFPS REROUTE ACCEPTED', $model->other_information);
        $I->assertEquals('0335', $model->endurance_time);

        $charterRoute = \app\models\CharterRoute::find()->where(['pilot_id' => 1])->one();
        $I->assertNotNull($charterRoute);
        $I->assertEquals($charterRoute->id, $model->charter_route_id);

        $count = \app\models\SubmittedFlightPlan::find()->count();
        $I->assertEquals(6, $count);

    }

    public function openPrepareFplCharterValidIFRPlan(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'gclp', 'aircraft_id' => '2' ]);

        $I->see('Flight Plan Submission');
        $I->see('Charter flight (LEBL-GCLP)');
        $I->seeInField('input[name=aircraftRegistration]', 'EC-BBB');
        $I->seeInField('input[name=aircraftType]', 'B738');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'GCLP');
        $I->seeInField('input[name=pilot]', 'John Doe');
        $I->see('Submit FPL', 'button');

        $I->fillField('#submittedflightplan-cruise_speed_value','350');
        $I->fillField('#flight_level_value','340');
        $I->fillField('#submittedflightplan-route','LOTOS M985 SOPET');
        $I->fillField('#submittedflightplan-estimated_time','0049');
        $I->fillField('#submittedflightplan-alternative1_icao','LEMD');
        $I->fillField('#submittedflightplan-other_information','DOF/20241205 REG/ECBBB OPR/XXX PER/B NAV/TCAS');
        $I->fillField('#submittedflightplan-endurance_time','0235');

        $I->click('Submit FPL', 'button');
        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('submitted-flight-plan/view');

        $I->see('Current Flight Plan');

        $I->seeInField('input[name=aircraftRegistration]', 'EC-BBB');
        $I->seeInField('input[name=aircraftType]', 'B738');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[flight_rules]"]', 'I - IFR (Instrument Flight)');

        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[cruise_speed_unit]"]', 'N');
        $I->seeInField('input[name="SubmittedFlightPlan[cruise_speed_value]"]', '350');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[flight_level_unit]"]', 'F');
        $I->seeInField('input[name="SubmittedFlightPlan[flight_level_value]"]', '340');

        $I->seeInField('textarea[name="SubmittedFlightPlan[route]"]', 'LOTOS M985 SOPET');

        $I->seeInField('input[name="destination"]', 'GCLP');
        $I->seeInField('input[name="SubmittedFlightPlan[estimated_time]"]', '0049');
        $I->seeInField('input[name="SubmittedFlightPlan[alternative1_icao]"]', 'LEMD');
        $I->seeInField('input[name="SubmittedFlightPlan[alternative2_icao]"]', '');

        $I->seeInField('textarea[name="SubmittedFlightPlan[other_information]"]', 'DOF/20241205 REG/ECBBB OPR/XXX PER/B NAV/TCAS');

        $I->seeInField('input[name="SubmittedFlightPlan[endurance_time]"]', '0235');
        $I->seeInField('input[name=pilot]', 'John Doe');

        $model = \app\models\SubmittedFlightPlan::find()->where(['pilot_id' => '1'])->one();
        $I->assertNotNull($model);
        $I->assertEquals(2, $model->aircraft_id);
        $I->assertEquals(null, $model->route_id);
        $I->assertEquals(null, $model->tour_stage_id);
        $I->assertEquals('I', $model->flight_rules);
        $I->assertEquals('N', $model->cruise_speed_unit);
        $I->assertEquals('350', $model->cruise_speed_value);
        $I->assertEquals('F', $model->flight_level_unit);
        $I->assertEquals('340', $model->flight_level_value);
        $I->assertEquals('LOTOS M985 SOPET', $model->route);
        $I->assertEquals('0049', $model->estimated_time);
        $I->assertEquals('LEMD', $model->alternative1_icao);
        $I->assertNull($model->alternative2_icao);
        $I->assertEquals('DOF/20241205 REG/ECBBB OPR/XXX PER/B NAV/TCAS', $model->other_information);
        $I->assertEquals('0235', $model->endurance_time);

        $charterRoute = \app\models\CharterRoute::find()->where(['pilot_id' => 1])->one();
        $I->assertNotNull($charterRoute);
        $I->assertEquals($charterRoute->id, $model->charter_route_id);

        $count = \app\models\SubmittedFlightPlan::find()->count();
        $I->assertEquals(6, $count);
    }

    public function openPrepareFplCharterValidIFRToVFRPlan(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'lemd', 'aircraft_id' => '3' ]);

        $I->see('Flight Plan Submission');
        $I->see('Charter flight (LEBL-LEMD)');
        $I->seeInField('input[name=aircraftRegistration]', 'EC-UUU');
        $I->seeInField('input[name=aircraftType]', 'C172');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'LEMD');
        $I->seeInField('input[name=pilot]', 'John Doe');
        $I->see('Submit FPL', 'button');

        $I->selectOption('select[name="SubmittedFlightPlan[flight_rules]"]', 'Y - IFR/VFR (IFR changing to VFR)');
        $I->selectOption('select[name="SubmittedFlightPlan[cruise_speed_unit]"]', 'N');
        $I->fillField('#submittedflightplan-cruise_speed_value','110');
        $I->selectOption('select[name="SubmittedFlightPlan[flight_level_unit]"]', 'M');
        $I->fillField('#flight_level_value','1100');
        $I->fillField('#submittedflightplan-route','LOTOS VFR');
        $I->fillField('#submittedflightplan-estimated_time','0132');
        $I->fillField('#submittedflightplan-alternative1_icao','LEAL');
        $I->fillField('#submittedflightplan-alternative2_icao','');
        $I->fillField('#submittedflightplan-other_information','DOF/20241205 REG/ECUUU');
        $I->fillField('#submittedflightplan-endurance_time','0400');

        $I->click('Submit FPL', 'button');
        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('submitted-flight-plan/view');

        $I->see('Current Flight Plan');

        $I->seeInField('input[name=aircraftRegistration]', 'EC-UUU');
        $I->seeInField('input[name=aircraftType]', 'C172');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[flight_rules]"]', 'Y - IFR/VFR (IFR changing to VFR)');

        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[cruise_speed_unit]"]', 'N');
        $I->seeInField('input[name="SubmittedFlightPlan[cruise_speed_value]"]', '110');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[flight_level_unit]"]', 'M');
        $I->seeInField('input[name="SubmittedFlightPlan[flight_level_value]"]', '1100');

        $I->seeInField('textarea[name="SubmittedFlightPlan[route]"]', 'LOTOS VFR');

        $I->seeInField('input[name="destination"]', 'LEMD');
        $I->seeInField('input[name="SubmittedFlightPlan[estimated_time]"]', '0132');
        $I->seeInField('input[name="SubmittedFlightPlan[alternative1_icao]"]', 'LEAL');
        $I->seeInField('input[name="SubmittedFlightPlan[alternative2_icao]"]', '');

        $I->seeInField('textarea[name="SubmittedFlightPlan[other_information]"]', 'DOF/20241205 REG/ECUUU');

        $I->seeInField('input[name="SubmittedFlightPlan[endurance_time]"]', '0400');
        $I->seeInField('input[name=pilot]', 'John Doe');

        $model = \app\models\SubmittedFlightPlan::find()->where(['pilot_id' => '1'])->one();
        $I->assertNotNull($model);
        $I->assertEquals(3, $model->aircraft_id);
        $I->assertEquals(null, $model->route_id);
        $I->assertEquals(null, $model->tour_stage_id);
        $I->assertEquals('Y', $model->flight_rules);
        $I->assertEquals('N', $model->cruise_speed_unit);
        $I->assertEquals('110', $model->cruise_speed_value);
        $I->assertEquals('M', $model->flight_level_unit);
        $I->assertEquals('1100', $model->flight_level_value);
        $I->assertEquals('LOTOS VFR', $model->route);
        $I->assertEquals('0132', $model->estimated_time);
        $I->assertEquals('LEAL', $model->alternative1_icao);
        $I->assertNull($model->alternative2_icao);
        $I->assertEquals('DOF/20241205 REG/ECUUU', $model->other_information);
        $I->assertEquals('0400', $model->endurance_time);

        $charterRoute = \app\models\CharterRoute::find()->where(['pilot_id' => 1])->one();
        $I->assertNotNull($charterRoute);
        $I->assertEquals($charterRoute->id, $model->charter_route_id);

        $count = \app\models\SubmittedFlightPlan::find()->count();
        $I->assertEquals(6, $count);
    }

    public function openPrepareFplCharterValidVFRToIFRPlan(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', [ 'arrival' => 'lemd', 'aircraft_id' => '3' ]);

        $I->see('Flight Plan Submission');
        $I->see('Charter flight (LEBL-LEMD)');
        $I->seeInField('input[name=aircraftRegistration]', 'EC-UUU');
        $I->seeInField('input[name=aircraftType]', 'C172');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'LEMD');
        $I->seeInField('input[name=pilot]', 'John Doe');
        $I->see('Submit FPL', 'button');

        $I->selectOption('select[name="SubmittedFlightPlan[flight_rules]"]', 'Z - VFR/IFR (VFR changing to IFR)');
        $I->selectOption('select[name="SubmittedFlightPlan[cruise_speed_unit]"]', 'M');
        $I->fillField('#submittedflightplan-cruise_speed_value','020');
        $I->selectOption('select[name="SubmittedFlightPlan[flight_level_unit]"]', 'A');
        $I->fillField('#flight_level_value','045');
        $I->fillField('#submittedflightplan-route','DCT LOTOS M985 SOPET');
        $I->fillField('#submittedflightplan-estimated_time','0140');
        $I->fillField('#submittedflightplan-alternative1_icao','LEAL');
        $I->fillField('#submittedflightplan-alternative2_icao','LEBL');
        $I->fillField('#submittedflightplan-other_information','DOF/20241205 REG/ECUUU OPR/XXX RMK/IFPS REROUTE ACCEPTED');
        $I->fillField('#submittedflightplan-endurance_time','0325');

        $I->click('Submit FPL', 'button');
        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('submitted-flight-plan/view');

        $I->see('Current Flight Plan');

        $I->seeInField('input[name=aircraftRegistration]', 'EC-UUU');
        $I->seeInField('input[name=aircraftType]', 'C172');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[flight_rules]"]', 'Z - VFR/IFR (VFR changing to IFR)');

        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[cruise_speed_unit]"]', 'M');
        $I->seeInField('input[name="SubmittedFlightPlan[cruise_speed_value]"]', '020');
        $I->seeOptionIsSelected('select[name="SubmittedFlightPlan[flight_level_unit]"]', 'A');
        $I->seeInField('input[name="SubmittedFlightPlan[flight_level_value]"]', '045');

        $I->seeInField('textarea[name="SubmittedFlightPlan[route]"]', 'DCT LOTOS M985 SOPET');

        $I->seeInField('input[name="destination"]', 'LEMD');
        $I->seeInField('input[name="SubmittedFlightPlan[estimated_time]"]', '0140');
        $I->seeInField('input[name="SubmittedFlightPlan[alternative1_icao]"]', 'LEAL');
        $I->seeInField('input[name="SubmittedFlightPlan[alternative2_icao]"]', 'LEBL');

        $I->seeInField('textarea[name="SubmittedFlightPlan[other_information]"]', 'DOF/20241205 REG/ECUUU OPR/XXX RMK/IFPS REROUTE ACCEPTED');

        $I->seeInField('input[name="SubmittedFlightPlan[endurance_time]"]', '0325');
        $I->seeInField('input[name=pilot]', 'John Doe');

        $model = \app\models\SubmittedFlightPlan::find()->where(['pilot_id' => '1'])->one();
        $I->assertNotNull($model);
        $I->assertEquals(3, $model->aircraft_id);
        $I->assertEquals(null, $model->route_id);
        $I->assertEquals(null, $model->tour_stage_id);
        $I->assertEquals('Z', $model->flight_rules);
        $I->assertEquals('M', $model->cruise_speed_unit);
        $I->assertEquals('020', $model->cruise_speed_value);
        $I->assertEquals('A', $model->flight_level_unit);
        $I->assertEquals('045', $model->flight_level_value);
        $I->assertEquals('DCT LOTOS M985 SOPET', $model->route);
        $I->assertEquals('0140', $model->estimated_time);
        $I->assertEquals('LEAL', $model->alternative1_icao);
        $I->assertEquals('LEBL', $model->alternative2_icao);
        $I->assertEquals('DOF/20241205 REG/ECUUU OPR/XXX RMK/IFPS REROUTE ACCEPTED', $model->other_information);
        $I->assertEquals('0325', $model->endurance_time);

        $charterRoute = \app\models\CharterRoute::find()->where(['pilot_id' => 1])->one();
        $I->assertNotNull($charterRoute);
        $I->assertEquals($charterRoute->id, $model->charter_route_id);

        $count = \app\models\SubmittedFlightPlan::find()->count();
        $I->assertEquals(6, $count);
    }

}