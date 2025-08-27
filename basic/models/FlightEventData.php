<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_event_data".
 *
 * @property int $event_id
 * @property int $attribute_id
 * @property string $value
 *
 * @property FlightEventAttribute $attribute0
 * @property FlightEvent $event
 */
class FlightEventData extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight_event_data';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['event_id', 'attribute_id', 'value'], 'required'],
            [['event_id', 'attribute_id'], 'integer'],
            [['value'], 'string', 'max' => 100],
            [['event_id', 'attribute_id'], 'unique', 'targetAttribute' => ['event_id', 'attribute_id']],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightEvent::class, 'targetAttribute' => ['event_id' => 'id']],
            [['attribute_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightEventAttribute::class, 'targetAttribute' => ['attribute_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'event_id' => 'Event ID',
            'attribute_id' => 'Attribute ID',
            'value' => 'Value',
        ];
    }

    /**
     * Gets query for [[Attribute0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttribute0()
    {
        return $this->hasOne(FlightEventAttribute::class, ['id' => 'attribute_id']);
    }

    /**
     * Gets query for [[Event]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(FlightEvent::class, ['id' => 'event_id']);
    }
}
