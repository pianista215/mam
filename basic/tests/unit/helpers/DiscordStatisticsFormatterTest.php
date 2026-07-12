<?php

namespace tests\unit\helpers;

use app\helpers\DiscordStatisticsFormatter;
use app\models\Airport;
use app\models\Country;
use app\models\Pilot;
use app\models\StatisticAggregate;
use app\models\StatisticAggregateType;
use app\models\StatisticPeriod;
use app\models\StatisticPeriodType;
use app\models\StatisticRanking;
use app\models\StatisticRankingType;
use app\models\StatisticRecord;
use app\models\StatisticRecordType;
use tests\unit\BaseUnitTest;

class DiscordStatisticsFormatterTest extends BaseUnitTest
{
    private Pilot $pilot;

    protected function _before()
    {
        parent::_before();

        $country = new Country(['name' => 'Spain', 'iso2_code' => 'ES']);
        $country->save(false);

        $airport = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'LEMD Airport',
            'latitude' => 40.0,
            'longitude' => -3.0,
            'city' => 'Madrid',
            'country_id' => $country->id,
        ]);
        $airport->save(false);

        $this->pilot = new Pilot([
            'license' => 'PIL001',
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@test.com',
            'password' => '$2y$10$72JM.DYpddpessTYjHI0kuH/0NKNYeLP.YoU2AZwGY1kHY.Aow0Mu',
            'country_id' => $country->id,
            'city' => 'Madrid',
            'location' => 'LEMD',
            'date_of_birth' => '1990-01-01',
        ]);
        $this->pilot->save(false);
    }

    public function testFormatBuildsEmbedWithAggregatesRankingsAndRecords()
    {
        $periodType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_MONTHLY);
        $period = new StatisticPeriod([
            'period_type_id' => $periodType->id,
            'year' => 2025,
            'month' => 1,
            'status' => StatisticPeriod::STATUS_CLOSED,
            'calculated_at' => '2025-02-01 08:00:00',
        ]);
        $period->save(false);

        // Aggregate: total flight hours, with a positive variation
        $hoursType = StatisticAggregateType::findOne(['code' => StatisticAggregateType::CODE_TOTAL_FLIGHT_HOURS]);
        $hoursAggregate = new StatisticAggregate([
            'period_id' => $period->id,
            'aggregate_type_id' => $hoursType->id,
            'value' => 4.5,
            'variation_percent' => 12.5,
        ]);
        $hoursAggregate->save(false);
        $aggregates = [StatisticAggregateType::CODE_TOTAL_FLIGHT_HOURS => $hoursAggregate];

        // Ranking: top pilot by hours, position 1
        $rankingType = StatisticRankingType::findOne(['code' => StatisticRankingType::CODE_TOP_PILOTS_BY_HOURS]);
        $ranking = new StatisticRanking([
            'period_id' => $period->id,
            'ranking_type_id' => $rankingType->id,
            'position' => 1,
            'entity_id' => $this->pilot->id,
            'value' => 4.5,
        ]);
        $ranking->save(false);
        $rankings = [
            StatisticRankingType::CODE_TOP_PILOTS_BY_HOURS => [
                'type' => $rankingType,
                'entries' => [$ranking],
                'entities' => [$this->pilot->id => $this->pilot],
            ],
        ];

        // Record: longest flight time, no matching flight (should degrade gracefully)
        $recordType = StatisticRecordType::findOne(['code' => StatisticRecordType::CODE_LONGEST_FLIGHT_TIME]);
        $record = new StatisticRecord([
            'period_id' => $period->id,
            'record_type_id' => $recordType->id,
            'entity_id' => 999999,
            'value' => 120,
            'is_all_time_record' => 0,
        ]);
        $record->save(false);
        $records = [
            StatisticRecordType::CODE_LONGEST_FLIGHT_TIME => ['record' => $record, 'flight' => null],
        ];

        $payload = DiscordStatisticsFormatter::format(
            'MAM Airlines',
            'January 2025',
            $aggregates,
            $rankings,
            $records
        );

        $this->assertArrayHasKey('content', $payload);
        $content = $payload['content'];

        $this->assertStringContainsString('# 📊 MAM Airlines — January 2025', $content);
        $this->assertStringContainsString('⏱️ **4:30 (+12.5%)**', $content);
        $this->assertStringContainsString('1. ' . $this->pilot->fullName . ' — **4:30**', $content);
        $this->assertStringContainsString('**2:00**', $content);
    }
}
