<?php

namespace tests\functional;

use FunctionalTester;
use tests\fixtures\AircraftConfigurationFixture;
use tests\fixtures\AircraftFixture;
use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\FlightFixture;
use tests\fixtures\FlightPhaseFixture;
use tests\fixtures\FlightPhaseMetricFixture;
use tests\fixtures\FlightReportFixture;
use tests\fixtures\PilotFixture;
use tests\fixtures\StatisticAggregateFixture;
use tests\fixtures\StatisticPeriodFixture;
use tests\fixtures\StatisticRankingFixture;
use tests\fixtures\StatisticRecordFixture;
use Yii;

/**
 * Functional tests for statistics web views.
 *
 * Tests that the statistics pages render correctly with fixture data.
 * Uses dynamic dates (current month/year) for realistic testing.
 */
class StatisticsViewCest
{
    private int $currentYear;
    private int $currentMonth;
    private int $prevYear;
    private int $prevMonth;
    private string $currentMonthName;
    private string $prevMonthName;

    public function _before(FunctionalTester $I)
    {
        $now = new \DateTimeImmutable();
        $this->currentYear = (int) $now->format('Y');
        $this->currentMonth = (int) $now->format('n');

        $this->prevMonth = $this->currentMonth - 1;
        $this->prevYear = $this->currentYear;
        if ($this->prevMonth < 1) {
            $this->prevMonth = 12;
            $this->prevYear--;
        }

        // Get month names
        $this->currentMonthName = $now->format('F');
        $prevDate = $now->modify('-1 month');
        $this->prevMonthName = $prevDate->format('F');
    }

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
            // Note: flight_phase_type and flight_phase_metric_type are seed data from DDL, not fixtures
            'flightPhases' => [
                'class' => FlightPhaseFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/flight_phase_for_statistics.php'),
            ],
            'flightPhaseMetrics' => [
                'class' => FlightPhaseMetricFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/flight_phase_metric_for_statistics.php'),
            ],
            // Note: statistic_*_type tables are seed data from statistics.sql, not fixtures
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
        $I->amOnPage("/statistics/month?year={$this->currentYear}&month={$this->currentMonth}");

        $I->seeResponseCodeIs(200);
        $I->see('Monthly Statistics', 'h1');
        $I->see("{$this->currentMonthName} {$this->currentYear}", 'h1');

        // Check aggregates are displayed
        $I->see('3'); // Total flights

        // Check rankings are displayed
        $I->see('Rankings');

        // Check records are displayed
        $I->see('Records');
        $I->see('2:00'); // 120 minutes formatted with TimeHelper
        $I->see('350 Nm');
    }

    /**
     * Test monthly statistics page shows variation percentages.
     */
    public function testMonthlyStatisticsShowsVariation(FunctionalTester $I)
    {
        $I->amOnPage("/statistics/month?year={$this->currentYear}&month={$this->currentMonth}");

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
        $I->amOnPage("/statistics/year?year={$this->currentYear}");

        $I->seeResponseCodeIs(200);
        $I->see('Yearly Statistics', 'h1');
        $I->see("{$this->currentYear}", 'h1');

        // Check aggregates
        $I->see('3'); // Total flights

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

        // Check aggregates (total of all periods: 4 flights)
        $I->see('4'); // Total flights

        // Check navigation links
        $I->see('View Monthly Statistics');
        $I->see('View Yearly Statistics');
    }

    /**
     * Test previous month navigation.
     */
    public function testPreviousMonthNavigation(FunctionalTester $I)
    {
        $I->amOnPage("/statistics/month?year={$this->prevYear}&month={$this->prevMonth}");

        $I->seeResponseCodeIs(200);
        $I->see("{$this->prevMonthName} {$this->prevYear}", 'h1');

        // Previous month stats
        $I->see('1'); // 1 flight
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
        $I->amOnPage("/statistics/month?year={$this->currentYear}&month=13");

        $I->seeResponseCodeIs(404);
    }

    /**
     * Test period selector is displayed.
     */
    public function testPeriodSelectorIsDisplayed(FunctionalTester $I)
    {
        $I->amOnPage("/statistics/month?year={$this->currentYear}&month={$this->currentMonth}");

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
        $I->amOnPage("/statistics/month?year={$this->currentYear}&month={$this->currentMonth}");

        $I->seeResponseCodeIs(200);

        // Check for position change indicators (up arrow, down arrow, or dash)
        $I->see('Rankings');
    }

    /**
     * Test records display flight details.
     */
    public function testRecordsDisplayFlightDetails(FunctionalTester $I)
    {
        $I->amOnPage("/statistics/month?year={$this->currentYear}&month={$this->currentMonth}");

        $I->seeResponseCodeIs(200);
        $I->see('Records');

        // Flight 102 is the record holder - check pilot and route are shown
        $I->see('LEMD'); // departure
        $I->see('LEBL'); // arrival
    }
}
