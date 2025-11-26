<?php

namespace app\models;

use app\helpers\GeoUtils;
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

    const SCENARIO_UPDATE = 'update';

    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_UPDATE] = ['description'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tour_id', 'departure', 'arrival', 'sequence'], 'required'],
            [['tour_id', 'distance_nm', 'sequence'], 'integer'],
            [['departure', 'arrival'], 'string', 'max' => 4],
            [['departure', 'arrival'], 'filter', 'filter' => 'strtoupper'],
            [['description'], 'string', 'max' => 200],
            [['description'], 'trim'],
            [['tour_id', 'sequence'], 'unique', 'targetAttribute' => ['tour_id', 'sequence']],
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

    public function getFplDescription()
    {
        return Yii::t('app', 'Stage').' '.$this->tour->name.' #'.$this->sequence. ' ('.$this->departure.'-'.$this->arrival.')';
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

    public function getMyFlightsAccepted()
    {
        return $this->hasMany(Flight::class, ['tour_stage_id' => 'id'])
            ->andWhere(['pilot_id' => Yii::$app->user->id])
            ->andWhere(['status' => 'F']);
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

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            // Validate sequence is consecutive
            $maxSeq = static::find()
                ->where(['tour_id' => $this->tour_id])
                ->max('sequence');

            if ($this->sequence != $maxSeq + 1) {
                $this->addError('sequence', 'The sequence number is not the next available.');
                return false;
            }

            // Fill distance
            $dep = $this->departure0;
            $arr = $this->arrival0;
            $this->distance_nm = GeoUtils::haversine($dep->latitude, $dep->longitude, $arr->latitude, $arr->longitude, 'nm');
            return true;
        } else {
            $changed = array_keys($this->getDirtyAttributes());
            if (in_array('departure', $changed) || in_array('arrival', $changed)) {
                $this->addError('departure', 'Departure and arrival cannot be modified once the stage is created.');
                $this->addError('arrival', 'Departure and arrival cannot be modified once the stage is created.');
                return false;
            }
        }

        return true;
    }

}
