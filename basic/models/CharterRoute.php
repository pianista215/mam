<?php

namespace app\models;

use app\helpers\GeoUtils;
use Yii;

/**
 * This is the model class for table "charter_route".
 *
 * @property int $id
 * @property int $pilot_id
 * @property string $departure
 * @property string $arrival
 * @property int $distance_nm
 *
 * @property Airport $arrival0
 * @property Airport $departure0
 * @property SubmittedFlightPlan[] $submittedFlightPlans
 */
class CharterRoute extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'charter_route';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pilot_id', 'departure', 'arrival', 'distance_nm'], 'required'],
            [['pilot_id', 'distance_nm'], 'integer'],
            [['departure', 'arrival'], 'string', 'max' => 4],
            [['departure', 'arrival'], 'filter', 'filter' => 'strtoupper'],
            [['pilot_id'], 'unique'],
            [['arrival'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['arrival' => 'icao_code']],
            [['departure'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['departure' => 'icao_code']],
        ];
    }

    public function getFplDescription()
    {
        return Yii::t('app', 'Charter flight') . ' '.' ('.$this->departure.'-'.$this->arrival.')';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'pilot_id' => Yii::t('app', 'Pilot'),
            'departure' => Yii::t('app', 'Departure'),
            'arrival' => Yii::t('app', 'Arrival airport (ICAO)'),
            'distance_nm' => Yii::t('app', 'Distance NM'),
        ];
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $dep = $this->departure0;
            $arr = $this->arrival0;
            // We do this to avoid error messages with distance_nm in select-aircraft, real NM will be computed in beforeSave
            if($dep && $arr){
                $this->distance_nm = round(GeoUtils::haversine($dep->latitude, $dep->longitude, $arr->latitude, $arr->longitude, 'nm'));
            } else {
                $this->distance_nm = 0;
            }
            return true;
        }
        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $dep = $this->departure0;
            $arr = $this->arrival0;
            $this->distance_nm = GeoUtils::haversine($dep->latitude, $dep->longitude, $arr->latitude, $arr->longitude, 'nm');
            return true;
        }
        return false;
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
     * Gets query for [[SubmittedFlightPlans]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubmittedFlightPlans()
    {
        return $this->hasMany(SubmittedFlightPlan::class, ['charter_route_id' => 'id']);
    }
}
