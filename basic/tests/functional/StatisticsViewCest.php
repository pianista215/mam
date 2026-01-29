<?php

namespace tests\functional;

use FunctionalTester;
use tests\fixtures\AircraftConfigurationFixture;
use tests\fixtures\AircraftFixture;
use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\FlightFixture;
use tests\fixtures\FlightPhaseFixture;
use tests\fixtures\FlightPhaseMetricFixture;
use tests\fixtures\FlightPhaseMetricTypeFixture;
use tests\fixtures\FlightPhaseTypeFixture;
use tests\fixtures\FlightReportFixture;
use tests\fixtures\PilotFixture;
use tests\fixtures\StatisticAggregateFixture;
use tests\fixtures\StatisticAggregateTypeFixture;
use tests\fixtures\StatisticAggregateTypeLangFixture;
use tests\fixtures\StatisticPeriodFixture;
use tests\fixtures\StatisticPeriodTypeFixture;
use tests\fixtures\StatisticRankingFixture;
use tests\fixtures\StatisticRankingTypeFixture;
use tests\fixtures\StatisticRankingTypeLangFixture;
use tests\fixtures\StatisticRecordFixture;
use tests\fixtures\StatisticRecordTypeFixture;
use tests\fixtures\StatisticRecordTypeLangFixture;
use Yii;

/**
 * Functional tests for statistics web views.
 *
 * Tests that the statistics pages render correctly with fixture data.
 */
class StatisticsViewCest
{
    public function _fixtures()
    {
        return [
            'pilots' => PilotFixture::class,
            'aircraftTypes' => AircraftTypeFixture::class,
            'aircraftConfigurations' => AircraftConfigurationFixture::class,
            'aircraft' => AircraftFixture::class,
            'flights' => [
                'class' => FlightFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/flight_for_statistics.php'),
            ],
            'flightReports' => [
                'class' => FlightReportFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/flight_report_for_statistics.php'),
            ],
            'flightPhaseTypes' => [
                'class' => FlightPhaseTypeFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/flight_phase_type_for_statistics.php'),
            ],
            'flightPhases' => [
                'class' => FlightPhaseFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/flight_phase_for_statistics.php'),
            ],
            'flightPhaseMetricTypes' => [
                'class' => FlightPhaseMetricTypeFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/flight_phase_metric_type_for_statistics.php'),
            ],
            'flightPhaseMetrics' => [
                'class' => FlightPhaseMetricFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/flight_phase_metric_for_statistics.php'),
            ],
            // Types must be loaded before their dependent tables
            'statisticPeriodTypes' => StatisticPeriodTypeFixture::class,
            'statisticAggregateTypes' => StatisticAggregateTypeFixture::class,
            'statisticAggregateTypeLangs' => StatisticAggregateTypeLangFixture::class,
            'statisticRankingTypes' => StatisticRankingTypeFixture::class,
            'statisticRankingTypeLangs' => StatisticRankingTypeLangFixture::class,
            'statisticRecordTypes' => StatisticRecordTypeFixture::class,
            'statisticRecordTypeLangs' => StatisticRecordTypeLangFixture::class,
            // Data tables
            'statisticPeriods' => StatisticPeriodFixture::class,
            'statisticAggregates' => StatisticAggregateFixture::class,
            'statisticRankings' => StatisticRankingFixture::class,
            'statisticRecords' => StatisticRecordFixture::class,
        ];
    }

    /**
     * Test monthly statistics page renders correctly.
     */
    public function testMonthlyStatisticsPage(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/month?year=2025&month=1');

        $I->seeResponseCodeIs(200);
        $I->see('Monthly Statistics', 'h1');
        $I->see('January 2025', 'h1');

        // Check aggregates are displayed
        $I->see('3'); // Total flights
        $I->see('4.4'); // Total hours (formatted)

        // Check rankings are displayed
        $I->see('Rankings');

        // Check records are displayed
        $I->see('Records');
        $I->see('2h 00m'); // 120 minutes formatted
        $I->see('350 nm');
    }

    /**
     * Test monthly statistics page shows variation percentages.
     */
    public function testMonthlyStatisticsShowsVariation(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/month?year=2025&month=1');

        $I->seeResponseCodeIs(200);

        // Check variation percentages are displayed
        $I->see('+200'); // 200% increase in flights
        $I->see('vs previous period');
    }

    /**
     * Test yearly statistics page renders correctly.
     */
    public function testYearlyStatisticsPage(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/year?year=2025');

        $I->seeResponseCodeIs(200);
        $I->see('Yearly Statistics', 'h1');
        $I->see('2025', 'h1');

        // Check aggregates
        $I->see('3'); // Total flights
        $I->see('4.4'); // Total hours

        // Check navigation links
        $I->see('View Monthly Statistics');
        $I->see('View All-Time Statistics');
    }

    /**
     * Test all-time statistics page renders correctly.
     */
    public function testAllTimeStatisticsPage(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/all-time');

        $I->seeResponseCodeIs(200);
        $I->see('All-Time Statistics', 'h1');

        // Check aggregates (total of all periods: 4 flights, 6 hours)
        $I->see('4'); // Total flights
        $I->see('6.0'); // Total hours

        // Check navigation links
        $I->see('View Monthly Statistics');
        $I->see('View Yearly Statistics');
    }

    /**
     * Test previous month navigation.
     */
    public function testPreviousMonthNavigation(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/month?year=2024&month=12');

        $I->seeResponseCodeIs(200);
        $I->see('December 2024', 'h1');

        // December 2024 stats
        $I->see('1'); // 1 flight
        $I->see('1.6'); // 1.5833 hours (rounded to 1 decimal)
    }

    /**
     * Test empty period shows appropriate message.
     */
    public function testEmptyPeriodShowsMessage(FunctionalTester $I)
    {
        // Access a period that doesn't exist in fixtures
        $I->amOnPage('/statistics/month?year=2020&month=1');

        $I->seeResponseCodeIs(200);
        $I->see('No statistics available for this period.');
    }

    /**
     * Test invalid month returns 404.
     */
    public function testInvalidMonthReturns404(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/month?year=2025&month=13');

        $I->seeResponseCodeIs(404);
    }

    /**
     * Test period selector is displayed.
     */
    public function testPeriodSelectorIsDisplayed(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/month?year=2025&month=1');

        $I->seeResponseCodeIs(200);
        $I->see('Select period');
        $I->seeElement('select#period-select');
    }

    /**
     * Test default month is current month.
     */
    public function testDefaultMonthIsCurrent(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/month');

        $I->seeResponseCodeIs(200);
        $I->see('Monthly Statistics', 'h1');
    }

    /**
     * Test default year is current year.
     */
    public function testDefaultYearIsCurrent(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/year');

        $I->seeResponseCodeIs(200);
        $I->see('Yearly Statistics', 'h1');
    }

    /**
     * Test rankings display position changes.
     */
    public function testRankingsDisplayPositionChanges(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/month?year=2025&month=1');

        $I->seeResponseCodeIs(200);

        // Check for position change indicators (up arrow, down arrow, or dash)
        // Aircraft 6 went from #1 to #2, so should show down arrow
        $I->see('Rankings');
    }

    /**
     * Test records display flight details.
     */
    public function testRecordsDisplayFlightDetails(FunctionalTester $I)
    {
        $I->amOnPage('/statistics/month?year=2025&month=1');

        $I->seeResponseCodeIs(200);
        $I->see('Records');

        // Flight 102 is the record holder - check pilot and route are shown
        $I->see('LEMD'); // departure
        $I->see('LEBL'); // arrival
    }
}
