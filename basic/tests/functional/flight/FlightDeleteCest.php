<?php

namespace tests\functional\flight;

use app\models\Flight;
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
}
