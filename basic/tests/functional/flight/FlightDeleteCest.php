<?php

namespace tests\functional\flight;

use app\models\Aircraft;
use app\models\Flight;
use app\models\FlightReport;
use app\models\Pilot;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ConfigFixture;
use tests\fixtures\FlightReportFixture;

class FlightDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'config' => ConfigFixture::class,
            'flightReport' => FlightReportFixture::class,
        ];
    }

    // Flight id=3: pilot_id=7, status='V' (pending validation)
    // Flight id=1: pilot_id=5, status='F' (finished)

    public function deleteButtonVisibleForOwnerWithPendingValidation(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->amOnRoute('flight/view', ['id' => 3]);

        $I->see('Delete flight');
    }

    public function deleteButtonNotVisibleForOwnerWithFinishedFlight(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $I->amOnRoute('flight/view', ['id' => 1]);

        $I->dontSee('Delete flight');
    }

    public function deleteButtonNotVisibleForNonOwner(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $I->amOnRoute('flight/view', ['id' => 3]);

        $I->dontSee('Delete flight');
    }

    public function deleteButtonNotVisibleForGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('flight/view', ['id' => 3]);

        $I->seeCurrentUrlMatches('~login~');
    }

    public function deleteOnlyPostAsOwner(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->amOnRoute('flight/delete', ['id' => 3]);

        $I->seeResponseCodeIs(405);
        $count = Flight::find()->where(['id' => 3])->count();
        $I->assertEquals(1, $count);
    }

    public function deleteOnlyPostAsNonOwner(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $I->amOnRoute('flight/delete', ['id' => 3]);

        $I->seeResponseCodeIs(405);
        $count = Flight::find()->where(['id' => 3])->count();
        $I->assertEquals(1, $count);
    }

    public function deleteOnlyPostAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('flight/delete', ['id' => 3]);

        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function nonOwnerCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $I->sendAjaxPostRequest('/flight/delete?id=3');

        $I->seeResponseCodeIs(403);
        $count = Flight::find()->where(['id' => 3])->count();
        $I->assertEquals(1, $count);
    }

    public function ownerCannotDeleteFinishedFlightViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $I->sendAjaxPostRequest('/flight/delete?id=1');

        $I->seeResponseCodeIs(403);
        $count = Flight::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }

    public function ownerCanDeletePendingValidationFlightViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->sendAjaxPostRequest('/flight/delete?id=3');

        $I->seeResponseCodeIsRedirection();
        $count = Flight::find()->where(['id' => 3])->count();
        $I->assertEquals(0, $count);
    }

    public function ownerCanDeleteFlightAndChunkFiles(\FunctionalTester $I)
    {
        // Setup: create chunks directory and test file
        $chunksPath = '/tmp/mam_test_chunks/3';
        if (!is_dir($chunksPath)) {
            mkdir($chunksPath, 0777, true);
        }
        file_put_contents($chunksPath . '/test_chunk.dat', 'test data');

        $I->assertTrue(is_dir($chunksPath));

        // Execute delete
        $I->amLoggedInAs(7);
        $I->sendAjaxPostRequest('/flight/delete?id=3');

        // Verify flight is deleted
        $I->seeResponseCodeIsRedirection();
        $count = Flight::find()->where(['id' => 3])->count();
        $I->assertEquals(0, $count);

        // Verify chunks directory was deleted
        $I->assertFalse(is_dir($chunksPath));
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/flight/delete?id=3');

        $I->seeResponseCodeIsRedirection();
        $count = Flight::find()->where(['id' => 3])->count();
        $I->assertEquals(1, $count);
    }

    // Flight id=3: pilot_id=7 (hours_flown=12.5), aircraft_id=7 (hours_flown=200.7)
    public function deleteFlightWithHoursRevertsHours(\FunctionalTester $I)
    {
        // Set flight_time_minutes on the report (60 minutes = 1 hour)
        $report = FlightReport::findOne(3);
        $report->flight_time_minutes = 60;
        $report->save(false);

        $pilotBefore = Pilot::findOne(7);
        $aircraftBefore = Aircraft::findOne(7);
        $I->assertEquals(12.5, $pilotBefore->hours_flown);
        $I->assertEquals(200.7, $aircraftBefore->hours_flown);

        $I->amLoggedInAs(7);
        $I->sendAjaxPostRequest('/flight/delete?id=3');

        $I->seeResponseCodeIsRedirection();
        $I->assertEquals(0, Flight::find()->where(['id' => 3])->count());

        // Verify hours were reverted (subtracted 1 hour)
        $pilotAfter = Pilot::findOne(7);
        $aircraftAfter = Aircraft::findOne(7);
        $I->assertEquals(11.5, $pilotAfter->hours_flown);
        $I->assertEquals(199.7, $aircraftAfter->hours_flown);
    }

    public function deleteFlightWithoutHoursDoesNotChangeHours(\FunctionalTester $I)
    {
        // Ensure flight_time_minutes is null (simulating 72h passed without processing)
        $report = FlightReport::findOne(3);
        $report->flight_time_minutes = null;
        $report->save(false);

        $pilotBefore = Pilot::findOne(7);
        $aircraftBefore = Aircraft::findOne(7);
        $I->assertEquals(12.5, $pilotBefore->hours_flown);
        $I->assertEquals(200.7, $aircraftBefore->hours_flown);

        $I->amLoggedInAs(7);
        $I->sendAjaxPostRequest('/flight/delete?id=3');

        $I->seeResponseCodeIsRedirection();
        $I->assertEquals(0, Flight::find()->where(['id' => 3])->count());

        // Verify hours were NOT changed
        $pilotAfter = Pilot::findOne(7);
        $aircraftAfter = Aircraft::findOne(7);
        $I->assertEquals(12.5, $pilotAfter->hours_flown);
        $I->assertEquals(200.7, $aircraftAfter->hours_flown);
    }
}
