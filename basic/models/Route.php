<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "route".
 *
 * @property int $id
 * @property string $code
 * @property string $departure
 * @property string $arrival
 * @property int $distance_nm
 *
 * @property Airport $arrival0
 * @property Airport $departure0
 * @property SubmittedFlightplan[] $submittedFlightplans
 */
class Route extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'route';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'departure', 'arrival'], 'required'],
            [['distance_nm'], 'integer'],
            [['code'], 'string', 'max' => 10],
            [['departure', 'arrival'], 'string', 'length' => 4],
            [['code'], 'unique'],
            [['departure', 'arrival'], 'unique', 'targetAttribute' => ['departure', 'arrival']],
            [['arrival'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['arrival' => 'icao_code']],
            [['departure'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['departure' => 'icao_code']],
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
            'departure' => 'Departure',
            'arrival' => 'Arrival',
            'distance_nm' => 'Distance Nm',
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
     * Gets query for [[SubmittedFlightplans]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubmittedFlightplans()
    {
        return $this->hasMany(SubmittedFlightplan::class, ['route_id' => 'id']);
    }

    /**
     * Calculate the distance between twi points in NM (https://gist.github.com/teachmeter/3014803)
     * TODO: Better as static function outside here????
     */
    protected function distanceBetween($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 0.8684;
    }

    public function beforeSave($insert)
        {
            if (parent::beforeSave($insert)) {
                $dep = $this->departure0;
                $arr = $this->arrival0;
                $this->distance_nm = $this->distanceBetween($dep->latitude, $dep->longitude, $arr->latitude, $arr->longitude);
                return true;
            }
            return false;
        }
}
