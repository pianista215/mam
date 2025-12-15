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
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1');
        $this->checkUnauthenticated($I);

        $I->amBearerAuthenticated('inventedToken');
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1');
        $this->checkUnauthenticated($I);
    }

    public function testUserAuthenticatedWithoutFlightPlan(ApiTester $I)
    {
        $validDataRequest = [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44)],
            ]
        ];
        $this->loginAsUser(1, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', $validDataRequest);
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
        $validDataRequest = [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44)],
            ]
        ];
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=3', $validDataRequest);
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
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', []);
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
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', [
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

    public function testUserMissingAcarsFileDetails(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid data: '
                . 'Chunks cannot be blank.',
            'code' => 0,
            'status' => 400
        ]);

        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => []
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid data: '
                . 'Chunks cannot be blank.',
            'code' => 0,
            'status' => 400
        ]);
    }

    public function testUserInvalidSha256Chunk(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => 'novalidhash'],
            ]
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid data: '
                . 'Invalid chunk data: {"sha256sum":["Sha256sum should contain 44 characters."]}',
            'code' => 0,
            'status' => 400
        ]);
    }

    public function testUserDuplicateAcarsFiles(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44),],
                ['id' => 1, 'sha256sum' => str_repeat('A', 44),],
            ]
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid data: '
                . 'Chunk IDs must be sequential and start from 1. Missing or duplicated IDs detected.',
            'code' => 0,
            'status' => 400
        ]);
    }

    public function testUserMissingAcarsFileIds(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44),],
                ['id' => 2, 'sha256sum' => str_repeat('A', 44),],
                ['id' => 4, 'sha256sum' => str_repeat('A', 44),],
            ]
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid data: '
                . 'Chunk IDs must be sequential and start from 1. Missing or duplicated IDs detected.',
            'code' => 0,
            'status' => 400
        ]);
    }

    public function testUserSendChunkId0InsteadOf1(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 0, 'sha256sum' => str_repeat('A', 44),],
                ['id' => 1, 'sha256sum' => str_repeat('A', 44),],
                ['id' => 2, 'sha256sum' => str_repeat('A', 44),],
            ]
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid data: '
                . 'Chunk IDs must be sequential and start from 1. Missing or duplicated IDs detected.',
            'code' => 0,
            'status' => 400
        ]);
    }

    public function testUserSendInvalidTime(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 14:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44),],
            ]
        ]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid data: '
                . 'Start Time must be earlier than End Time.',
            'code' => 0,
            'status' => 400
        ]);
    }

    private function testValidWithRequest(ApiTester $I, $request)
    {
        // FPL with id 1 has aircraft_id 4 and pilot_id 4
        $aircraft_id = 4;
        $pilot_id = 5;

        $this->loginAsUser($pilot_id, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', $request);
        $I->seeResponseCodeIs(200);
        $response = $I->grabResponse();
        $data = json_decode($response, true);
        $I->assertArrayHasKey('flight_report_id', $data);
        $I->assertCount(1, $data);

        $flight_report_id = $data['flight_report_id'];

        $pilot_location = \app\models\Pilot::find()->where(['id' => $pilot_id])->one()->location;
        $aircraft_location = \app\models\Aircraft::find()->where(['id' => $aircraft_id])->one()->location;
        $I->assertEquals($pilot_location, 'LEAL');
        $I->assertEquals($aircraft_location, 'LEAL');

        $fpl = \app\models\SubmittedFlightPlan::find()->where(['pilot_id' => 4])->all();
        $I->assertEmpty($fpl);

        $flight_report = \app\models\FlightReport::find()->where(['id' => $flight_report_id])->one();
        $I->assertEquals($flight_report->pilot_comments, 'Good flight');
        $I->assertEquals($flight_report->sim_aircraft_name, 'Xplane King Air 350');
        $I->assertEquals($flight_report->start_time, '2025-02-01 11:00:00');
        $I->assertEquals($flight_report->end_time, '2025-02-01 12:15:13');

        $flight = \app\models\Flight::find()->where(['id' => $flight_report->flight_id])->one();
        $I->assertEquals($flight->pilot_id, $pilot_id);
        $I->assertEquals($flight->aircraft_id, $aircraft_id);
        $I->assertEquals($flight->network, 'IVAO');
        $I->assertEquals($flight->report_tool, 'Mam Acars 1.0');
        $I->assertEquals($flight->departure, 'LEBL');
        $I->assertEquals($flight->arrival, 'GCLP');
        $I->assertEquals($flight->alternative1_icao, 'LEMD');
        $I->assertEquals($flight->flight_rules, 'I');
        $I->assertEquals($flight->cruise_speed_unit, 'N');
        $I->assertEquals($flight->cruise_speed_value, '350');
        $I->assertEquals($flight->flight_level_unit, 'F');
        $I->assertEquals($flight->flight_level_value, '320');
        $I->assertEquals($flight->route, 'DCT EXAMPLE');
        $I->assertEquals($flight->estimated_time, '0130');
        $I->assertEquals($flight->other_information, 'DOF/20241205 REG/ECDDD OPR/XXX');
        $I->assertEquals($flight->endurance_time, '0400');
        $I->assertEquals($flight->code, 'R003');
        $I->assertEquals($flight->status, 'C');
        $I->assertEquals(null, $flight->tour_stage_id);
        $I->assertEquals('R', $flight->flight_type);

        return $flight_report_id;
    }

    public function testValidFlightReportSubmissionOneChunk(ApiTester $I)
    {
        $request = [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44)],
            ]
        ];

        $flight_report_id = $this->testValidWithRequest($I, $request);

        $flight_report = \app\models\FlightReport::find()->where(['id' => $flight_report_id])->one();
        $I->assertEquals($flight_report->landing_airport, 'LEAL');


        $acars_files = \app\models\AcarsFile::find()->where(['flight_report_id' => $flight_report_id])->all();
        $I->assertCount(1, $acars_files);
        $file = $acars_files[0];
        $I->assertEquals($file->chunk_id, 1);
        $I->assertEquals($file->sha256sum, str_repeat('A', 44));
    }

    public function testValidFlightReportSubmissionLandingInWater(ApiTester $I)
    {
        $request = [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.282220,
            'last_position_lon' => 0.558050,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44)],
            ]
        ];

        $flight_report_id = $this->testValidWithRequest($I, $request);

        $flight_report = \app\models\FlightReport::find()->where(['id' => $flight_report_id])->one();
        $I->assertEquals($flight_report->landing_airport, null);


        $acars_files = \app\models\AcarsFile::find()->where(['flight_report_id' => $flight_report_id])->all();
        $I->assertCount(1, $acars_files);
        $file = $acars_files[0];
        $I->assertEquals($file->chunk_id, 1);
        $I->assertEquals($file->sha256sum, str_repeat('A', 44));
    }

    public function testValidFlightReportSubmissionMultipleChunk(ApiTester $I)
    {
        $request = [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44)],
                ['id' => 2, 'sha256sum' => str_repeat('B', 44)],
                ['id' => 3, 'sha256sum' => str_repeat('C', 44)],
                ['id' => 4, 'sha256sum' => str_repeat('D', 44)],
            ]
        ];

        $flight_report_id = $this->testValidWithRequest($I, $request);

        $acars_files = \app\models\AcarsFile::find()->where(['flight_report_id' => $flight_report_id])->all();
        $I->assertCount(4, $acars_files);
        $file = $acars_files[0];
        $I->assertEquals($file->chunk_id, 1);
        $I->assertEquals($file->sha256sum, str_repeat('A', 44));
        $file = $acars_files[1];
        $I->assertEquals($file->chunk_id, 2);
        $I->assertEquals($file->sha256sum, str_repeat('B', 44));
        $file = $acars_files[2];
        $I->assertEquals($file->chunk_id, 3);
        $I->assertEquals($file->sha256sum, str_repeat('C', 44));
        $file = $acars_files[3];
        $I->assertEquals($file->chunk_id, 4);
        $I->assertEquals($file->sha256sum, str_repeat('D', 44));
    }

    public function testValidFlightReportSubmissionVFR(ApiTester $I)
    {
        $request = [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44)],
            ]
        ];

        // Check we are not having strange issues without Flight Level Unit
        $this->loginAsUser(6, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=2', $request);
        $I->seeResponseCodeIs(200);

        $response = $I->grabResponse();
        $data = json_decode($response, true);
        $I->assertArrayHasKey('flight_report_id', $data);
        $I->assertCount(1, $data);

        $flight_report_id = $data['flight_report_id'];

        $flight_report = \app\models\FlightReport::find()->where(['id' => $flight_report_id])->one();
        $I->assertEquals($flight_report->pilot_comments, 'Good flight');
        $I->assertEquals($flight_report->sim_aircraft_name, 'Xplane King Air 350');
        $I->assertEquals($flight_report->start_time, '2025-02-01 11:00:00');
        $I->assertEquals($flight_report->end_time, '2025-02-01 12:15:13');

        $flight = \app\models\Flight::find()->where(['id' => $flight_report->flight_id])->one();
        $I->assertEquals($flight->flight_level_unit, 'VFR');
        $I->assertEquals($flight->flight_level_value, '');
        $I->assertEquals(null, $flight->tour_stage_id);
        $I->assertEquals('R', $flight->flight_type);
    }

    public function testValidFlightReportSubmissionTwice(ApiTester $I)
    {
        /* If the report is sent twice (bad network, we expect just to have the report id
         * if the chunks matches with the last sent flight and the flight is in status 'C'
         */
        $request = [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44)],
            ]
        ];

        $flight_report_id = $this->testValidWithRequest($I, $request);

        $acars_files = \app\models\AcarsFile::find()->where(['flight_report_id' => $flight_report_id])->all();
        $I->assertCount(1, $acars_files);
        $file = $acars_files[0];
        $I->assertEquals($file->chunk_id, 1);
        $I->assertEquals($file->sha256sum, str_repeat('A', 44));

        $second_flight_report_id = $this->testValidWithRequest($I, $request);

        $I->assertSame($flight_report_id, $second_flight_report_id);
    }

    public function testValidFlightReportSubmissionTwiceError(ApiTester $I)
    {
        /* If the report is sent twice (bad network, we expect just to have the report id
         * if the chunks matches with the last sent flight and the flight is in status 'C'
         */
        $request = [
            'pilot_comments' => 'Good flight',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44)],
            ]
        ];

        $flight_report_id = $this->testValidWithRequest($I, $request);

        $acars_files = \app\models\AcarsFile::find()->where(['flight_report_id' => $flight_report_id])->all();
        $I->assertCount(1, $acars_files);
        $file = $acars_files[0];
        $I->assertEquals($file->chunk_id, 1);
        $I->assertEquals($file->sha256sum, str_repeat('A', 44));

        // If we try to close another time the FPL but with other chunks we expect an error
        $request['chunks'] = [
            ['id' => 1, 'sha256sum' => str_repeat('B', 44)],
        ];
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=1', $request);
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'The user hasn\'t any submitted flight plan.',
            'code' => 0,
            'status' => 404
        ]);
    }

    public function testValidFlightReportSubmissionTour(ApiTester $I)
    {
        $request = [
            'pilot_comments' => 'Good stage',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44)],
            ]
        ];

        // Check tour_stage_id is propagated to flight and data is filled
        $this->loginAsUser(8, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=4', $request);
        $I->seeResponseCodeIs(200);

        $response = $I->grabResponse();
        $data = json_decode($response, true);
        $I->assertArrayHasKey('flight_report_id', $data);
        $I->assertCount(1, $data);

        $flight_report_id = $data['flight_report_id'];

        $flight_report = \app\models\FlightReport::find()->where(['id' => $flight_report_id])->one();
        $I->assertEquals($flight_report->pilot_comments, 'Good stage');
        $I->assertEquals($flight_report->sim_aircraft_name, 'Xplane King Air 350');
        $I->assertEquals($flight_report->start_time, '2025-02-01 11:00:00');
        $I->assertEquals($flight_report->end_time, '2025-02-01 12:15:13');

        $flight = \app\models\Flight::find()->where(['id' => $flight_report->flight_id])->one();
        $I->assertEquals($flight->flight_level_unit, 'F');
        $I->assertEquals($flight->flight_level_value, '320');
        $I->assertEquals(2, $flight->tour_stage_id);
        $I->assertEquals('TAR1', $flight->code);
        $I->assertEquals('LEBL', $flight->departure);
        $I->assertEquals('LEMD', $flight->arrival);
        $I->assertEquals('T', $flight->flight_type);
    }

    public function testValidFlightReportSubmissionCharter(ApiTester $I)
    {
        $request = [
            'pilot_comments' => 'Good stage',
            'last_position_lat' => 38.280722,
            'last_position_lon' => -0.55235,
            'network' => 'IVAO',
            'sim_aircraft_name' => 'Xplane King Air 350',
            'report_tool' => 'Mam Acars 1.0',
            'start_time' => '2025-02-01 11:00:00',
            'end_time' => '2025-02-01 12:15:13',
            'chunks' => [
                ['id' => 1, 'sha256sum' => str_repeat('A', 44)],
            ]
        ];

        $this->loginAsUser(4, $I);
        $I->sendPOST('/flight-report/submit-report/?flight_plan_id=5', $request);
        $I->seeResponseCodeIs(200);

        $response = $I->grabResponse();
        $data = json_decode($response, true);
        $I->assertArrayHasKey('flight_report_id', $data);
        $I->assertCount(1, $data);

        $flight_report_id = $data['flight_report_id'];

        $flight_report = \app\models\FlightReport::find()->where(['id' => $flight_report_id])->one();
        $I->assertEquals($flight_report->pilot_comments, 'Good stage');
        $I->assertEquals($flight_report->sim_aircraft_name, 'Xplane King Air 350');
        $I->assertEquals($flight_report->start_time, '2025-02-01 11:00:00');
        $I->assertEquals($flight_report->end_time, '2025-02-01 12:15:13');

        $flight = \app\models\Flight::find()->where(['id' => $flight_report->flight_id])->one();
        $I->assertEquals($flight->flight_level_unit, 'F');
        $I->assertEquals($flight->flight_level_value, '320');
        $I->assertEquals(null, $flight->tour_stage_id);
        $I->assertEquals('CHARTER', $flight->code);
        $I->assertEquals('LEBL', $flight->departure);
        $I->assertEquals('LEMD', $flight->arrival);
        $I->assertEquals('C', $flight->flight_type);
    }


}
