<?php

namespace tests\api\v1\flightPlan;

use app\modules\api\dto\v1\response\FlightPlanDTO;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use \ApiTester;

class GetCurrentFplCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    private function checkCantAccessData(ApiTester $I)
    {
        $I->sendGET('/flight-plan/current-fpl');;
        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson([
            'name' => 'Unauthorized',
            'message' => 'Your request was made with invalid credentials.',
            'code' => 0,
            'status' => 401
        ]);
    }

    public function testInvalidBearerToken(ApiTester $I)
    {
        $I->amBearerAuthenticated('inventedToken');
        $this->checkCantAccessData($I);
    }

    public function testEmptyBearerToken(ApiTester $I)
    {
        $I->amBearerAuthenticated('');
        $this->checkCantAccessData($I);
    }

    public function testNoBearerToken(ApiTester $I)
    {
        $this->checkCantAccessData($I);
    }

    private function loginAsUser($id, ApiTester $I)
    {
        $access_token = \app\models\Pilot::find()->where(['id' => $id])->one()->access_token;
        $I->amBearerAuthenticated($access_token);
    }

    private function checkNoFplSubmitted(ApiTester $I)
    {
        $I->sendGET('/flight-plan/current-fpl');;
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'Flight plan not found.',
            'code' => 0,
            'status' => 404
        ]);
    }

    public function testNoSubmittedFpl(ApiTester $I)
    {
        $users = [1,2];
        $length = count($users);

        for($i = 0; $i < $length; $i++){
            $pilot_id = $users[$i];
            $this->loginAsUser($pilot_id, $I);
            $this->checkNoFplSubmitted($I);
        }
    }

    private function checkFplRetrieved($id, ApiTester $I)
    {
        $I->sendGET('/flight-plan/current-fpl');;
        $I->seeResponseCodeIs(200);

        $submittedFpl = \app\models\SubmittedFlightPlan::find()->where(['pilot_id' => $id])->one();
        $dto = FlightPlanDTO::fromModel($submittedFpl);

        $I->assertNotNull($dto->id);
        $I->assertNotNull($dto->departure_icao);
        $I->assertNotNull($dto->departure_latitude);
        $I->assertNotNull($dto->departure_longitude);
        $I->assertNotNull($dto->arrival_icao);
        $I->assertNotNull($dto->alt1_icao);
        $I->assertNotNull($dto->aircraft_type_icao);
        $I->assertNotNull($dto->aircraft_reg);

        $I->seeResponseContainsJson($dto->toArray());
    }

    public function testSubmittedFpl(ApiTester $I)
    {
        $users = [4,5,6,7,8];
        $length = count($users);

        for($i = 0; $i < $length; $i++){
            $pilot_id = $users[$i];
            $this->loginAsUser($pilot_id, $I);
            $this->checkFplRetrieved($pilot_id, $I);
        }
    }

    public function testSubmittedAssignmentFpl(ApiTester $I)
    {
        // Assignment 1: pilot 1, aircraft 2 (EC-BBB, B738), LEBL-LEMD
        $fpl = new \app\models\SubmittedFlightPlan([
            'aircraft_id' => 2,
            'pilot_id' => 1,
            'assignment_id' => 1,
            'flight_rules' => 'I',
            'alternative1_icao' => 'LEVC',
            'alternative2_icao' => 'LEAL',
            'cruise_speed_value' => '350',
            'cruise_speed_unit' => 'N',
            'flight_level_value' => '340',
            'flight_level_unit' => 'F',
            'route' => 'DCT',
            'estimated_time' => '0105',
            'other_information' => 'DOF/20261027',
            'endurance_time' => '0300',
        ]);
        $I->assertTrue($fpl->save(), json_encode($fpl->errors));

        $this->loginAsUser(1, $I);
        $I->sendGET('/flight-plan/current-fpl');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => $fpl->id,
            'departure_icao' => 'LEBL',
            'arrival_icao' => 'LEMD',
            'alt1_icao' => 'LEVC',
            'alt2_icao' => 'LEAL',
            'aircraft_type_icao' => 'B738',
            'aircraft_reg' => 'EC-BBB',
        ]);

        $data = json_decode($I->grabResponse(), true);
        $I->assertEqualsWithDelta(41.2971, (float) $data['departure_latitude'], 0.0001);
        $I->assertEqualsWithDelta(2.0833, (float) $data['departure_longitude'], 0.0001);
    }

}
