<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistic_record".
 *
 * @property int $id
 * @property int $period_id
 * @property int $record_type_id
 * @property int $entity_id
 * @property float $value
 * @property int $is_all_time_record
 *
 * @property StatisticPeriod $period
 * @property StatisticRecordType $recordType
 */
class StatisticRecord extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'statistic_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['period_id', 'record_type_id', 'entity_id', 'value'], 'required'],
            [['period_id', 'record_type_id', 'entity_id', 'is_all_time_record'], 'integer'],
            [['value'], 'number'],
            [['is_all_time_record'], 'default', 'value' => 0],
            [['period_id', 'record_type_id'], 'unique', 'targetAttribute' => ['period_id', 'record_type_id']],
            [['period_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatisticPeriod::class, 'targetAttribute' => ['period_id' => 'id']],
            [['record_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => StatisticRecordType::class, 'targetAttribute' => ['record_type_id' => 'id']],
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
            'record_type_id' => 'Record Type ID',
            'entity_id' => 'Entity ID',
            'value' => 'Value',
            'is_all_time_record' => 'Is All Time Record',
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
     * Gets query for [[RecordType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRecordType()
    {
        return $this->hasOne(StatisticRecordType::class, ['id' => 'record_type_id']);
    }

    /**
     * Check if this is an all-time record
     *
     * @return bool
     */
    public function isAllTimeRecord(): bool
    {
        return (bool) $this->is_all_time_record;
    }

    /**
     * Find or create a record for a period and type
     *
     * @param int $periodId
     * @param int $recordTypeId
     * @return static
     */
    public static function findOrCreate(int $periodId, int $recordTypeId): self
    {
        $record = static::findOne([
            'period_id' => $periodId,
            'record_type_id' => $recordTypeId,
        ]);

        if (!$record) {
            $record = new static([
                'period_id' => $periodId,
                'record_type_id' => $recordTypeId,
            ]);
        }

        return $record;
    }
}
