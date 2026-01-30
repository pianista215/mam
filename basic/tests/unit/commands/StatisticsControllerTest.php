<?php

namespace tests\unit\commands;

use app\commands\StatisticsController;
use app\models\Flight;
use app\models\FlightReport;
use app\models\StatisticAggregate;
use app\models\StatisticAggregateType;
use app\models\StatisticPeriod;
use app\models\StatisticPeriodType;
use app\models\StatisticRanking;
use app\models\StatisticRankingType;
use app\models\StatisticRecord;
use app\models\StatisticRecordType;
use tests\unit\BaseUnitTest;
use Yii;
use yii\console\ExitCode;

class StatisticsControllerTest extends BaseUnitTest
{
    private StatisticsController $controller;

    protected function _before()
    {
        parent::_before();
        $this->controller = new StatisticsController('statistics', Yii::$app);
        $this->createTestData();
    }

    /**
     * Create test data for statistics calculations.
     *
     * January 2025 (should count):
     * - Flight 1: pilot 1, aircraft 1, 90 min, 250 nm (status F)
     * - Flight 2: pilot 1, aircraft 2, 120 min, 350 nm (status F)
     * - Flight 3: pilot 2, aircraft 1, 55 min, 180 nm (status F)
     *
     * Should NOT count:
     * - Flight 4: status R (rejected)
     * - Flight 5: status C (created)
     * - Flight 6: status V (pending)
     * - Flight 7: status F but no flight_time_minutes
     *
     * December 2024:
     * - Flight 8: pilot 1, aircraft 2, 95 min, 280 nm (status F)
     */
    private function createTestData(): void
    {
        // Note: statistic_*_type tables are seed data from statistics.sql

        // Create base entities
        $country = new \app\models\Country(['name' => 'Spain', 'iso2_code' => 'ES']);
        $country->save(false);

        $airports = [];
        foreach (['LEMD', 'LEBL', 'LEVC', 'LEAL'] as $icao) {
            $airport = new \app\models\Airport([
                'icao_code' => $icao,
                'name' => $icao . ' Airport',
                'latitude' => 40.0,
                'longitude' => -3.0,
                'city' => 'City',
                'country_id' => $country->id,
            ]);
            $airport->save(false);
            $airports[$icao] = $airport;
        }

        $aircraftType = new \app\models\AircraftType([
            'icao_type_code' => 'B738',
            'name' => 'Boeing 737-800',
            'max_nm_range' => 2900,
        ]);
        $aircraftType->save(false);

        $config = new \app\models\AircraftConfiguration([
            'aircraft_type_id' => $aircraftType->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
        ]);
        $config->save(false);

        $aircraft1 = new \app\models\Aircraft([
            'aircraft_configuration_id' => $config->id,
            'registration' => 'EC-AAA',
            'name' => 'Aircraft 1',
            'location' => 'LEMD',
            'hours_flown' => 0,
        ]);
        $aircraft1->save(false);

        $aircraft2 = new \app\models\Aircraft([
            'aircraft_configuration_id' => $config->id,
            'registration' => 'EC-BBB',
            'name' => 'Aircraft 2',
            'location' => 'LEBL',
            'hours_flown' => 0,
        ]);
        $aircraft2->save(false);

        $pilot1 = new \app\models\Pilot([
            'license' => 'PIL001',
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@test.com',
            'password' => Yii::$app->security->generatePasswordHash('test'),
            'country_id' => $country->id,
            'city' => 'Madrid',
            'location' => 'LEMD',
            'date_of_birth' => '1990-01-01',
        ]);
        $pilot1->save(false);

        $pilot2 = new \app\models\Pilot([
            'license' => 'PIL002',
            'name' => 'Jane',
            'surname' => 'Smith',
            'email' => 'jane@test.com',
            'password' => Yii::$app->security->generatePasswordHash('test'),
            'country_id' => $country->id,
            'city' => 'Barcelona',
            'location' => 'LEBL',
            'date_of_birth' => '1992-05-15',
        ]);
        $pilot2->save(false);

        // Create flights and reports
        $flightData = [
            // January 2025 - should count
            ['pilot' => $pilot1, 'aircraft' => $aircraft1, 'status' => 'F', 'date' => '2025-01-10', 'minutes' => 90, 'nm' => 250],
            ['pilot' => $pilot1, 'aircraft' => $aircraft2, 'status' => 'F', 'date' => '2025-01-15', 'minutes' => 120, 'nm' => 350],
            ['pilot' => $pilot2, 'aircraft' => $aircraft1, 'status' => 'F', 'date' => '2025-01-20', 'minutes' => 55, 'nm' => 180],
            // January 2025 - should NOT count
            ['pilot' => $pilot1, 'aircraft' => $aircraft1, 'status' => 'R', 'date' => '2025-01-11', 'minutes' => 60, 'nm' => 200],
            ['pilot' => $pilot2, 'aircraft' => $aircraft2, 'status' => 'C', 'date' => '2025-01-18', 'minutes' => 45, 'nm' => 150],
            ['pilot' => $pilot1, 'aircraft' => $aircraft2, 'status' => 'V', 'date' => '2025-01-22', 'minutes' => 40, 'nm' => 130],
            ['pilot' => $pilot2, 'aircraft' => $aircraft1, 'status' => 'F', 'date' => '2025-01-27', 'minutes' => null, 'nm' => null],
            // December 2024
            ['pilot' => $pilot1, 'aircraft' => $aircraft2, 'status' => 'F', 'date' => '2024-12-15', 'minutes' => 95, 'nm' => 280],
        ];

        foreach ($flightData as $i => $data) {
            $flight = new Flight([
                'pilot_id' => $data['pilot']->id,
                'aircraft_id' => $data['aircraft']->id,
                'code' => 'TST' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'departure' => 'LEMD',
                'arrival' => 'LEBL',
                'alternative1_icao' => 'LEVC',
                'flight_rules' => 'I',
                'cruise_speed_unit' => 'N',
                'cruise_speed_value' => '350',
                'flight_level_unit' => 'F',
                'flight_level_value' => '320',
                'route' => 'DCT',
                'estimated_time' => '0130',
                'other_information' => 'Test',
                'endurance_time' => '0400',
                'report_tool' => 'Test',
                'status' => $data['status'],
                'creation_date' => $data['date'] . ' 10:00:00',
                'flight_type' => 'R',
            ]);
            $flight->save(false);

            $report = new FlightReport([
                'flight_id' => $flight->id,
                'start_time' => $data['date'] . ' 10:00:00',
                'end_time' => $data['date'] . ' 12:00:00',
                'flight_time_minutes' => $data['minutes'],
                'distance_nm' => $data['nm'],
                'sim_aircraft_name' => 'Test Aircraft',
            ]);
            $report->save(false);
        }
    }

    public function testRecalculateCreatesCorrectAggregates()
    {
        $this->assertEquals(0, StatisticPeriod::find()->count());

        $exitCode = $this->controller->actionRecalculate(2025, 1);

        $this->assertEquals(ExitCode::OK, $exitCode);

        $period = StatisticPeriod::findOne(['year' => 2025, 'month' => 1]);
        $this->assertNotNull($period);
        $this->assertEquals(StatisticPeriod::STATUS_OPEN, $period->status);

        // Verify total_flights (should be 3)
        $totalFlightsType = StatisticAggregateType::findOne(['code' => StatisticAggregateType::CODE_TOTAL_FLIGHTS]);
        $totalFlights = StatisticAggregate::findOne([
            'period_id' => $period->id,
            'aggregate_type_id' => $totalFlightsType->id,
        ]);
        $this->assertEquals(3, (int) $totalFlights->value);

        // Verify total_flight_hours (90+120+55 = 265 min = 4.4167 hours)
        $totalHoursType = StatisticAggregateType::findOne(['code' => StatisticAggregateType::CODE_TOTAL_FLIGHT_HOURS]);
        $totalHours = StatisticAggregate::findOne([
            'period_id' => $period->id,
            'aggregate_type_id' => $totalHoursType->id,
        ]);
        $this->assertEqualsWithDelta(4.4167, (float) $totalHours->value, 0.01);
    }

    public function testRecalculateCreatesCorrectRankings()
    {
        $this->controller->actionRecalculate(2025, 1);

        $period = StatisticPeriod::findOne(['year' => 2025, 'month' => 1]);

        // Verify top_pilots_by_hours (pilot1: 210min=3.5h, pilot2: 55min=0.917h)
        $pilotsByHoursType = StatisticRankingType::findOne(['code' => StatisticRankingType::CODE_TOP_PILOTS_BY_HOURS]);
        $rankings = StatisticRanking::find()
            ->where(['period_id' => $period->id, 'ranking_type_id' => $pilotsByHoursType->id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $this->assertCount(2, $rankings);
        $this->assertEquals(1, $rankings[0]->position);
        $this->assertEqualsWithDelta(3.5, (float) $rankings[0]->value, 0.01);
        $this->assertEquals(2, $rankings[1]->position);

        // Verify top_pilots_by_flights (pilot1: 2, pilot2: 1)
        $pilotsByFlightsType = StatisticRankingType::findOne(['code' => StatisticRankingType::CODE_TOP_PILOTS_BY_FLIGHTS]);
        $rankings = StatisticRanking::find()
            ->where(['period_id' => $period->id, 'ranking_type_id' => $pilotsByFlightsType->id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $this->assertCount(2, $rankings);
        $this->assertEquals(2, (int) $rankings[0]->value);
        $this->assertEquals(1, (int) $rankings[1]->value);

        // Verify top_aircraft_types_by_flights (both aircraft use same type B738, so 3 flights total)
        $aircraftTypeRanking = StatisticRankingType::findOne(['code' => StatisticRankingType::CODE_TOP_AIRCRAFT_TYPES_BY_FLIGHTS]);
        $rankings = StatisticRanking::find()
            ->where(['period_id' => $period->id, 'ranking_type_id' => $aircraftTypeRanking->id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        $this->assertCount(1, $rankings); // Only one aircraft type (B738)
        $this->assertEquals(3, (int) $rankings[0]->value); // All 3 flights use B738
    }

    public function testRecalculateCreatesCorrectRecords()
    {
        $this->controller->actionRecalculate(2025, 1);

        $period = StatisticPeriod::findOne(['year' => 2025, 'month' => 1]);

        // Verify longest_flight_time (flight 2 with 120 min)
        $longestTimeType = StatisticRecordType::findOne(['code' => StatisticRecordType::CODE_LONGEST_FLIGHT_TIME]);
        $record = StatisticRecord::findOne([
            'period_id' => $period->id,
            'record_type_id' => $longestTimeType->id,
        ]);

        $this->assertNotNull($record);
        $this->assertEquals(120, (int) $record->value);
        $this->assertEquals(1, $record->is_all_time_record);

        // Verify longest_flight_distance (flight 2 with 350 nm)
        $longestDistType = StatisticRecordType::findOne(['code' => StatisticRecordType::CODE_LONGEST_FLIGHT_DISTANCE]);
        $record = StatisticRecord::findOne([
            'period_id' => $period->id,
            'record_type_id' => $longestDistType->id,
        ]);

        $this->assertNotNull($record);
        $this->assertEquals(350, (int) $record->value);
    }

    public function testVariationPercentCalculation()
    {
        // First calculate December 2024
        $this->controller->actionRecalculate(2024, 12);

        // Then January 2025
        $this->controller->actionRecalculate(2025, 1);

        $janPeriod = StatisticPeriod::findOne(['year' => 2025, 'month' => 1]);
        $totalFlightsType = StatisticAggregateType::findOne(['code' => StatisticAggregateType::CODE_TOTAL_FLIGHTS]);

        $janAggregate = StatisticAggregate::findOne([
            'period_id' => $janPeriod->id,
            'aggregate_type_id' => $totalFlightsType->id,
        ]);

        // December had 1 flight, January has 3, variation = ((3-1)/1)*100 = 200%
        $this->assertNotNull($janAggregate->variation_percent);
        $this->assertEqualsWithDelta(200.0, (float) $janAggregate->variation_percent, 0.1);
    }

    public function testPreviousPositionTracking()
    {
        // First calculate December 2024
        $this->controller->actionRecalculate(2024, 12);

        // Then January 2025
        $this->controller->actionRecalculate(2025, 1);

        $janPeriod = StatisticPeriod::findOne(['year' => 2025, 'month' => 1]);
        $pilotsByHoursType = StatisticRankingType::findOne(['code' => StatisticRankingType::CODE_TOP_PILOTS_BY_HOURS]);

        $rankings = StatisticRanking::find()
            ->where(['period_id' => $janPeriod->id, 'ranking_type_id' => $pilotsByHoursType->id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        // First position should have previous_position = 1 (was also #1 in December)
        $this->assertEquals(1, $rankings[0]->position);
        $this->assertEquals(1, $rankings[0]->previous_position);

        // Second position should have previous_position = null (wasn't in December)
        $this->assertEquals(2, $rankings[1]->position);
        $this->assertNull($rankings[1]->previous_position);
    }

    public function testOnlyFinishedFlightsWithDataAreCounted()
    {
        $this->controller->actionRecalculate(2025, 1);

        $period = StatisticPeriod::findOne(['year' => 2025, 'month' => 1]);
        $totalFlightsType = StatisticAggregateType::findOne(['code' => StatisticAggregateType::CODE_TOTAL_FLIGHTS]);
        $totalFlights = StatisticAggregate::findOne([
            'period_id' => $period->id,
            'aggregate_type_id' => $totalFlightsType->id,
        ]);

        // Only 3 flights counted (F status with flight_time_minutes)
        // NOT counted: R, C, V status or F without flight_time_minutes
        $this->assertEquals(3, (int) $totalFlights->value);
    }

    public function testConsolidateRecalculatesAllPeriods()
    {
        // Create periods
        $this->controller->actionRecalculate(2024, 12);
        $this->controller->actionRecalculate(2025, 1);

        // Manually modify an aggregate
        $janPeriod = StatisticPeriod::findOne(['year' => 2025, 'month' => 1]);
        $totalFlightsType = StatisticAggregateType::findOne(['code' => StatisticAggregateType::CODE_TOTAL_FLIGHTS]);
        $aggregate = StatisticAggregate::findOne([
            'period_id' => $janPeriod->id,
            'aggregate_type_id' => $totalFlightsType->id,
        ]);
        $aggregate->value = 999;
        $aggregate->save(false);

        // Run consolidate
        $exitCode = $this->controller->actionConsolidate();
        $this->assertEquals(ExitCode::OK, $exitCode);

        // Verify value was recalculated
        $aggregate->refresh();
        $this->assertEquals(3, (int) $aggregate->value);
    }

    public function testConsolidateCreatesMissingPeriods()
    {
        // Start with no periods
        $this->assertEquals(0, StatisticPeriod::find()->count());

        // Run consolidate - should detect flights and create all needed periods
        $exitCode = $this->controller->actionConsolidate();
        $this->assertEquals(ExitCode::OK, $exitCode);

        // Test data has flights in Dec 2024 and Jan 2025
        // Should create: Dec 2024 monthly, Jan 2025 monthly, 2024 yearly, 2025 yearly, all-time
        $monthlyType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_MONTHLY);
        $yearlyType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_YEARLY);
        $allTimeType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_ALL_TIME);

        // Verify monthly periods
        $dec2024 = StatisticPeriod::findOne(['period_type_id' => $monthlyType->id, 'year' => 2024, 'month' => 12]);
        $this->assertNotNull($dec2024, 'December 2024 monthly period should be created');

        $jan2025 = StatisticPeriod::findOne(['period_type_id' => $monthlyType->id, 'year' => 2025, 'month' => 1]);
        $this->assertNotNull($jan2025, 'January 2025 monthly period should be created');

        // Verify yearly periods
        $year2024 = StatisticPeriod::findOne(['period_type_id' => $yearlyType->id, 'year' => 2024, 'month' => null]);
        $this->assertNotNull($year2024, '2024 yearly period should be created');

        $year2025 = StatisticPeriod::findOne(['period_type_id' => $yearlyType->id, 'year' => 2025, 'month' => null]);
        $this->assertNotNull($year2025, '2025 yearly period should be created');

        // Verify all-time period
        $allTime = StatisticPeriod::findOne(['period_type_id' => $allTimeType->id]);
        $this->assertNotNull($allTime, 'All-time period should be created');

        // Verify statistics were calculated
        $totalFlightsType = StatisticAggregateType::findOne(['code' => StatisticAggregateType::CODE_TOTAL_FLIGHTS]);

        $jan2025Flights = StatisticAggregate::findOne([
            'period_id' => $jan2025->id,
            'aggregate_type_id' => $totalFlightsType->id,
        ]);
        $this->assertEquals(3, (int) $jan2025Flights->value);

        $allTimeFlights = StatisticAggregate::findOne([
            'period_id' => $allTime->id,
            'aggregate_type_id' => $totalFlightsType->id,
        ]);
        $this->assertEquals(4, (int) $allTimeFlights->value); // 3 Jan + 1 Dec
    }

    public function testConsolidateClosesPastPeriods()
    {
        // Run consolidate - creates periods for Dec 2024 and Jan 2025
        $exitCode = $this->controller->actionConsolidate();
        $this->assertEquals(ExitCode::OK, $exitCode);

        $monthlyType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_MONTHLY);
        $yearlyType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_YEARLY);
        $allTimeType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_ALL_TIME);

        // Get current date to determine what should be closed
        $now = new \DateTimeImmutable();
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');

        // December 2024 monthly should be closed (it's in the past)
        $dec2024 = StatisticPeriod::findOne(['period_type_id' => $monthlyType->id, 'year' => 2024, 'month' => 12]);
        $this->assertEquals(StatisticPeriod::STATUS_CLOSED, $dec2024->status, 'Dec 2024 should be closed');

        // 2024 yearly should be closed (it's in the past)
        $year2024 = StatisticPeriod::findOne(['period_type_id' => $yearlyType->id, 'year' => 2024]);
        $this->assertEquals(StatisticPeriod::STATUS_CLOSED, $year2024->status, '2024 yearly should be closed');

        // January 2025 - closed if we're past Jan 2025, open otherwise
        $jan2025 = StatisticPeriod::findOne(['period_type_id' => $monthlyType->id, 'year' => 2025, 'month' => 1]);
        if ($currentYear > 2025 || ($currentYear === 2025 && $currentMonth > 1)) {
            $this->assertEquals(StatisticPeriod::STATUS_CLOSED, $jan2025->status, 'Jan 2025 should be closed');
        } else {
            $this->assertEquals(StatisticPeriod::STATUS_OPEN, $jan2025->status, 'Jan 2025 should be open');
        }

        // All-time should always be open
        $allTime = StatisticPeriod::findOne(['period_type_id' => $allTimeType->id]);
        $this->assertEquals(StatisticPeriod::STATUS_OPEN, $allTime->status, 'All-time should always be open');
    }

    public function testYearlyPeriodCalculation()
    {
        // Calculate yearly 2025
        $exitCode = $this->controller->actionRecalculate(2025);
        $this->assertEquals(ExitCode::OK, $exitCode);

        $yearlyType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_YEARLY);
        $period = StatisticPeriod::findOne([
            'period_type_id' => $yearlyType->id,
            'year' => 2025,
            'month' => null,
        ]);

        $this->assertNotNull($period);

        $totalFlightsType = StatisticAggregateType::findOne(['code' => StatisticAggregateType::CODE_TOTAL_FLIGHTS]);
        $totalFlights = StatisticAggregate::findOne([
            'period_id' => $period->id,
            'aggregate_type_id' => $totalFlightsType->id,
        ]);

        // Should match January data (only month in 2025)
        $this->assertEquals(3, (int) $totalFlights->value);
    }
}
