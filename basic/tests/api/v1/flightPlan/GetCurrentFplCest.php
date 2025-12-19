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


}
