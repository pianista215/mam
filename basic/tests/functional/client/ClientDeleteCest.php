<?php

namespace tests\functional\client;

use app\models\Assignment;
use app\models\Client;
use app\models\ClientHub;
use app\models\Flight;
use app\models\SubmittedFlightPlan;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ClientHubFixture;
use tests\fixtures\FlightFixture;
use tests\fixtures\SubmittedFlightPlanFixture;

class ClientDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'clientHub' => ClientHubFixture::class,
            'flight' => FlightFixture::class,
            // Loads the assignments too (see tests/fixtures/data/assignment.php)
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    public function deleteOnlyPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('client/delete', ['id' => 1]);
        $I->seeResponseCodeIs(405);
        $I->assertEquals(2, Client::find()->count());
    }

    public function adminCanDeleteViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/client/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $I->assertNull(Client::findOne(1));
        $I->assertEquals(0, ClientHub::find()->where(['client_id' => 1])->count());
    }

    public function businessManagerCanDeleteViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->sendAjaxPostRequest('/client/delete?id=2');

        $I->seeResponseCodeIsRedirection();
        $I->assertNull(Client::findOne(2));
    }

    public function userCannotDeleteViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/client/delete?id=1');

        $I->seeResponseCodeIs(403);
        $I->assertNotNull(Client::findOne(1));
    }

    public function guestCannotDeleteViaPost(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/client/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $I->assertNotNull(Client::findOne(1));
    }

    public function deletingClientDeletesItsPendingAssignments(\FunctionalTester $I)
    {
        $I->assertGreaterThan(0, Assignment::find()->where(['client_id' => 2])->count());

        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/client/delete?id=2');

        $I->seeResponseCodeIsRedirection();
        $I->assertNull(Client::findOne(2));
        $I->assertEquals(0, Assignment::find()->where(['client_id' => 2])->count());
        $I->assertGreaterThan(0, Assignment::find()->where(['client_id' => 1])->count());
    }

    public function cannotDeleteClientWithFlights(\FunctionalTester $I)
    {
        $flight = Flight::findOne(2);
        $flight->flight_type = Flight::TYPE_ASSIGNMENT;
        $flight->client_id = 1;
        $I->assertTrue($flight->save(false));

        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/client/delete?id=1');
        $I->seeResponseCodeIsRedirection();
        $I->assertNotNull(Client::findOne(1));

        $I->amOnRoute('client/view', ['id' => 1]);
        $I->see('This client can\'t be deleted because it has flown or booked assignments.');
    }

    public function cannotDeleteClientWithBookedAssignment(\FunctionalTester $I)
    {
        $fpl = SubmittedFlightPlan::findOne(1);
        $fpl->route_id = null;
        $fpl->assignment_id = 4;
        $I->assertTrue($fpl->save(false));

        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/client/delete?id=2');
        $I->seeResponseCodeIsRedirection();
        $I->assertNotNull(Client::findOne(2));
        $I->assertNotNull(Assignment::findOne(4));

        $I->amOnRoute('client/view', ['id' => 2]);
        $I->see('This client can\'t be deleted because it has flown or booked assignments.');
    }

}
