<?php

namespace tests\functional\flight;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightReportFixture;
use FunctionalTester;

class FlightViewLoadSheetCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flightReport'   => FlightReportFixture::class,
        ];
    }

    public function loadSheetNotShownForLegacyFlight(FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 1]);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Load Sheet');
    }

    public function loadSheetShownForModernFlight(FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 10]);
        $I->seeResponseCodeIs(200);
        $I->see('Load Sheet');
        $I->see('Adults');
        $I->see('50');
    }
}
