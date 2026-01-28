<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_aggregate_type".
 *
 * @property int $id
 * @property string $code
 *
 * @property StatisticAggregate[] $statisticAggregates
 * @property StatisticAggregateTypeLang[] $statisticAggregateTypeLangs
 * @property StatisticAggregateTypeLang $lang
 */
class StatisticAggregateType extends \yii\db\ActiveRecord
{
    const CODE_TOTAL_FLIGHTS = 'total_flights';
    const CODE_TOTAL_FLIGHT_HOURS = 'total_flight_hours';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_aggregate_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code'], 'required'],
            [['code'], 'string', 'max' => 50],
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
        ];
    }

    /**
     * Gets query for [[StatisticAggregates]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatisticAggregates()
    {
        return $this->hasMany(StatisticAggregate::class, ['aggregate_type_id' => 'id']);
    }

    /**
     * Gets query for [[StatisticAggregateTypeLangs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatisticAggregateTypeLangs()
    {
        return $this->hasMany(StatisticAggregateTypeLang::class, ['aggregate_type_id' => 'id']);
    }

    /**
     * Gets query for [[Lang]] (current language translation).
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        return $this->hasOne(StatisticAggregateTypeLang::class, ['aggregate_type_id' => 'id'])
            ->andWhere(['language' => Yii::$app->language]);
    }

    /**
     * Find an aggregate type by code
     *
     * @param string $code
     * @return static|null
     */
    public static function findByCode(string $code): ?self
    {
        return static::findOne(['code' => $code]);
    }
}
