<?php

namespace app\controllers;

use app\models\AircraftType;
use app\models\Flight;
use app\models\Pilot;
use app\models\StatisticAggregate;
use app\models\StatisticAggregateType;
use app\models\StatisticPeriod;
use app\models\StatisticPeriodType;
use app\models\StatisticRanking;
use app\models\StatisticRankingType;
use app\models\StatisticRecord;
use app\models\StatisticRecordType;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * StatisticsController displays pre-calculated statistics.
 */
class StatisticsController extends Controller
{
    /**
     * Displays monthly statistics.
     *
     * @param int|null $year
     * @param int|null $month
     * @return string
     */
    public function actionMonth(?int $year = null, ?int $month = null)
    {
        $now = new \DateTimeImmutable();
        $year = $year ?? (int) $now->format('Y');
        $month = $month ?? (int) $now->format('n');

        // Validate month range
        if ($month < 1 || $month > 12) {
            throw new NotFoundHttpException(Yii::t('app', 'Invalid month.'));
        }

        $period = $this->findMonthlyPeriod($year, $month);

        return $this->render('month', [
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'aggregates' => $this->getAggregatesForPeriod($period),
            'rankings' => $this->getRankingsForPeriod($period),
            'records' => $this->getRecordsForPeriod($period),
            'availableMonths' => $this->getAvailableMonths(),
        ]);
    }

    /**
     * Displays yearly statistics.
     *
     * @param int|null $year
     * @return string
     */
    public function actionYear(?int $year = null)
    {
        $now = new \DateTimeImmutable();
        $year = $year ?? (int) $now->format('Y');

        $period = $this->findYearlyPeriod($year);

        return $this->render('year', [
            'period' => $period,
            'year' => $year,
            'aggregates' => $this->getAggregatesForPeriod($period),
            'rankings' => $this->getRankingsForPeriod($period),
            'records' => $this->getRecordsForPeriod($period),
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    /**
     * Displays all-time statistics.
     *
     * @return string
     */
    public function actionAllTime()
    {
        $period = $this->findAllTimePeriod();

        return $this->render('all-time', [
            'period' => $period,
            'aggregates' => $this->getAggregatesForPeriod($period),
            'rankings' => $this->getRankingsForPeriod($period),
            'records' => $this->getRecordsForPeriod($period),
        ]);
    }

    /**
     * Find a monthly period by year and month.
     *
     * @param int $year
     * @param int $month
     * @return StatisticPeriod|null
     */
    protected function findMonthlyPeriod(int $year, int $month): ?StatisticPeriod
    {
        $periodType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_MONTHLY);
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
     * Find a yearly period by year.
     *
     * @param int $year
     * @return StatisticPeriod|null
     */
    protected function findYearlyPeriod(int $year): ?StatisticPeriod
    {
        $periodType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_YEARLY);
        if (!$periodType) {
            return null;
        }

        return StatisticPeriod::findOne([
            'period_type_id' => $periodType->id,
            'year' => $year,
            'month' => null,
        ]);
    }

    /**
     * Find the all-time period.
     *
     * @return StatisticPeriod|null
     */
    protected function findAllTimePeriod(): ?StatisticPeriod
    {
        $periodType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_ALL_TIME);
        if (!$periodType) {
            return null;
        }

        return StatisticPeriod::findOne([
            'period_type_id' => $periodType->id,
            'year' => null,
            'month' => null,
        ]);
    }

    /**
     * Get aggregates for a period, indexed by type code.
     *
     * @param StatisticPeriod|null $period
     * @return array
     */
    protected function getAggregatesForPeriod(?StatisticPeriod $period): array
    {
        if (!$period) {
            return [];
        }

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
     *
     * @param StatisticPeriod|null $period
     * @return array
     */
    protected function getRankingsForPeriod(?StatisticPeriod $period): array
    {
        if (!$period) {
            return [];
        }

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
                $entities = AircraftType::find()
                    ->where(['id' => $entityIds])
                    ->indexBy('id')
                    ->all();
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
     *
     * @param StatisticPeriod|null $period
     * @return array
     */
    protected function getRecordsForPeriod(?StatisticPeriod $period): array
    {
        if (!$period) {
            return [];
        }

        $records = StatisticRecord::find()
            ->with(['recordType', 'recordType.lang'])
            ->where(['period_id' => $period->id])
            ->all();

        $result = [];
        foreach ($records as $record) {
            $code = $record->recordType->code;

            // Load related flight entity
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

    /**
     * Get available months for navigation.
     *
     * @return array
     */
    protected function getAvailableMonths(): array
    {
        $periodType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_MONTHLY);
        if (!$periodType) {
            return [];
        }

        return StatisticPeriod::find()
            ->where(['period_type_id' => $periodType->id])
            ->orderBy(['year' => SORT_DESC, 'month' => SORT_DESC])
            ->all();
    }

    /**
     * Get available years for navigation.
     *
     * @return array
     */
    protected function getAvailableYears(): array
    {
        $periodType = StatisticPeriodType::findByCode(StatisticPeriodType::TYPE_YEARLY);
        if (!$periodType) {
            return [];
        }

        return StatisticPeriod::find()
            ->where(['period_type_id' => $periodType->id])
            ->orderBy(['year' => SORT_DESC])
            ->all();
    }
}
