<?php

namespace tests\functional\flight;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightFixture;
use tests\fixtures\FlightReportFixture;
use FunctionalTester;
use Yii;

class FlightViewValidationCest
{

    protected function _before(FunctionalTester $I)
    {
        $I->amLoggedOut();
    }

    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flight' => [
                'class' => FlightFixture::class,
                'dataFile' => __DIR__ . '/../../fixtures/data/flight_long_for_validations.php',
            ],
            'flightReport' => [
                'class' => FlightReportFixture::class,
                'dataFile' => __DIR__ . '/../../fixtures/data/flight_report_for_validations.php',
            ],
        ];
    }

    protected function assertFlightValidated(
        FunctionalTester $I,
        int $flightId,
        string $expectedStatus,
        int $expectedValidatorId,
        string $expectedComment
    ) {
        $flight = \app\models\Flight::findOne($flightId);
        $I->assertNotNull($flight, "Flight {$flightId} doesn't exist in DB");

        $I->assertEquals($expectedStatus, $flight->status, "Incorrect status {$flightId}");
        $I->assertEquals($expectedValidatorId, $flight->validator_id, "Incorrect validator for flight {$flightId}");
        $I->assertEquals($expectedComment, $flight->validator_comments, "Incorrect validator comment for {$flightId}");
        $I->assertNotEmpty($flight->validation_date, 'validation_date should be set after approve');
    }

    protected function submitValidation(FunctionalTester $I, int $flightId, string $action = 'approve', string $comment = null)
    {
        $I->amOnRoute('flight/view', ['id' => $flightId]);
        $I->seeResponseCodeIs(200);

        $I->see('Validate', 'button');
        $I->see('Reject', 'button');

        // Ensure validation form is there
        $I->seeElement('form[action*="validate"]');
        $I->submitForm('form[action*="validate"]', [
            'action' => $action,
            'Flight[validator_comments]' => $comment,
        ]);

        // After submit usually redirect to view; ensure we got 200
        $I->seeResponseCodeIs(200);

        $I->dontSeeElement('form[action*="validate"]');
        $I->dontSee('Validate', 'button');
        $I->dontSee('Reject', 'button');
    }

    /**
     * Helper: submit approve on validation form in view
     */
    protected function submitApprove(FunctionalTester $I, int $flightId, string $comment = 'OK')
    {
        $this->submitValidation($I, $flightId, 'approve', $comment);
    }

    protected function submitReject(FunctionalTester $I, int $flightId, string $comment = 'Rejected')
    {
        $this->submitValidation($I, $flightId, 'reject', $comment);
    }

    // 1 & 2: VFR validator approve/reject VFR flights
    public function vfrValidatorApproveAndReject(FunctionalTester $I)
    {
        // vfr validator user id = 4
        $I->amLoggedInAs(4);

        // --- Approve a VFR flight in status V (fixture id = 1)
        $this->submitApprove($I, 1, 'Approved by VFR');

        // DB checks
        $this->assertFlightValidated($I, 1, 'F', 4, 'Approved by VFR');

        // --- Reject a VFR flight in status C (>72h) (fixture id = 5)
        // Reload fixture is automatic per test; flight 5 is still in initial state
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 5]);
        $I->see('Validate');
        $I->see('Reject');

        $this->submitReject($I, 5, 'Rejected by VFR');

        $this->assertFlightValidated($I, 5, 'R', 4, 'Rejected by VFR');
    }

    // 3 & 4: IFR validator can approve/reject all validable flights (ids 1..8)
    public function ifrValidatorCanValidateAllValidable(FunctionalTester $I)
    {
        // validable flights ids per fixture: 1..8 (V or C >72h)
        $validable = [1,2,3,4,5,6,7,8];

        foreach ($validable as $id) {
            // ifr validator user id = 5
            $I->amLoggedInAs(5);
            $this->submitApprove($I, $id, "Approved by IFR for test $id");

            // Assert record updated
            $this->assertFlightValidated($I, $id, 'F', 5, "Approved by IFR for test $id");
        }
    }

    // 5 & 6: VFR cannot validate non-VFR or not-validable flights
    public function vfrValidatorCannotValidateOtherOrInvalid(FunctionalTester $I)
    {
        $I->amLoggedInAs(4);

        // non-VFR validable examples (IFR, Y, Z): ids 2,3,4,6,7,8 are not VFR
        $nonVfr = [2,3,4,6,7,8];

        foreach ($nonVfr as $id) {
            $I->amOnRoute('flight/view', ['id' => $id]);
            $I->seeResponseCodeIs(200);
            // UI should not show validate buttons for VFR validator on non-VFR flights
            $I->dontSee('Validate');
            $I->dontSee('Reject');

            // Try to force a POST anyway -> should be forbidden (403)
            $I->sendPOST(['flight/validate', 'id' => $id], [
                'action' => 'approve',
                'Flight[validator_comments]' => 'Try to approve'
            ]);
            $I->seeResponseCodeIs(403);
        }

        // Also check not-validable flights: ids 9 (C <72h), 10 (F), 11 (R)
        $notValidable = [9,10,11];
        foreach ($notValidable as $id) {
            $I->amOnRoute('flight/view', ['id' => $id]);
            $I->seeResponseCodeIs(200);
            $I->dontSee('Validate');
            $I->dontSee('Reject');

            $I->sendPOST(['flight/validate', 'id' => $id], [
                'action' => 'approve',
                'Flight[validator_comments]' => 'Try to approve not validable'
            ]);
            $I->seeResponseCodeIs(403);
        }
    }

    // 7: IFR validator cannot validate not-validable flights
    public function ifrValidatorCannotValidateInvalid(FunctionalTester $I)
    {
        $I->amLoggedInAs(5);

        $notValidable = [9,10,11]; // C <72h, F, R
        foreach ($notValidable as $id) {
            $I->amOnRoute('flight/view', ['id' => $id]);
            $I->seeResponseCodeIs(200);
            $I->dontSee('Validate');
            $I->dontSee('Reject');

            $I->sendPOST(['flight/validate', 'id' => $id], [
                'action' => 'approve',
                'Flight[validator_comments]' => 'try'
            ]);
            $I->seeResponseCodeIs(403);
        }
    }

    // 8: Cannot validate own flight (test both VFR and IFR user)
    public function cannotValidateOwnFlight(FunctionalTester $I)
    {
        // create a flight owned by vfr validator (pilot_id = 4) - status V
        $ownVfrId = 200;
        $I->haveRecord('flight', [
            'id' => $ownVfrId,
            'pilot_id' => 4,
            'aircraft_id' => 1,
            'code' => 'OWNVFR',
            'departure' => 'LEBL',
            'arrival' => 'LEMD',
            'alternative1_icao' => 'LEVC',
            'flight_rules' => 'V',
            'cruise_speed_unit' => 'N',
            'cruise_speed_value' => '100',
            'flight_level_unit' => 'F',
            'flight_level_value' => '50',
            'route' => 'DCT',
            'estimated_time' => '0100',
            'other_information' => 'own vfr',
            'endurance_time' => '0200',
            'report_tool' => 'Mam Acars',
            'status' => 'V',
            'creation_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);

        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => $ownVfrId]);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Validate');
        $I->dontSee('Reject');

        $I->sendPOST(['flight/validate', 'id' => $ownVfrId], [
            'action' => 'approve',
            'Flight[validator_comments]' => 'self try'
        ]);
        $I->seeResponseCodeIs(403);

        // create a flight owned by ifr validator (pilot_id = 5)
        $ownIfrId = 201;
        $I->haveRecord('flight', [
            'id' => $ownIfrId,
            'pilot_id' => 5,
            'aircraft_id' => 1,
            'code' => 'OWNIFR',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
            'alternative1_icao' => 'LEVC',
            'flight_rules' => 'I',
            'cruise_speed_unit' => 'N',
            'cruise_speed_value' => '300',
            'flight_level_unit' => 'F',
            'flight_level_value' => '250',
            'route' => 'DCT',
            'estimated_time' => '0200',
            'other_information' => 'own ifr',
            'endurance_time' => '0400',
            'report_tool' => 'Mam Acars',
            'status' => 'V',
            'creation_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);

        $I->amLoggedInAs(5);
        $I->amOnRoute('flight/view', ['id' => $ownIfrId]);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Validate');
        $I->dontSee('Reject');

        $I->sendPOST(['flight/validate', 'id' => $ownIfrId], [
            'action' => 'approve',
            'Flight[validator_comments]' => 'self try'
        ]);
        $I->seeResponseCodeIs(403);
    }
}
