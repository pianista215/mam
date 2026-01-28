<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_record_type".
 *
 * @property int $id
 * @property string $code
 * @property string $entity_type
 * @property string $comparison
 * @property string|null $unit
 *
 * @property StatisticRecord[] $statisticRecords
 * @property StatisticRecordTypeLang[] $statisticRecordTypeLangs
 * @property StatisticRecordTypeLang $lang
 */
class StatisticRecordType extends \yii\db\ActiveRecord
{
    const CODE_LONGEST_FLIGHT_TIME = 'longest_flight_time';
    const CODE_LONGEST_FLIGHT_DISTANCE = 'longest_flight_distance';

    const ENTITY_FLIGHT = 'flight';

    const COMPARISON_MAX = 'MAX';
    const COMPARISON_MIN = 'MIN';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_record_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'entity_type'], 'required'],
            [['code', 'entity_type', 'unit'], 'string', 'max' => 50],
            [['comparison'], 'string', 'max' => 3],
            [['comparison'], 'in', 'range' => [self::COMPARISON_MAX, self::COMPARISON_MIN]],
            [['comparison'], 'default', 'value' => self::COMPARISON_MAX],
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
            'comparison' => 'Comparison',
            'unit' => 'Unit',
        ];
    }

    /**
     * Gets query for [[StatisticRecords]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatisticRecords()
    {
        return $this->hasMany(StatisticRecord::class, ['record_type_id' => 'id']);
    }

    /**
     * Gets query for [[StatisticRecordTypeLangs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatisticRecordTypeLangs()
    {
        return $this->hasMany(StatisticRecordTypeLang::class, ['record_type_id' => 'id']);
    }

    /**
     * Gets query for [[Lang]] (current language translation).
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        return $this->hasOne(StatisticRecordTypeLang::class, ['record_type_id' => 'id'])
            ->andWhere(['language' => Yii::$app->language]);
    }

    /**
     * Find a record type by code
     *
     * @param string $code
     * @return static|null
     */
    public static function findByCode(string $code): ?self
    {
        return static::findOne(['code' => $code]);
    }

    /**
     * Check if this record type uses MAX comparison
     *
     * @return bool
     */
    public function isMax(): bool
    {
        return $this->comparison === self::COMPARISON_MAX;
    }

    /**
     * Check if this record type uses MIN comparison
     *
     * @return bool
     */
    public function isMin(): bool
    {
        return $this->comparison === self::COMPARISON_MIN;
    }
}
