<?php

namespace app\models;

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
            [['pilot_id'], 'unique'],
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
            'id' => Yii::t('app', 'ID'),
            'pilot_id' => Yii::t('app', 'Pilot'),
            'departure' => Yii::t('app', 'Departure'),
            'arrival' => Yii::t('app', 'Arrival'),
            'distance_nm' => Yii::t('app', 'Distance NM'),
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
     * Gets query for [[SubmittedFlightPlans]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubmittedFlightPlans()
    {
        return $this->hasMany(SubmittedFlightPlan::class, ['charter_route_id' => 'id']);
    }
}
