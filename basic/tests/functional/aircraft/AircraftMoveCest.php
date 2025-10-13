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
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    /**
     * User without aircraftCrud permission cannot move
     */
    public function moveAircraftWithoutPermission(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1); // pilot role, not moveAircraft
        $I->amOnRoute('aircraft/move', ['id' => 1]);
        $I->seeResponseCodeIs(403);
        $I->see('You do not have permission to move this aircraft.');
    }

    /**
     * Successful aircraft move
     */
    public function moveAircraftToNewAirportAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // admin with moveAircraft
        $I->amOnRoute('aircraft/move', ['id' => 1]);

        $I->seeResponseCodeIs(200);
        $I->see('Move aircraft');

        $I->fillField('#aircraft-location', 'LEBL');
        $I->click('Move', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Aircraft has been moved to LEBL airport.');

        $aircraft = Aircraft::findOne(1);
        $I->assertEquals('LEBL', $aircraft->location);
    }

    /**
     * Successful aircraft move
     */
    public function moveAircraftToNewAirportFleetMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(9); // fleetMgr with moveAircraft
        $I->amOnRoute('aircraft/move', ['id' => 1]);

        $I->seeResponseCodeIs(200);
        $I->see('Move aircraft');

        $I->fillField('#aircraft-location', 'LEBL');
        $I->click('Move', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Aircraft has been moved to LEBL airport.');

        $aircraft = Aircraft::findOne(1);
        $I->assertEquals('LEBL', $aircraft->location);
    }

    /**
     * Invalid ICAO code shows error
     */
    public function moveAircraftInvalidIcao(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/move', ['id' => 1]);

        $I->fillField('#aircraft-location', 'XX');
        $I->click('Move', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Location should contain 4 characters.');

        $aircraft = Aircraft::findOne(1);
        $I->assertNotEquals('XX', $aircraft->location);
    }

    /**
     * Airport doesn't exist
     */
    public function moveAircraftAirportNotExists(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/move', ['id' => 1]);

        $I->fillField('#aircraft-location', 'XXXX');
        $I->click('Move', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Airport (ICAO) Location is invalid');
        $aircraft = Aircraft::findOne(1);
        $I->assertNotEquals('XXXX', $aircraft->location);
    }

    /**
     * Aircraft cannot move if it has an active flight plan
     */
    public function moveAircraftWithActiveFlightPlan(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/move', ['id' => 5]); // Aircraft 5 is on active flight plan
        $I->seeResponseCodeIs(403);
        $I->see('You cannot change the location of an aircraft with an active submitted flight plan.');
    }
}
