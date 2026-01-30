<?php

namespace app\commands;

use app\config\ConfigHelper;
use app\models\AircraftType;
use app\models\Flight;
use app\models\FlightPhaseMetricType;
use app\models\FlightPhaseType;
use app\models\FlightReport;
use app\models\Pilot;
use app\models\StatisticAggregate;
use app\models\StatisticAggregateType;
use app\models\StatisticPeriod;
use app\models\StatisticPeriodType;
use app\models\StatisticRanking;
use app\models\StatisticRankingType;
use app\models\StatisticRecord;
use app\models\StatisticRecordType;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;
use Yii;

/**
 * Statistics calculation controller for daily cron job and manual recalculation.
 */
class StatisticsController extends Controller
{
    /**
     * Daily cron job to calculate statistics.
     *
     * Workflow:
     * 1. If it's the 1st of the month, close the previous month
     * 2. If it's January 1st, close the previous year
     * 3. Ensure current month and year periods exist
     * 4. Recalculate all open periods
     *
     * @return int Exit code
     */
    public function actionCalculateDaily(): int
    {
        $this->stdout("Starting daily statistics calculation...\n");

        $now = new \DateTimeImmutable();
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');
        $currentDay = (int) $now->format('j');

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Close previous month if it's day 1
            if ($currentDay === 1) {
                $prevMonth = $currentMonth - 1;
                $prevYear = $currentYear;
                if ($prevMonth < 1) {
                    $prevMonth = 12;
                    $prevYear--;
                }

                $this->closePeriodIfExists(StatisticPeriodType::TYPE_MONTHLY, $prevYear, $prevMonth);

                // Close previous year if it's January 1st
                if ($currentMonth === 1) {
                    $this->closePeriodIfExists(StatisticPeriodType::TYPE_YEARLY, $prevYear, null);
                }
            }

            // Ensure current periods exist
            $monthlyPeriod = StatisticPeriod::findOrCreate(
                StatisticPeriodType::TYPE_MONTHLY,
                $currentYear,
                $currentMonth
            );
            $this->stdout("Monthly period: {$currentYear}-{$currentMonth} (ID: {$monthlyPeriod->id})\n");

            $yearlyPeriod = StatisticPeriod::findOrCreate(
                StatisticPeriodType::TYPE_YEARLY,
                $currentYear,
                null
            );
            $this->stdout("Yearly period: {$currentYear} (ID: {$yearlyPeriod->id})\n");

            // Ensure all-time period exists
            $allTimePeriod = StatisticPeriod::findOrCreate(
                StatisticPeriodType::TYPE_ALL_TIME,
                null,
                null
            );
            $this->stdout("All-time period (ID: {$allTimePeriod->id})\n");

            // Recalculate all open periods
            $openPeriods = StatisticPeriod::find()
                ->where(['status' => StatisticPeriod::STATUS_OPEN])
                ->all();

            foreach ($openPeriods as $period) {
                $this->calculatePeriod($period);
            }

            $transaction->commit();
            $this->stdout("Daily statistics calculation completed successfully.\n");

            return ExitCode::OK;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stderr("Error calculating statistics: " . $e->getMessage() . "\n");
            $this->stderr($e->getTraceAsString() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Manually recalculate a specific period.
     *
     * @param int $year The year to recalculate
     * @param int|null $month The month to recalculate (null for yearly)
     * @return int Exit code
     */
    public function actionRecalculate(int $year, ?int $month = null): int
    {
        $periodDesc = $month ? "{$year}-{$month}" : "{$year}";
        $this->stdout("Recalculating statistics for period: {$periodDesc}\n");

        $periodType = $month ? StatisticPeriodType::TYPE_MONTHLY : StatisticPeriodType::TYPE_YEARLY;

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $period = StatisticPeriod::findOrCreate($periodType, $year, $month);
            $this->calculatePeriod($period);

            $transaction->commit();
            $this->stdout("Recalculation completed successfully.\n");

            return ExitCode::OK;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stderr("Error recalculating statistics: " . $e->getMessage() . "\n");
            $this->stderr($e->getTraceAsString() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Manually recalculate all-time statistics.
     *
     * @return int Exit code
     */
    public function actionRecalculateAllTime(): int
    {
        $this->stdout("Recalculating all-time statistics\n");

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $period = StatisticPeriod::findOrCreate(StatisticPeriodType::TYPE_ALL_TIME, null, null);
            $this->calculatePeriod($period);

            $transaction->commit();
            $this->stdout("Recalculation completed successfully.\n");

            return ExitCode::OK;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stderr("Error recalculating all-time statistics: " . $e->getMessage() . "\n");
            $this->stderr($e->getTraceAsString() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Consolidate all statistics - recalculate ALL periods (open and closed).
     * Useful for correcting statistics after logic changes or data fixes.
     *
     * @return int Exit code
     */
    public function actionConsolidate(): int
    {
        $this->stdout("Starting statistics consolidation (all periods)...\n");

        $periods = StatisticPeriod::find()
            ->orderBy(['year' => SORT_ASC, 'month' => SORT_ASC])
            ->all();

        $this->stdout("Found " . count($periods) . " periods to consolidate.\n");

        foreach ($periods as $period) {
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $this->calculatePeriod($period);
                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                $periodDesc = $period->isAllTime() ? 'ALL-TIME' : ($period->month ? "{$period->year}-{$period->month}" : "{$period->year}");
                $this->stderr("Error consolidating period {$periodDesc}: " . $e->getMessage() . "\n");
            }
        }

        $this->stdout("Consolidation completed.\n");

        return ExitCode::OK;
    }

    /**
     * Close a period if it exists.
     */
    private function closePeriodIfExists(string $typeCode, int $year, ?int $month): void
    {
        $periodType = StatisticPeriodType::findByCode($typeCode);
        if (!$periodType) {
            return;
        }

        $period = StatisticPeriod::findOne([
            'period_type_id' => $periodType->id,
            'year' => $year,
            'month' => $month,
        ]);

        if ($period && $period->isOpen()) {
            $period->status = StatisticPeriod::STATUS_CLOSED;
            if (!$period->save()) {
                throw new \RuntimeException("Failed to close period: " . json_encode($period->errors));
            }
            $periodDesc = $month ? "{$year}-{$month}" : "{$year}";
            $this->stdout("Closed period: {$periodDesc}\n");
        }
    }

    /**
     * Calculate all statistics for a period.
     */
    private function calculatePeriod(StatisticPeriod $period): void
    {
        $periodDesc = $period->isAllTime() ? 'ALL-TIME' : ($period->month ? "{$period->year}-{$period->month}" : "{$period->year}");
        $this->stdout("Calculating period: {$periodDesc}\n");

        $startDate = $period->getStartDate()->format('Y-m-d H:i:s');
        $endDate = $period->getEndDate()->format('Y-m-d H:i:s');

        // Calculate aggregates
        $this->calculateAggregates($period, $startDate, $endDate);

        // Calculate rankings
        $this->calculateRankings($period, $startDate, $endDate);

        // Calculate records
        $this->calculateRecords($period, $startDate, $endDate);

        // Update calculated_at timestamp
        $period->calculated_at = date('Y-m-d H:i:s');
        if (!$period->save()) {
            throw new \RuntimeException("Failed to update period timestamp: " . json_encode($period->errors));
        }
    }

    /**
     * Build base query for valid flights (approved with complete data).
     */
    private function buildBaseFlightQuery(string $startDate, string $endDate): Query
    {
        return (new Query())
            ->from(['f' => Flight::tableName()])
            ->innerJoin(['fr' => FlightReport::tableName()], 'fr.flight_id = f.id')
            ->where(['f.status' => Flight::STATUS_FINISHED])
            ->andWhere(['not', ['fr.flight_time_minutes' => null]])
            ->andWhere(['>=', 'f.creation_date', $startDate])
            ->andWhere(['<', 'f.creation_date', $endDate]);
    }

    /**
     * Calculate aggregate statistics for a period.
     */
    private function calculateAggregates(StatisticPeriod $period, string $startDate, string $endDate): void
    {
        $previousPeriod = $period->getPreviousPeriod();

        $aggregateTypes = StatisticAggregateType::find()->all();

        foreach ($aggregateTypes as $aggregateType) {
            $value = $this->calculateAggregateValue($aggregateType->code, $startDate, $endDate);

            $aggregate = StatisticAggregate::findOrCreate($period->id, $aggregateType->id);
            $aggregate->value = $value;

            // Calculate variation from previous period
            $aggregate->variation_percent = null;
            if ($previousPeriod) {
                $previousAggregate = StatisticAggregate::findOne([
                    'period_id' => $previousPeriod->id,
                    'aggregate_type_id' => $aggregateType->id,
                ]);
                if ($previousAggregate && $previousAggregate->value > 0) {
                    $aggregate->variation_percent = (($value - $previousAggregate->value) / $previousAggregate->value) * 100;
                }
            }

            if (!$aggregate->save()) {
                throw new \RuntimeException("Failed to save aggregate: " . json_encode($aggregate->errors));
            }

            $this->stdout("  - {$aggregateType->code}: {$value}\n");
        }
    }

    /**
     * Calculate a specific aggregate value.
     */
    private function calculateAggregateValue(string $code, string $startDate, string $endDate): float
    {
        switch ($code) {
            case StatisticAggregateType::CODE_TOTAL_FLIGHTS:
                $result = $this->buildBaseFlightQuery($startDate, $endDate)
                    ->count();
                return (float) ($result ?? 0);

            case StatisticAggregateType::CODE_TOTAL_FLIGHT_HOURS:
                $result = $this->buildBaseFlightQuery($startDate, $endDate)
                    ->select(new \yii\db\Expression('COALESCE(SUM(fr.flight_time_minutes) / 60.0, 0)'))
                    ->scalar();
                return (float) ($result ?? 0);

            default:
                $this->stderr("Unknown aggregate type: {$code}\n");
                return 0;
        }
    }

    /**
     * Calculate ranking statistics for a period.
     */
    private function calculateRankings(StatisticPeriod $period, string $startDate, string $endDate): void
    {
        $previousPeriod = $period->getPreviousPeriod();

        // Delete existing rankings for this period
        StatisticRanking::deleteAll(['period_id' => $period->id]);

        $rankingTypes = StatisticRankingType::find()->all();

        foreach ($rankingTypes as $rankingType) {
            $rankings = $this->calculateRankingValues($rankingType, $startDate, $endDate);

            // Get previous rankings for comparison
            $previousRankings = [];
            if ($previousPeriod) {
                $prevRanks = StatisticRanking::find()
                    ->where(['period_id' => $previousPeriod->id, 'ranking_type_id' => $rankingType->id])
                    ->all();
                foreach ($prevRanks as $rank) {
                    $previousRankings[$rank->entity_id] = $rank->position;
                }
            }

            $position = 1;
            foreach ($rankings as $ranking) {
                $statisticRanking = new StatisticRanking([
                    'period_id' => $period->id,
                    'ranking_type_id' => $rankingType->id,
                    'position' => $position,
                    'entity_id' => $ranking['entity_id'],
                    'value' => $ranking['value'],
                    'previous_position' => $previousRankings[$ranking['entity_id']] ?? null,
                ]);

                if (!$statisticRanking->save()) {
                    throw new \RuntimeException("Failed to save ranking: " . json_encode($statisticRanking->errors));
                }

                $position++;
            }

            $this->stdout("  - {$rankingType->code}: " . count($rankings) . " entries\n");
        }
    }

    /**
     * Calculate ranking values for a specific ranking type.
     *
     * @return array Array of ['entity_id' => int, 'value' => float]
     */
    private function calculateRankingValues(StatisticRankingType $rankingType, string $startDate, string $endDate): array
    {
        $limit = $rankingType->max_positions;
        $order = $rankingType->sort_order === 'DESC' ? SORT_DESC : SORT_ASC;

        switch ($rankingType->code) {
            case StatisticRankingType::CODE_TOP_PILOTS_BY_HOURS:
                $results = $this->buildBaseFlightQuery($startDate, $endDate)
                    ->select([
                        'entity_id' => 'f.pilot_id',
                        'value' => new \yii\db\Expression('SUM(fr.flight_time_minutes) / 60.0'),
                    ])
                    ->groupBy('f.pilot_id')
                    ->orderBy(['value' => $order])
                    ->limit($limit)
                    ->all();
                break;

            case StatisticRankingType::CODE_TOP_PILOTS_BY_FLIGHTS:
                $results = $this->buildBaseFlightQuery($startDate, $endDate)
                    ->select([
                        'entity_id' => 'f.pilot_id',
                        'value' => new \yii\db\Expression('COUNT(*)'),
                    ])
                    ->groupBy('f.pilot_id')
                    ->orderBy(['value' => $order])
                    ->limit($limit)
                    ->all();
                break;

            case StatisticRankingType::CODE_TOP_AIRCRAFT_TYPES_BY_FLIGHTS:
                $results = $this->buildBaseFlightQuery($startDate, $endDate)
                    ->innerJoin(['a' => 'aircraft'], 'a.id = f.aircraft_id')
                    ->innerJoin(['ac' => 'aircraft_configuration'], 'ac.id = a.aircraft_configuration_id')
                    ->select([
                        'entity_id' => 'ac.aircraft_type_id',
                        'value' => new \yii\db\Expression('COUNT(*)'),
                    ])
                    ->groupBy('ac.aircraft_type_id')
                    ->orderBy(['value' => $order])
                    ->limit($limit)
                    ->all();
                break;

            case StatisticRankingType::CODE_SMOOTHEST_LANDINGS:
                $results = (new Query())
                    ->select([
                        'entity_id' => 'f.id',
                        'value' => new \yii\db\Expression('ABS(CAST(fpm.value AS DECIMAL(10,2)))'),
                    ])
                    ->from(['f' => Flight::tableName()])
                    ->innerJoin(['fr' => FlightReport::tableName()], 'fr.flight_id = f.id')
                    ->innerJoin(['fp' => 'flight_phase'], 'fp.flight_report_id = fr.id')
                    ->innerJoin(['fpt' => FlightPhaseType::tableName()], 'fpt.id = fp.flight_phase_type_id AND fpt.code = :phaseCode', [
                        ':phaseCode' => FlightPhaseType::CODE_FINAL_LANDING,
                    ])
                    ->innerJoin(['fpm' => 'flight_phase_metric'], 'fpm.flight_phase_id = fp.id')
                    ->innerJoin(['fpmt' => FlightPhaseMetricType::tableName()], 'fpmt.id = fpm.metric_type_id AND fpmt.code = :metricCode', [
                        ':metricCode' => FlightPhaseMetricType::CODE_LANDING_VS_FPM,
                    ])
                    ->where(['f.status' => Flight::STATUS_FINISHED])
                    ->andWhere(['>=', 'f.creation_date', $startDate])
                    ->andWhere(['<', 'f.creation_date', $endDate])
                    ->orderBy(['value' => $order])
                    ->limit($limit)
                    ->all();
                break;

            default:
                $this->stderr("Unknown ranking type: {$rankingType->code}\n");
                return [];
        }

        return array_map(function ($row) {
            return [
                'entity_id' => (int) $row['entity_id'],
                'value' => (float) $row['value'],
            ];
        }, $results);
    }

    /**
     * Calculate record statistics for a period.
     */
    private function calculateRecords(StatisticPeriod $period, string $startDate, string $endDate): void
    {
        // Delete existing records for this period
        StatisticRecord::deleteAll(['period_id' => $period->id]);

        $recordTypes = StatisticRecordType::find()->all();

        foreach ($recordTypes as $recordType) {
            $recordData = $this->calculateRecordValue($recordType, $startDate, $endDate);

            if ($recordData) {
                // Check if this is an all-time record
                $isAllTimeRecord = $this->isAllTimeRecord($recordType, $recordData['value']);

                $record = new StatisticRecord([
                    'period_id' => $period->id,
                    'record_type_id' => $recordType->id,
                    'entity_id' => $recordData['entity_id'],
                    'value' => $recordData['value'],
                    'is_all_time_record' => $isAllTimeRecord ? 1 : 0,
                ]);

                if (!$record->save()) {
                    throw new \RuntimeException("Failed to save record: " . json_encode($record->errors));
                }

                $allTimeFlag = $isAllTimeRecord ? ' (ALL-TIME!)' : '';
                $this->stdout("  - {$recordType->code}: {$recordData['value']}{$allTimeFlag}\n");
            }
        }
    }

    /**
     * Calculate the record value for a specific record type.
     *
     * @return array|null ['entity_id' => int, 'value' => float] or null if no record
     */
    private function calculateRecordValue(StatisticRecordType $recordType, string $startDate, string $endDate): ?array
    {
        $order = $recordType->isMax() ? SORT_DESC : SORT_ASC;

        switch ($recordType->code) {
            case StatisticRecordType::CODE_LONGEST_FLIGHT_TIME:
                $result = $this->buildBaseFlightQuery($startDate, $endDate)
                    ->select([
                        'entity_id' => 'f.id',
                        'value' => 'fr.flight_time_minutes',
                    ])
                    ->orderBy(['fr.flight_time_minutes' => $order])
                    ->limit(1)
                    ->one();
                break;

            case StatisticRecordType::CODE_LONGEST_FLIGHT_DISTANCE:
                $result = (new Query())
                    ->select([
                        'entity_id' => 'f.id',
                        'value' => 'fr.distance_nm',
                    ])
                    ->from(['f' => Flight::tableName()])
                    ->innerJoin(['fr' => FlightReport::tableName()], 'fr.flight_id = f.id')
                    ->where(['f.status' => Flight::STATUS_FINISHED])
                    ->andWhere(['not', ['fr.distance_nm' => null]])
                    ->andWhere(['>=', 'f.creation_date', $startDate])
                    ->andWhere(['<', 'f.creation_date', $endDate])
                    ->orderBy(['fr.distance_nm' => $order])
                    ->limit(1)
                    ->one();
                break;

            default:
                $this->stderr("Unknown record type: {$recordType->code}\n");
                return null;
        }

        if (!$result) {
            return null;
        }

        return [
            'entity_id' => (int) $result['entity_id'],
            'value' => (float) $result['value'],
        ];
    }

    /**
     * Check if a value is an all-time record.
     */
    private function isAllTimeRecord(StatisticRecordType $recordType, float $value): bool
    {
        $existingRecord = StatisticRecord::find()
            ->where(['record_type_id' => $recordType->id, 'is_all_time_record' => 1])
            ->one();

        if (!$existingRecord) {
            return true;
        }

        if ($recordType->isMax()) {
            return $value > $existingRecord->value;
        }

        return $value < $existingRecord->value;
    }

    // ========================================
    // Email sending actions
    // ========================================

    /**
     * Send monthly statistics email.
     * Intended to be scheduled on the 1st of each month to send previous month's statistics.
     *
     * @param int|null $year The year (defaults to previous month's year)
     * @param int|null $month The month (defaults to previous month)
     * @return int Exit code
     */
    public function actionSendMonthlyEmail(?int $year = null, ?int $month = null): int
    {
        $now = new \DateTimeImmutable();

        // Default to previous month
        if ($year === null || $month === null) {
            $prevMonth = $now->modify('-1 month');
            $year = $year ?? (int) $prevMonth->format('Y');
            $month = $month ?? (int) $prevMonth->format('n');
        }

        $this->stdout("Sending monthly statistics email for {$year}-{$month}...\n");

        $email = ConfigHelper::getStatisticsEmail();
        if (empty($email)) {
            $this->stderr("No statistics email configured. Set it in Site Settings.\n");
            return ExitCode::CONFIG;
        }

        $period = $this->findPeriod(StatisticPeriodType::TYPE_MONTHLY, $year, $month);
        if (!$period) {
            $this->stderr("No statistics found for {$year}-{$month}.\n");
            return ExitCode::DATAERR;
        }

        // Set language for email
        $originalLanguage = Yii::$app->language;
        $emailLanguage = ConfigHelper::getStatisticsEmailLanguage();
        Yii::$app->language = $emailLanguage === 'es' ? 'es-ES' : 'en-US';

        // Get month name in the configured language
        $date = new \DateTimeImmutable("{$year}-{$month}-01");
        $monthName = Yii::$app->formatter->asDate($date, 'MMMM');
        $periodTitle = ucfirst($monthName) . ' ' . $year;

        $sent = $this->sendStatisticsEmail(
            $email,
            Yii::t('app', 'Monthly Statistics') . ' - ' . $periodTitle,
            'monthlyStatistics',
            $period,
            $periodTitle
        );

        // Restore original language
        Yii::$app->language = $originalLanguage;

        if ($sent) {
            $this->stdout("Email sent successfully to {$email}\n");
            return ExitCode::OK;
        }

        $this->stderr("Failed to send email.\n");
        return ExitCode::UNAVAILABLE;
    }

    /**
     * Send yearly statistics email.
     * Intended to be scheduled on January 1st to send previous year's statistics.
     *
     * @param int|null $year The year (defaults to previous year)
     * @return int Exit code
     */
    public function actionSendYearlyEmail(?int $year = null): int
    {
        $now = new \DateTimeImmutable();

        // Default to previous year
        if ($year === null) {
            $year = (int) $now->format('Y') - 1;
        }

        $this->stdout("Sending yearly statistics email for {$year}...\n");

        $email = ConfigHelper::getStatisticsEmail();
        if (empty($email)) {
            $this->stderr("No statistics email configured. Set it in Site Settings.\n");
            return ExitCode::CONFIG;
        }

        $period = $this->findPeriod(StatisticPeriodType::TYPE_YEARLY, $year, null);
        if (!$period) {
            $this->stderr("No statistics found for year {$year}.\n");
            return ExitCode::DATAERR;
        }

        // Set language for email
        $originalLanguage = Yii::$app->language;
        $emailLanguage = ConfigHelper::getStatisticsEmailLanguage();
        Yii::$app->language = $emailLanguage === 'es' ? 'es-ES' : 'en-US';

        $periodTitle = (string) $year;

        $sent = $this->sendStatisticsEmail(
            $email,
            Yii::t('app', 'Yearly Statistics') . ' - ' . $periodTitle,
            'yearlyStatistics',
            $period,
            $periodTitle
        );

        // Restore original language
        Yii::$app->language = $originalLanguage;

        if ($sent) {
            $this->stdout("Email sent successfully to {$email}\n");
            return ExitCode::OK;
        }

        $this->stderr("Failed to send email.\n");
        return ExitCode::UNAVAILABLE;
    }

    /**
     * Find a period by type and date.
     */
    private function findPeriod(string $typeCode, ?int $year, ?int $month): ?StatisticPeriod
    {
        $periodType = StatisticPeriodType::findByCode($typeCode);
        if (!$periodType) {
            return null;
        }

        return StatisticPeriod::findOne([
            'period_type_id' => $periodType->id,
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * Send statistics email.
     */
    private function sendStatisticsEmail(
        string $to,
        string $subject,
        string $template,
        StatisticPeriod $period,
        string $periodTitle
    ): bool {
        $aggregates = $this->getAggregatesForPeriod($period);
        $rankings = $this->getRankingsForPeriod($period);
        $records = $this->getRecordsForPeriod($period);

        $airlineName = ConfigHelper::getAirlineName();
        $fromEmail = ConfigHelper::getNoReplyMail();
        $replyToEmail = ConfigHelper::getSupportMail();

        return Yii::$app->mailer->compose($template, [
            'airlineName' => $airlineName,
            'periodTitle' => $periodTitle,
            'period' => $period,
            'aggregates' => $aggregates,
            'rankings' => $rankings,
            'records' => $records,
        ])
            ->setReplyTo($replyToEmail)
            ->setFrom($fromEmail)
            ->setTo($to)
            ->setSubject($airlineName . ' - ' . $subject)
            ->send();
    }

    /**
     * Get aggregates for a period, indexed by type code.
     */
    private function getAggregatesForPeriod(StatisticPeriod $period): array
    {
        $aggregates = StatisticAggregate::find()
            ->with(['aggregateType', 'aggregateType.lang'])
            ->where(['period_id' => $period->id])
            ->all();

        $result = [];
        foreach ($aggregates as $aggregate) {
            $result[$aggregate->aggregateType->code] = $aggregate;
        }
        return $result;
    }

    /**
     * Get rankings for a period, grouped by type code.
     */
    private function getRankingsForPeriod(StatisticPeriod $period): array
    {
        $rankings = StatisticRanking::find()
            ->with(['rankingType', 'rankingType.lang'])
            ->where(['period_id' => $period->id])
            ->orderBy(['ranking_type_id' => SORT_ASC, 'position' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($rankings as $ranking) {
            $code = $ranking->rankingType->code;
            if (!isset($result[$code])) {
                $result[$code] = [
                    'type' => $ranking->rankingType,
                    'entries' => [],
                ];
            }
            $result[$code]['entries'][] = $ranking;
        }

        // Load related entities for display
        foreach ($result as $code => &$data) {
            $entityType = $data['type']->entity_type;
            $entityIds = array_column($data['entries'], 'entity_id');

            if ($entityType === StatisticRankingType::ENTITY_PILOT) {
                $entities = Pilot::find()->where(['id' => $entityIds])->indexBy('id')->all();
            } elseif ($entityType === StatisticRankingType::ENTITY_AIRCRAFT_TYPE) {
                $entities = AircraftType::find()->where(['id' => $entityIds])->indexBy('id')->all();
            } elseif ($entityType === StatisticRankingType::ENTITY_FLIGHT) {
                $entities = Flight::find()
                    ->with(['pilot', 'flightReport'])
                    ->where(['id' => $entityIds])
                    ->indexBy('id')
                    ->all();
            } else {
                $entities = [];
            }

            $data['entities'] = $entities;
        }

        return $result;
    }

    /**
     * Get records for a period, indexed by type code.
     */
    private function getRecordsForPeriod(StatisticPeriod $period): array
    {
        $records = StatisticRecord::find()
            ->with(['recordType', 'recordType.lang'])
            ->where(['period_id' => $period->id])
            ->all();

        $result = [];
        foreach ($records as $record) {
            $code = $record->recordType->code;
            $flight = Flight::find()
                ->with(['pilot', 'flightReport'])
                ->where(['id' => $record->entity_id])
                ->one();

            $result[$code] = [
                'record' => $record,
                'flight' => $flight,
            ];
        }
        return $result;
    }
}
