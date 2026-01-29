<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_period".
 *
 * @property int $id
 * @property int $period_type_id
 * @property int $year
 * @property int|null $month
 * @property string $status
 * @property string|null $calculated_at
 *
 * @property StatisticPeriodType $periodType
 * @property StatisticAggregate[] $statisticAggregates
 * @property StatisticRanking[] $statisticRankings
 * @property StatisticRecord[] $statisticRecords
 */
class StatisticPeriod extends \yii\db\ActiveRecord
{
    const STATUS_OPEN = 'O';
    const STATUS_CLOSED = 'C';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_period';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['period_type_id'], 'required'],
            [['period_type_id', 'year', 'month'], 'integer'],
            [['calculated_at'], 'safe'],
            [['status'], 'string', 'max' => 1],
            [['status'], 'in', 'range' => [self::STATUS_OPEN, self::STATUS_CLOSED]],
            [['period_type_id', 'year', 'month'], 'unique', 'targetAttribute' => ['period_type_id', 'year', 'month']],
            [['period_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatisticPeriodType::class, 'targetAttribute' => ['period_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'period_type_id' => 'Period Type ID',
            'year' => 'Year',
            'month' => 'Month',
            'status' => 'Status',
            'calculated_at' => 'Calculated At',
        ];
    }

    /**
     * Gets query for [[PeriodType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPeriodType()
    {
        return $this->hasOne(StatisticPeriodType::class, ['id' => 'period_type_id']);
    }

    /**
     * Gets query for [[StatisticAggregates]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatisticAggregates()
    {
        return $this->hasMany(StatisticAggregate::class, ['period_id' => 'id']);
    }

    /**
     * Gets query for [[StatisticRankings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatisticRankings()
    {
        return $this->hasMany(StatisticRanking::class, ['period_id' => 'id']);
    }

    /**
     * Gets query for [[StatisticRecords]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatisticRecords()
    {
        return $this->hasMany(StatisticRecord::class, ['period_id' => 'id']);
    }

    /**
     * Check if this is a monthly period
     *
     * @return bool
     */
    public function isMonthly(): bool
    {
        return $this->month !== null;
    }

    /**
     * Check if this is a yearly period
     *
     * @return bool
     */
    public function isYearly(): bool
    {
        return $this->year !== null && $this->month === null;
    }

    /**
     * Check if this is an all-time period
     *
     * @return bool
     */
    public function isAllTime(): bool
    {
        return $this->year === null && $this->month === null;
    }

    /**
     * Check if this period is open
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if this period is closed
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Get the start date of this period
     *
     * @return \DateTimeImmutable
     */
    public function getStartDate(): \DateTimeImmutable
    {
        if ($this->isAllTime()) {
            return new \DateTimeImmutable('2000-01-01 00:00:00');
        }
        $month = $this->month ?? 1;
        return new \DateTimeImmutable("{$this->year}-{$month}-01 00:00:00");
    }

    /**
     * Get the end date of this period (exclusive)
     *
     * @return \DateTimeImmutable
     */
    public function getEndDate(): \DateTimeImmutable
    {
        if ($this->isAllTime()) {
            return new \DateTimeImmutable('2100-01-01 00:00:00');
        }
        if ($this->isMonthly()) {
            return $this->getStartDate()->modify('+1 month');
        }
        return new \DateTimeImmutable(($this->year + 1) . "-01-01 00:00:00");
    }

    /**
     * Find or create a period
     *
     * @param string $typeCode 'monthly', 'yearly', or 'all_time'
     * @param int|null $year
     * @param int|null $month
     * @return static
     */
    public static function findOrCreate(string $typeCode, ?int $year, ?int $month = null): self
    {
        $periodType = StatisticPeriodType::findByCode($typeCode);
        if (!$periodType) {
            throw new \RuntimeException("Period type not found: {$typeCode}");
        }

        $period = static::findOne([
            'period_type_id' => $periodType->id,
            'year' => $year,
            'month' => $month,
        ]);

        if (!$period) {
            $period = new static([
                'period_type_id' => $periodType->id,
                'year' => $year,
                'month' => $month,
                'status' => self::STATUS_OPEN,
            ]);
            if (!$period->save()) {
                throw new \RuntimeException("Failed to create period: " . json_encode($period->errors));
            }
        }

        return $period;
    }

    /**
     * Get the previous period of the same type
     *
     * @return static|null
     */
    public function getPreviousPeriod(): ?self
    {
        // All-time has no previous period
        if ($this->isAllTime()) {
            return null;
        }

        if ($this->isMonthly()) {
            $prevMonth = $this->month - 1;
            $prevYear = $this->year;
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear--;
            }
            return static::findOne([
                'period_type_id' => $this->period_type_id,
                'year' => $prevYear,
                'month' => $prevMonth,
            ]);
        }

        return static::findOne([
            'period_type_id' => $this->period_type_id,
            'year' => $this->year - 1,
            'month' => null,
        ]);
    }
}
