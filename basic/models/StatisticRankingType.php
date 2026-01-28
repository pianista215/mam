<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_ranking_type".
 *
 * @property int $id
 * @property string $code
 * @property string $entity_type
 * @property int $max_positions
 * @property string $sort_order
 *
 * @property StatisticRanking[] $statisticRankings
 * @property StatisticRankingTypeLang[] $statisticRankingTypeLangs
 * @property StatisticRankingTypeLang $lang
 */
class StatisticRankingType extends \yii\db\ActiveRecord
{
    const CODE_TOP_PILOTS_BY_HOURS = 'top_pilots_by_hours';
    const CODE_TOP_PILOTS_BY_FLIGHTS = 'top_pilots_by_flights';
    const CODE_TOP_AIRCRAFT_BY_FLIGHTS = 'top_aircraft_by_flights';
    const CODE_SMOOTHEST_LANDINGS = 'smoothest_landings';

    const ENTITY_PILOT = 'pilot';
    const ENTITY_AIRCRAFT = 'aircraft';
    const ENTITY_FLIGHT = 'flight';

    const SORT_DESC = 'DESC';
    const SORT_ASC = 'ASC';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_ranking_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'entity_type'], 'required'],
            [['max_positions'], 'integer'],
            [['code', 'entity_type'], 'string', 'max' => 50],
            [['sort_order'], 'string', 'max' => 4],
            [['sort_order'], 'in', 'range' => [self::SORT_DESC, self::SORT_ASC]],
            [['max_positions'], 'default', 'value' => 5],
            [['sort_order'], 'default', 'value' => self::SORT_DESC],
            [['code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'entity_type' => 'Entity Type',
            'max_positions' => 'Max Positions',
            'sort_order' => 'Sort Order',
        ];
    }

    /**
     * Gets query for [[StatisticRankings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatisticRankings()
    {
        return $this->hasMany(StatisticRanking::class, ['ranking_type_id' => 'id']);
    }

    /**
     * Gets query for [[StatisticRankingTypeLangs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatisticRankingTypeLangs()
    {
        return $this->hasMany(StatisticRankingTypeLang::class, ['ranking_type_id' => 'id']);
    }

    /**
     * Gets query for [[Lang]] (current language translation).
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        return $this->hasOne(StatisticRankingTypeLang::class, ['ranking_type_id' => 'id'])
            ->andWhere(['language' => Yii::$app->language]);
    }

    /**
     * Find a ranking type by code
     *
     * @param string $code
     * @return static|null
     */
    public static function findByCode(string $code): ?self
    {
        return static::findOne(['code' => $code]);
    }
}
