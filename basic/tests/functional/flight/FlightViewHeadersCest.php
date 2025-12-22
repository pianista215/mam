<?php

namespace tests\functional\flight;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightFixture;
use tests\fixtures\FlightReportFixture;
use FunctionalTester;
use Yii;

class FlightViewHeadersCest
{

    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flightReport' => FlightReportFixture::class,
        ];
    }

    public function openFlightViewTour(FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 1]);
        $I->seeResponseCodeIs(200);
        $I->see('Stage Tour actual reported #1 (LEBL-LEMD)');
    }

    public function openFlightViewRoute(FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 2]);
        $I->seeResponseCodeIs(200);
        $I->see('Route F002');
    }

    public function openFlightViewCharter(FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 6]);
        $I->seeResponseCodeIs(200);
        $I->see('Charter Flight (LEMD-LEBL)');
    }
}
