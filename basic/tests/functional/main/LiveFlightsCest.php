<?php

namespace tests\functional\main;

use app\models\LiveFlightPosition;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ConfigFixture;
use tests\fixtures\LiveFlightPositionFixture;
use tests\fixtures\PageContentFixture;

class LiveFlightsCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'config' => ConfigFixture::class,
            'pageContent' => PageContentFixture::class,
            'liveFlightPosition' => LiveFlightPositionFixture::class,
        ];
    }

    public function testLiveFlightsSectionDisplayed(\FunctionalTester $I)
    {
        $I->amOnRoute('/');

        // Should see the Live Flights section
        $I->see('Live Flights', 'h4');

        // Should see the map container
        $I->seeElement('#liveFlightsMap');
    }

    public function testActiveRouteFlightsAreDisplayed(\FunctionalTester $I)
    {
        $I->amOnRoute('/');

        // Pilot 5 (AB3456) with FPL 1 - active route flight (LEBL -> GCLP)
        $I->see('AB3456', '.live-flight-row');
        $I->see('Ifr V.', '.live-flight-row');
        $I->see('GCLP', '.live-flight-row');

        // Pilot 6 (Z1234) with FPL 2 - active route flight (LEBL -> LEVC)
        $I->see('Z1234', '.live-flight-row');
        $I->see('Vfr S.', '.live-flight-row');
    }

    public function testActiveTourFlightIsDisplayed(\FunctionalTester $I)
    {
        $I->amOnRoute('/');

        // Pilot 8 (AB6789) with FPL 4 - active tour flight (LEBL -> LEMD)
        $I->see('AB6789', '.live-flight-row');
        $I->see('Other Ifr S.', '.live-flight-row');
        $I->see('LEMD', '.live-flight-row');
    }

    public function testActiveCharterFlightIsDisplayed(\FunctionalTester $I)
    {
        $I->amOnRoute('/');

        // Pilot 4 (AB2345) with FPL 5 - active charter flight (LEBL -> LEVC)
        $I->see('AB2345', '.live-flight-row');
        $I->see('Vfr V.', '.live-flight-row');
        $I->see('LEVC', '.live-flight-row');
    }

    public function testStaleFlightsAreNotDisplayed(\FunctionalTester $I)
    {
        $I->amOnRoute('/');

        // Pilot 7 (AB5678) with FPL 3 - stale flight (more than 2 minutes old)
        // Should NOT be displayed in live flights section
        $I->dontSee('AB5678', '.live-flight-row');
        $I->dontSeeElement('.live-flight-row[data-flight-id="3"]');
    }

    public function testLiveFlightRowsHaveCorrectData(\FunctionalTester $I)
    {
        $I->amOnRoute('/');

        // Check that active flight rows have data-flight-id attributes
        $I->seeElement('.live-flight-row[data-flight-id="1"]'); // Route
        $I->seeElement('.live-flight-row[data-flight-id="2"]'); // Route
        $I->seeElement('.live-flight-row[data-flight-id="4"]'); // Tour
        $I->seeElement('.live-flight-row[data-flight-id="5"]'); // Charter

        // Stale flight should not have a row
        $I->dontSeeElement('.live-flight-row[data-flight-id="3"]');
    }

    public function testAltitudeIsDisplayed(\FunctionalTester $I)
    {
        $I->amOnRoute('/');

        // Check altitude is shown for active flights in the live flights section
        $I->see('35,000 ft', '.live-flight-row'); // FPL 1 - Route
        $I->see('28,000 ft', '.live-flight-row'); // FPL 2 - Route
        $I->see('38,000 ft', '.live-flight-row'); // FPL 4 - Tour
        $I->see('25,000 ft', '.live-flight-row'); // FPL 5 - Charter
    }

    public function testAllFlightTypesShowDepartureArrival(\FunctionalTester $I)
    {
        $I->amOnRoute('/');

        // All active flights depart from LEBL
        // Count occurrences of LEBL badges (should be 4 for active flights)
        $I->seeNumberOfElements('.live-flight-row .badge:contains("LEBL")', 4);
    }

    public function testNoLiveFlightsSectionWhenEmpty(\FunctionalTester $I)
    {
        // Delete all live flight positions to simulate no active flights
        LiveFlightPosition::deleteAll();

        $I->amOnRoute('/');

        // Should NOT see the Live Flights section
        $I->dontSee('Live Flights', 'h4');
        $I->dontSeeElement('#liveFlightsMap');
    }
}
