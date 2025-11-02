<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use Yii;

class SubmittedFlightPlanUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    private function cantUpdateSubmittedFpl(\FunctionalTester $I)
    {
        $I->amOnRoute('submitted-flight-plan/update', [ 'id' => '3' ]);
        $I->seeResponseCodeIs(403);
        $I->see('Forbidden');
        $I->dontSee('Submit FPL', 'button');
    }

    public function noOneCantUpdateOthersSubmittedFlightPlan(\FunctionalTester $I)
    {
        // Visitor
        $I->amOnRoute('submitted-flight-plan/update', [ 'id' => '3' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');

        // Others
        $users = [1,2,3,4,5,6,8];
        $length = count($users);

        for($i = 0; $i < $length; $i++){
            $I->amLoggedInAs($users[$i]);
            $this->cantUpdateSubmittedFpl($I);
        }
    }

    public function updateEmptySubmittedFlightPlan(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->amOnRoute('submitted-flight-plan/update', [ 'id' => '3' ]);

        $I->fillField('#submittedflightplan-cruise_speed_value','');
        $I->fillField('#flight_level_value','');
        $I->fillField('#submittedflightplan-route','');
        $I->fillField('#submittedflightplan-estimated_time','');
        $I->fillField('#submittedflightplan-alternative1_icao','');
        $I->fillField('#submittedflightplan-other_information','');
        $I->fillField('#submittedflightplan-endurance_time','');


        $I->click('Submit FPL', 'button');

        $I->expectTo('see validations errors');
        $I->see('Cruise Speed Value cannot be blank.');
        $I->see('Flight Level Value cannot be blank if VFR is not selected.');
        $I->see('Route cannot be blank.');
        $I->see('Total EET cannot be blank.');
        $I->see('Other Information cannot be blank.');
        $I->see('Endurance cannot be blank.');

        $count = \app\models\AircraftType::find()->count();
        $I->assertEquals(4, $count);
    }

    public function updateValidSubmittedFlightPlanRoute(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->amOnRoute('submitted-flight-plan/update', [ 'id' => '3' ]);

        $I->fillField('#submittedflightplan-cruise_speed_value','100');
        $I->fillField('#flight_level_value','120');
        $I->fillField('#submittedflightplan-route','OTHER ROUTE');
        $I->fillField('#submittedflightplan-estimated_time','0500');
        $I->fillField('#submittedflightplan-alternative1_icao','LEAL');
        $I->fillField('#submittedflightplan-other_information','OTHER INFO DIFFERENT');
        $I->fillField('#submittedflightplan-endurance_time','0630');

        $I->click('Submit FPL', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Current Flight Plan');
        $I->see('Route R003 (LEBL-GCLP)');

        $I->seeInField('input[name="SubmittedFlightPlan[cruise_speed_value]"]', '100');
        $I->seeInField('input[name="SubmittedFlightPlan[flight_level_value]"]', '120');
        $I->seeInField('textarea[name="SubmittedFlightPlan[route]"]', 'OTHER ROUTE');
        $I->seeInField('input[name="SubmittedFlightPlan[alternative1_icao]"]', 'LEAL');
        $I->seeInField('textarea[name="SubmittedFlightPlan[other_information]"]', 'OTHER INFO DIFFERENT');
        $I->seeInField('input[name="SubmittedFlightPlan[endurance_time]"]', '0630');

        $model = \app\models\SubmittedFlightPlan::find()->where(['pilot_id' => 7])->one();
        $I->assertNotNull($model);
        $I->assertEquals(100, $model->cruise_speed_value);
        $I->assertEquals(120, $model->flight_level_value);
        $I->assertEquals(3, $model->route_id);
        $I->assertEquals(null, $model->tour_stage_id);
        $I->assertEquals('OTHER ROUTE', $model->route);
        $I->assertEquals('LEAL', $model->alternative1_icao);
        $I->assertEquals('OTHER INFO DIFFERENT', $model->other_information);
        $I->assertEquals('0630', $model->endurance_time);

        $count = \app\models\SubmittedFlightPlan::find()->count();
        $I->assertEquals(4, $count);
    }

    public function updateValidSubmittedFlightPlanTour(\FunctionalTester $I)
    {
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/update', [ 'id' => '4' ]);

        $I->fillField('#submittedflightplan-cruise_speed_value','100');
        $I->fillField('#flight_level_value','120');
        $I->fillField('#submittedflightplan-route','OTHER ROUTE');
        $I->fillField('#submittedflightplan-estimated_time','0500');
        $I->fillField('#submittedflightplan-alternative1_icao','LEAL');
        $I->fillField('#submittedflightplan-other_information','OTHER INFO DIFFERENT');
        $I->fillField('#submittedflightplan-endurance_time','0630');

        $I->click('Submit FPL', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Current Flight Plan');
        $I->see('Stage Tour actual reported #1 (LEBL-LEMD)');

        $I->seeInField('input[name="SubmittedFlightPlan[cruise_speed_value]"]', '100');
        $I->seeInField('input[name="SubmittedFlightPlan[flight_level_value]"]', '120');
        $I->seeInField('textarea[name="SubmittedFlightPlan[route]"]', 'OTHER ROUTE');
        $I->seeInField('input[name="SubmittedFlightPlan[alternative1_icao]"]', 'LEAL');
        $I->seeInField('textarea[name="SubmittedFlightPlan[other_information]"]', 'OTHER INFO DIFFERENT');
        $I->seeInField('input[name="SubmittedFlightPlan[endurance_time]"]', '0630');

        $model = \app\models\SubmittedFlightPlan::find()->where(['pilot_id' => 8])->one();
        $I->assertNotNull($model);
        $I->assertEquals(100, $model->cruise_speed_value);
        $I->assertEquals(120, $model->flight_level_value);
        $I->assertEquals(null, $model->route_id);
        $I->assertEquals(2, $model->tour_stage_id);
        $I->assertEquals('OTHER ROUTE', $model->route);
        $I->assertEquals('LEAL', $model->alternative1_icao);
        $I->assertEquals('OTHER INFO DIFFERENT', $model->other_information);
        $I->assertEquals('0630', $model->endurance_time);

        $count = \app\models\SubmittedFlightPlan::find()->count();
        $I->assertEquals(4, $count);
    }

}