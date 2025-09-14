<?php

namespace tests\functional\aircraft;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use app\models\Aircraft;
use Yii;

class AircraftMoveCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class
        ];
    }

    /**
     * Guest cannot access aircraft move action
     */
    public function moveAircraftAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft/move', ['id' => 1]);
        $I->seeResponseCodeIs(403);
        $I->see('You do not have permission to move this aircraft.');
    }

    /**
     * User without aircraftCrud permission cannot move
     */
    public function moveAircraftWithoutPermission(\FunctionalTester $I)
    {
        $I->amLoggedInAs(3); // pilot role, not aircraftCrud
        $I->amOnRoute('aircraft/move', ['id' => 1]);
        $I->seeResponseCodeIs(403);
        $I->see('You do not have permission to move this aircraft.');
    }

    /**
     * Successful aircraft move
     */
    public function moveAircraftToNewAirport(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // admin with aircraftCrud
        $I->amOnRoute('aircraft/move', ['id' => 1]);

        $I->seeResponseCodeIs(200);
        $I->see('Move aircraft');

        $I->fillField('#aircraft-location', 'LEMD');
        $I->click('Move');

        $I->seeResponseCodeIs(200);
        $I->see('Aircraft has been moved to LEMD airport.');

        $aircraft = Aircraft::findOne(1);
        $I->assertEquals('LEMD', $aircraft->location);
    }

    /**
     * Invalid ICAO code shows error
     */
    public function moveAircraftInvalidIcao(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/move', ['id' => 1]);

        $I->fillField('#aircraft-location', 'XX'); // invalid ICAO
        $I->click('Move');

        $I->seeResponseCodeIs(200);
        $I->see('Must be a valid ICAO code.');
    }

    /**
     * Even if extra POST parameters are sent, only location changes
     */
    public function moveAircraftIgnoresOtherParams(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->sendPOST('/aircraft/move?id=1', [
            'Aircraft' => [
                'location' => 'LEBL',
                'registration' => 'HACKED123',
                'name' => 'Changed Name',
            ],
        ]);

        $I->seeResponseCodeIs(200);

        $aircraft = Aircraft::findOne(1);
        $I->assertEquals('LEBL', $aircraft->location);          // changed
        $I->assertNotEquals('HACKED123', $aircraft->registration); // ignored
        $I->assertNotEquals('Changed Name', $aircraft->name);      // ignored
    }

    /**
     * Aircraft cannot move if it has an active flight plan
     */
    public function moveAircraftWithActiveFlightPlan(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        // Simulate an active flight plan
        Yii::$app->db->createCommand()->insert('submitted_flight_plan', [
            'aircraft_id' => 1,
            'route' => 'TEST',
        ])->execute();

        $I->amOnRoute('aircraft/move', ['id' => 1]);
        $I->seeResponseCodeIs(403);
        $I->see('You cannot change the location of an aircraft with an active submitted flight plan.');
    }
}
