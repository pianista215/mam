<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_event".
 *
 * @property int $id
 * @property int $phase_id
 * @property string $timestamp
 *
 * @property FlightEventAttribute[] $attributes0
 * @property FlightEventData[] $flightEventDatas
 * @property FlightPhase $phase
 */
class FlightEvent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight_event';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phase_id', 'timestamp'], 'required'],
            [['phase_id'], 'integer'],
            [['timestamp'], 'safe'],
            [['phase_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightPhase::class, 'targetAttribute' => ['phase_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phase_id' => Yii::t('app', 'Phase'),
            'timestamp' => Yii::t('app', 'Timestamp'),
        ];
    }

    /**
     * Gets query for [[Attributes0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttributes0()
    {
        return $this->hasMany(FlightEventAttribute::class, ['id' => 'attribute_id'])->viaTable('flight_event_data', ['event_id' => 'id']);
    }

    /**
     * Gets query for [[FlightEventDatas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightEventDatas()
    {
        return $this->hasMany(FlightEventData::class, ['event_id' => 'id']);
    }

    /**
     * Gets query for [[Phase]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPhase()
    {
        return $this->hasOne(FlightPhase::class, ['id' => 'phase_id']);
    }
}
