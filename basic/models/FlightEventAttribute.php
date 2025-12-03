<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_event_attribute".
 *
 * @property int $id
 * @property string $name
 * @property string $code
 *
 * @property FlightEvent[] $events
 * @property FlightEventData[] $flightEventDatas
 */
class FlightEventAttribute extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight_event_attribute';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'code'], 'required'],
            [['name'], 'string', 'max' => 50],
            [['code'], 'string', 'max' => 32],
            [['code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'code' => Yii::t('app', 'Code'),
        ];
    }

    /**
     * Gets query for [[Events]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(FlightEvent::class, ['id' => 'event_id'])->viaTable('flight_event_data', ['attribute_id' => 'id']);
    }

    /**
     * Gets query for [[FlightEventDatas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightEventDatas()
    {
        return $this->hasMany(FlightEventData::class, ['attribute_id' => 'id']);
    }
}
