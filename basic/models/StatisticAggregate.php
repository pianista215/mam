<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_aggregate".
 *
 * @property int $id
 * @property int $period_id
 * @property int $aggregate_type_id
 * @property float $value
 * @property float|null $variation_percent
 *
 * @property StatisticPeriod $period
 * @property StatisticAggregateType $aggregateType
 */
class StatisticAggregate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_aggregate';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['period_id', 'aggregate_type_id'], 'required'],
            [['period_id', 'aggregate_type_id'], 'integer'],
            [['value', 'variation_percent'], 'number'],
            [['value'], 'default', 'value' => 0],
            [['period_id', 'aggregate_type_id'], 'unique', 'targetAttribute' => ['period_id', 'aggregate_type_id']],
            [['period_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatisticPeriod::class, 'targetAttribute' => ['period_id' => 'id']],
            [['aggregate_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatisticAggregateType::class, 'targetAttribute' => ['aggregate_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'period_id' => 'Period ID',
            'aggregate_type_id' => 'Aggregate Type ID',
            'value' => 'Value',
            'variation_percent' => 'Variation Percent',
        ];
    }

    /**
     * Gets query for [[Period]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPeriod()
    {
        return $this->hasOne(StatisticPeriod::class, ['id' => 'period_id']);
    }

    /**
     * Gets query for [[AggregateType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregateType()
    {
        return $this->hasOne(StatisticAggregateType::class, ['id' => 'aggregate_type_id']);
    }

    /**
     * Find or create an aggregate for a period and type
     *
     * @param int $periodId
     * @param int $aggregateTypeId
     * @return static
     */
    public static function findOrCreate(int $periodId, int $aggregateTypeId): self
    {
        $aggregate = static::findOne([
            'period_id' => $periodId,
            'aggregate_type_id' => $aggregateTypeId,
        ]);

        if (!$aggregate) {
            $aggregate = new static([
                'period_id' => $periodId,
                'aggregate_type_id' => $aggregateTypeId,
                'value' => 0,
            ]);
        }

        return $aggregate;
    }
}
