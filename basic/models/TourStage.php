<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tour_stage".
 *
 * @property int $id
 * @property int $tour_id
 * @property string $departure
 * @property string $arrival
 * @property int $distance_nm
 * @property string|null $description
 * @property int $sequence
 *
 * @property Airport $arrival0
 * @property Airport $departure0
 * @property Flight[] $flights
 * @property Tour $tour
 */
class TourStage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tour_stage';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'tour_id', 'departure', 'arrival', 'distance_nm', 'sequence'], 'required'],
            [['id', 'tour_id', 'distance_nm', 'sequence'], 'integer'],
            [['departure', 'arrival'], 'string', 'max' => 4],
            [['description'], 'string', 'max' => 200],
            [['tour_id', 'sequence'], 'unique', 'targetAttribute' => ['tour_id', 'sequence']],
            [['id'], 'unique'],
            [['arrival'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['arrival' => 'icao_code']],
            [['departure'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['departure' => 'icao_code']],
            [['tour_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tour::class, 'targetAttribute' => ['tour_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tour_id' => 'Tour ID',
            'departure' => 'Departure',
            'arrival' => 'Arrival',
            'distance_nm' => 'Distance Nm',
            'description' => 'Description',
            'sequence' => 'Sequence',
        ];
    }

    /**
     * Gets query for [[Arrival0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArrival0()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'arrival']);
    }

    /**
     * Gets query for [[Departure0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDeparture0()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'departure']);
    }

    /**
     * Gets query for [[Flights]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlights()
    {
        return $this->hasMany(Flight::class, ['tour_stage_id' => 'id']);
    }

    /**
     * Gets query for [[Tour]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTour()
    {
        return $this->hasOne(Tour::class, ['id' => 'tour_id']);
    }
}
