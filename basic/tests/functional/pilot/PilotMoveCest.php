<?php

namespace tests\functional\pilot;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use app\models\Pilot;
use Yii;

class PilotMoveCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    /**
     * Guest users cannot access move action
     */
    public function movePilotAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot/move');
        $I->seeResponseCodeIs(403);
        $I->see('You must be logged in to move your pilot.');
    }

    /**
     * Base case: the pilot successfully moves to another airport
     */
    public function movePilotToNewAirport(\FunctionalTester $I)
    {
        $I->amLoggedInAs(3);
        $I->amOnRoute('pilot/move');

        $I->seeResponseCodeIs(200);
        $I->see('Move pilot');

        $I->fillField('#pilot-location', 'LEMD');
        $I->click('Move');

        $I->seeResponseCodeIs(200);
        $I->see('Now you are at LEMD airport');

        $pilot = Pilot::findOne(3);
        $I->assertEquals('LEMD', $pilot->location);
    }

    /**
     * Error when airport code is invalid (ICAO validation)
     */
    public function movePilotInvalidIcao(\FunctionalTester $I)
    {
        $I->amLoggedInAs(3);
        $I->amOnRoute('pilot/move');

        $I->fillField('#pilot-location', 'XX'); // invalid
        $I->click('Move');

        $I->seeResponseCodeIs(200);
        $I->see('Airport (ICAO) Location should contain 4 characters');

        $pilot = Pilot::findOne(3);
        $I->assertNotEquals('XX', $pilot->location);
    }

    /**
     * Error when airport doesn't exist
     */
    public function movePilotAirportNotExists(\FunctionalTester $I)
    {
        $I->amLoggedInAs(3);
        $I->amOnRoute('pilot/move');

        $I->fillField('#pilot-location', 'XXXX');
        $I->click('Move');

        $I->seeResponseCodeIs(200);
        $I->see('Airport (ICAO) Location is invalid');

        $pilot = Pilot::findOne(3);
        $I->assertNotEquals('XXXX', $pilot->location);
    }

    /**
     * Pilot cannot move if there is an active Flight Plan
     */
    public function movePilotWithActiveFlightPlan(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->amOnRoute('pilot/move');
        $I->seeResponseCodeIs(403);
        $I->see('You cannot change your location with an active submitted flight plan.');
    }
}
