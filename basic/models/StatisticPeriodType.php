<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_period_type".
 *
 * @property int $id
 * @property string $code
 *
 * @property StatisticPeriod[] $statisticPeriods
 */
class StatisticPeriodType extends \yii\db\ActiveRecord
{
    const TYPE_MONTHLY = 'monthly';
    const TYPE_YEARLY = 'yearly';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_period_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code'], 'required'],
            [['code'], 'string', 'max' => 20],
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
     * Gets query for [[StatisticPeriods]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatisticPeriods()
    {
        return $this->hasMany(StatisticPeriod::class, ['period_type_id' => 'id']);
    }

    /**
     * Find a period type by code
     *
     * @param string $code
     * @return static|null
     */
    public static function findByCode(string $code): ?self
    {
        return static::findOne(['code' => $code]);
    }
}
