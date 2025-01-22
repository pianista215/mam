<?php

namespace tests\api\v1\flightReport;

use app\modules\api\dto\v1\response\FlightPlanDTO;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use \ApiTester;

class FlightReportSubmissionCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    private function loginAsUser($id, ApiTester $I)
    {
        $access_token = \app\models\Pilot::find()->where(['id' => $id])->one()->access_token;
        $I->amBearerAuthenticated($access_token);
    }

    private function checkUnauthenticated(ApiTester $I)
    {
        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson([
            'name' => 'Unauthorized',
            'message' => 'Your request was made with invalid credentials.',
            'code' => 0,
            'status' => 401
        ]);
    }

    public function testUserUnauthenticated(ApiTester $I)
    {
        $I->sendPOST('/flight-report/submit-report/?flightPlanId=1');
        $this->checkUnauthenticated($I);

        $I->amBearerAuthenticated('inventedToken');
        $I->sendPOST('/flight-report/submit-report/?flightPlanId=1');
        $this->checkUnauthenticated($I);
    }

    public function testUserAuthenticatedWithoutFlightPlan(ApiTester $I)
    {
        $this->loginAsUser(1, $I);
        $I->sendPOST('/flight-report/submit-report/?flightPlanId=1');
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'The user hasn\'t any submitted flight plan.',
            'code' => 0,
            'status' => 404
        ]);
    }

    public function testUserTriesToSubmitReportForAnotherFlightPlan(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/flight-report/submit-report/?flightPlanId=3');
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'User flight plan and sent flight plan doesn\'t match.',
            'code' => 0,
            'status' => 404
        ]);
    }

    public function testUserMissingAllFields(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/flight-report/submit-report/?flightPlanId=1', []);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid data. No data was provided.',
            'code' => 0,
            'status' => 400
        ]);
    }

    public function testUserMissingSomeFields(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/flight-report/submit-report/?flightPlanId=1', [
            'pilot_comments' => 'Good flight',
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid data: '
                . 'Last Position Lat cannot be blank., '
                . 'Last Position Lon cannot be blank., '
                . 'Network cannot be blank., '
                . 'Sim Aircraft Name cannot be blank., '
                . 'Start Time cannot be blank., '
                . 'End Time cannot be blank., '
                . 'Chunks cannot be blank.',
            'code' => 0,
            'status' => 400
        ]);
    }

    /*public function testUserMissingAcarsFileDetails(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/flight-report/submit-report/validFlightPlanId', [
            'pilot_comments' => 120,
            'last_position_lat' => 130,
            'last_position_lon' => 5000,
            'distance' => 300,
            'acarsFiles' => [], // Missing ACARS file details
        ]);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContains('ACARS file details are required');
    }

    public function testUserDuplicateAcarsFiles(ApiTester $I)
    {
        $I->amBearerAuthenticated('validTokenForUserWithFlightPlan');
        $I->sendPOST('/flight-report/submit-report/validFlightPlanId', [
            'flightTime' => 120,
            'blockTime' => 130,
            'fuelBurn' => 5000,
            'distance' => 300,
            'acarsFiles' => [
                ['id' => 1, 'sha256sum' => 'hash1'],
                ['id' => 1, 'sha256sum' => 'hash1'], // Duplicate
            ],
        ]);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContains('Duplicate ACARS file IDs');
    }

    public function testUserMissingAcarsFileIds(ApiTester $I)
    {
        $I->amBearerAuthenticated('validTokenForUserWithFlightPlan');
        $I->sendPOST('/flight-report/submit-report/validFlightPlanId', [
            'flightTime' => 120,
            'blockTime' => 130,
            'fuelBurn' => 5000,
            'distance' => 300,
            'acarsFiles' => [
                ['id' => 1, 'sha256sum' => 'hash1'],
                ['id' => 2, 'sha256sum' => 'hash2'],
                ['id' => 4, 'sha256sum' => 'hash4'], // Missing ID 3
            ],
        ]);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContains('Missing ACARS file IDs in sequence');
    }

    public function testValidFlightReportSubmission(ApiTester $I)
    {
        $I->amBearerAuthenticated('validTokenForUserWithFlightPlan');
        $I->sendPOST('/flight-report/submit-report/validFlightPlanId', [
            'flightTime' => 120,
            'blockTime' => 130,
            'fuelBurn' => 5000,
            'distance' => 300,
            'acarsFiles' => [
                ['id' => 1, 'sha256sum' => 'hash1'],
                ['id' => 2, 'sha256sum' => 'hash2'],
                ['id' => 3, 'sha256sum' => 'hash3'],
            ],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('Flight report submitted successfully');
    }

    public function testAfterValidSubmissionFPLIsClosed(ApiTester $I)
    {
        $I->amBearerAuthenticated('validTokenForUserWithFlightPlan');
        $I->sendPOST('/flight-report/submit-report/validFlightPlanId', [
            'flightTime' => 120,
            'blockTime' => 130,
            'fuelBurn' => 5000,
            'distance' => 300,
            'acarsFiles' => [
                ['id' => 1, 'sha256sum' => 'hash1'],
                ['id' => 2, 'sha256sum' => 'hash2'],
                ['id' => 3, 'sha256sum' => 'hash3'],
            ],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('Flight report submitted successfully');
    }

*/


}
