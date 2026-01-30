<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_ranking".
 *
 * @property int $id
 * @property int $period_id
 * @property int $ranking_type_id
 * @property int $position
 * @property int $entity_id
 * @property float $value
 * @property int|null $previous_position
 *
 * @property StatisticPeriod $period
 * @property StatisticRankingType $rankingType
 */
class StatisticRanking extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_ranking';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['period_id', 'ranking_type_id', 'position', 'entity_id', 'value'], 'required'],
            [['period_id', 'ranking_type_id', 'position', 'entity_id', 'previous_position'], 'integer'],
            [['value'], 'number'],
            [['period_id', 'ranking_type_id', 'position'], 'unique', 'targetAttribute' => ['period_id', 'ranking_type_id', 'position']],
            [['period_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatisticPeriod::class, 'targetAttribute' => ['period_id' => 'id']],
            [['ranking_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatisticRankingType::class, 'targetAttribute' => ['ranking_type_id' => 'id']],
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
            'ranking_type_id' => 'Ranking Type ID',
            'position' => 'Position',
            'entity_id' => 'Entity ID',
            'value' => 'Value',
            'previous_position' => 'Previous Position',
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
     * Gets query for [[RankingType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRankingType()
    {
        return $this->hasOne(StatisticRankingType::class, ['id' => 'ranking_type_id']);
    }

    /**
     * Get the position change from previous period
     *
     * @return int|null Positive = moved up, Negative = moved down, 0 = same, null = new entry
     */
    public function getPositionChange(): ?int
    {
        if ($this->previous_position === null) {
            return null;
        }
        return $this->previous_position - $this->position;
    }
}
