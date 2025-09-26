<?php

namespace tests\functional\flight;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightFixture;
use tests\fixtures\FlightReportFixture;
use FunctionalTester;
use Yii;

class FlightViewValidationCest
{
    // TODO: Acceptance tests with POST to ensure more than form is not shown

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

    // VFR validator approve/reject VFR flights
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

    // IFR validator can approve/reject all validable flights EXCEPT VFR
    public function ifrValidatorCanValidateAllValidable(FunctionalTester $I)
    {
        // validable flights: 2..4 and 6..8 (V or C >72h), but not id=1 or id=5 (VFR)
        $validable = [2,3,4,6,7,8];

        foreach ($validable as $id) {
            // ifr validator user id = 5
            $I->amLoggedInAs(5);
            $this->submitApprove($I, $id, "Approved by IFR for test $id");

            // Assert record updated
            $this->assertFlightValidated($I, $id, 'F', 5, "Approved by IFR for test $id");
        }
    }

    // IFR validator CANNOT validate a VFR flight
    public function ifrValidatorCannotValidateVfr(FunctionalTester $I)
    {
        $I->amLoggedInAs(5); // ifr validator user id = 5

        // vfr flights: 1,5
        $notValidable = [1,5];

        foreach ($notValidable as $id) {
            // ifr validator user id = 5
            $I->amLoggedInAs(5);
            // Try to access a VFR flight
            $I->amOnRoute('flight/view', ['id' => $id]);
            $I->seeResponseCodeIs(200);

            // Ensure validation form is NOT visible
            $I->dontSee('Validate', 'button');
            $I->dontSee('Reject', 'button');
            $I->dontSeeElement('form[action*="validate"]');
        }

    }

    // VFR cannot validate non-VFR or not-validable flights
    public function vfrValidatorCannotValidateOtherOrInvalid(FunctionalTester $I)
    {
        // non-VFR validable examples (IFR, Y, Z): ids 2,3,4,6,7,8 are not VFR
        $nonVfr = [2,3,4,6,7,8];

        foreach ($nonVfr as $id) {
            $I->amLoggedInAs(4);
            $I->amOnRoute('flight/view', ['id' => $id]);
            $I->seeResponseCodeIs(200);
            // Ensure validation form is NOT visible
            $I->dontSee('Validate', 'button');
            $I->dontSee('Reject', 'button');
            $I->dontSeeElement('form[action*="validate"]');
        }

        // Also check not-validable flights: ids 9 (C <72h), 10 (F), 11 (R)
        $notValidable = [9,10,11];
        foreach ($notValidable as $id) {
            $I->amLoggedInAs(4);
            $I->amOnRoute('flight/view', ['id' => $id]);
            $I->seeResponseCodeIs(200);
            // Ensure validation form is NOT visible
            $I->dontSee('Validate', 'button');
            $I->dontSee('Reject', 'button');
            $I->dontSeeElement('form[action*="validate"]');
        }
    }

    // IFR validator cannot validate not-validable flights
    public function ifrValidatorCannotValidateInvalid(FunctionalTester $I)
    {
        $notValidable = [9,10,11]; // C <72h, F, R
        foreach ($notValidable as $id) {
            $I->amLoggedInAs(5);
            $I->amOnRoute('flight/view', ['id' => $id]);
            $I->seeResponseCodeIs(200);
            // Ensure validation form is NOT visible
            $I->dontSee('Validate', 'button');
            $I->dontSee('Reject', 'button');
            $I->dontSeeElement('form[action*="validate"]');
        }
    }

    // Cannot validate own flight (test both VFR and IFR user)
    public function cannotValidateOwnFlight(FunctionalTester $I)
    {
        // Vfr Validator
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 12]);
        $I->seeResponseCodeIs(200);
        // Ensure validation form is NOT visible
        $I->dontSee('Validate', 'button');
        $I->dontSee('Reject', 'button');
        $I->dontSeeElement('form[action*="validate"]');

        // Ifr Validator
        $I->amLoggedInAs(5);
        $I->amOnRoute('flight/view', ['id' => 13]);
        $I->seeResponseCodeIs(200);
        // Ensure validation form is NOT visible
        $I->dontSee('Validate', 'button');
        $I->dontSee('Reject', 'button');
        $I->dontSeeElement('form[action*="validate"]');
    }
}
