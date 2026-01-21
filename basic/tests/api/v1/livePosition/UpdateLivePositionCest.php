<?php

namespace tests\api\v1\livePosition;

use app\models\LiveFlightPosition;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use \ApiTester;

class UpdateLivePositionCest
{
    public function _fixtures()
    {
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

    private function getValidPositionData()
    {
        return [
            'latitude' => 40.4168,
            'longitude' => -3.7038,
            'altitude' => 35000,
            'heading' => 270,
            'ground_speed' => 450,
        ];
    }

    // ==================== Authentication Tests ====================

    public function testUnauthenticatedUserCannotAccess(ApiTester $I)
    {
        $I->sendPOST('/live-position/update?flight_plan_id=1', $this->getValidPositionData());
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
        $I->sendPOST('/live-position/update?flight_plan_id=1', $this->getValidPositionData());
        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson([
            'name' => 'Unauthorized',
            'message' => 'Your request was made with invalid credentials.',
            'code' => 0,
            'status' => 401
        ]);
    }

    public function testEmptyBearerToken(ApiTester $I)
    {
        $I->amBearerAuthenticated('');
        $I->sendPOST('/live-position/update?flight_plan_id=1', $this->getValidPositionData());
        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson([
            'name' => 'Unauthorized',
            'message' => 'Your request was made with invalid credentials.',
            'code' => 0,
            'status' => 401
        ]);
    }

    // ==================== Flight Plan Validation Tests ====================

    public function testUserWithoutFlightPlan(ApiTester $I)
    {
        // User 1 has no submitted flight plan
        $this->loginAsUser(1, $I);
        $I->sendPOST('/live-position/update?flight_plan_id=1', $this->getValidPositionData());
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'Flight plan not found.',
            'code' => 0,
            'status' => 404
        ]);
    }

    public function testUserWithDifferentFlightPlanId(ApiTester $I)
    {
        // User 5 has FPL id 1, but we send id 999
        $this->loginAsUser(5, $I);
        $I->sendPOST('/live-position/update?flight_plan_id=999', $this->getValidPositionData());
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'Flight plan not found.',
            'code' => 0,
            'status' => 404
        ]);
    }

    public function testUserTryingToAccessAnotherUserFlightPlan(ApiTester $I)
    {
        // User 5 has FPL id 1, user 6 has FPL id 2
        // User 5 tries to update FPL id 2 (which belongs to user 6)
        $this->loginAsUser(5, $I);
        $I->sendPOST('/live-position/update?flight_plan_id=2', $this->getValidPositionData());
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'message' => 'Flight plan not found.',
            'code' => 0,
            'status' => 404
        ]);
    }

    // ==================== Validation Tests ====================

    public function testInvalidPositionDataEmpty(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $I->sendPOST('/live-position/update?flight_plan_id=1', []);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid position data.',
            'code' => 0,
            'status' => 400
        ]);
    }

    public function testInvalidLatitudeOutOfRange(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $data = $this->getValidPositionData();
        $data['latitude'] = 95; // Invalid: must be between -90 and 90
        $I->sendPOST('/live-position/update?flight_plan_id=1', $data);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid position data.',
            'code' => 0,
            'status' => 400
        ]);
    }

    public function testInvalidLongitudeOutOfRange(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $data = $this->getValidPositionData();
        $data['longitude'] = 200; // Invalid: must be between -180 and 180
        $I->sendPOST('/live-position/update?flight_plan_id=1', $data);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid position data.',
            'code' => 0,
            'status' => 400
        ]);
    }

    public function testInvalidHeadingOutOfRange(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $data = $this->getValidPositionData();
        $data['heading'] = 400; // Invalid: must be between 0 and 360
        $I->sendPOST('/live-position/update?flight_plan_id=1', $data);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid position data.',
            'code' => 0,
            'status' => 400
        ]);
    }

    public function testInvalidNegativeAltitude(ApiTester $I)
    {
        $this->loginAsUser(5, $I);
        $data = $this->getValidPositionData();
        $data['altitude'] = -100; // Invalid: must be positive
        $I->sendPOST('/live-position/update?flight_plan_id=1', $data);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'name' => 'Bad Request',
            'message' => 'Invalid position data.',
            'code' => 0,
            'status' => 400
        ]);
    }

    // ==================== Success Tests ====================

    public function testValidPositionUpdateCreatesRecord(ApiTester $I)
    {
        // User 5 has FPL id 1
        $this->loginAsUser(5, $I);

        // Ensure no position exists before
        $existingPosition = LiveFlightPosition::findOne(['submitted_flight_plan_id' => 1]);
        $I->assertNull($existingPosition);

        $positionData = [
            'latitude' => 40.4168,
            'longitude' => -3.7038,
            'altitude' => 35000,
            'heading' => 270,
            'ground_speed' => 450,
        ];

        $I->sendPOST('/live-position/update?flight_plan_id=1', $positionData);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'success']);

        // Verify the position was created in the database
        $position = LiveFlightPosition::findOne(['submitted_flight_plan_id' => 1]);
        $I->assertNotNull($position);
        $I->assertEquals(40.4168, $position->latitude);
        $I->assertEquals(-3.7038, $position->longitude);
        $I->assertEquals(35000, $position->altitude);
        $I->assertEquals(270, $position->heading);
        $I->assertEquals(450, $position->ground_speed);
        $I->assertNotNull($position->updated_at);
    }

    public function testValidPositionUpdateUpdatesExistingRecord(ApiTester $I)
    {
        // User 6 has FPL id 2
        $this->loginAsUser(6, $I);

        // First update
        $firstPositionData = [
            'latitude' => 41.0,
            'longitude' => -4.0,
            'altitude' => 30000,
            'heading' => 180,
            'ground_speed' => 400,
        ];

        $I->sendPOST('/live-position/update?flight_plan_id=2', $firstPositionData);
        $I->seeResponseCodeIs(200);

        $firstPosition = LiveFlightPosition::findOne(['submitted_flight_plan_id' => 2]);
        $I->assertNotNull($firstPosition);
        $firstUpdatedAt = $firstPosition->updated_at;

        // Wait a moment to ensure timestamp changes
        sleep(1);

        // Second update with new position
        $secondPositionData = [
            'latitude' => 42.0,
            'longitude' => -5.0,
            'altitude' => 32000,
            'heading' => 90,
            'ground_speed' => 420,
        ];

        $I->sendPOST('/live-position/update?flight_plan_id=2', $secondPositionData);
        $I->seeResponseCodeIs(200);

        // Refresh the model to get updated data
        $updatedPosition = LiveFlightPosition::findOne(['submitted_flight_plan_id' => 2]);
        $I->assertNotNull($updatedPosition);
        $I->assertEquals(42.0, $updatedPosition->latitude);
        $I->assertEquals(-5.0, $updatedPosition->longitude);
        $I->assertEquals(32000, $updatedPosition->altitude);
        $I->assertEquals(90, $updatedPosition->heading);
        $I->assertEquals(420, $updatedPosition->ground_speed);

        // Verify updated_at changed
        $I->assertGreaterThan($firstUpdatedAt, $updatedPosition->updated_at);
    }
}
