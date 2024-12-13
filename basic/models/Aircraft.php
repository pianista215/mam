<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "aircraft".
 *
 * @property int $id
 * @property int $aircraft_configuration_id
 * @property string $registration
 * @property string $name
 * @property string $location
 * @property float $hours_flown
 *
 * @property AircraftConfiguration $aircraftConfiguration
 * @property Airport $location0
 * @property SubmittedFlightPlan $submittedFlightPlan
 */
class Aircraft extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aircraft';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['aircraft_configuration_id', 'registration', 'name', 'location'], 'required'],
            [['aircraft_configuration_id'], 'integer'],
            [['hours_flown'], 'number', 'min' => 0],
            [['registration'], 'string', 'max' => 10],
            [['name'], 'string', 'max' => 20],
            [['location'], 'string', 'length' => 4],
            [['registration'], 'unique'],
            [['name'], 'unique'],
            [['aircraft_configuration_id'], 'exist', 'skipOnError' => true, 'targetClass' => AircraftConfiguration::class, 'targetAttribute' => ['aircraft_configuration_id' => 'id']],
            [['location'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['location' => 'icao_code']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'aircraft_configuration_id' => 'Aircraft Configuration ID',
            'registration' => 'Registration',
            'name' => 'Name',
            'location' => 'Location',
            'hours_flown' => 'Hours Flown',
        ];
    }

    /**
     * Gets query for [[AircraftConfiguration]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAircraftConfiguration()
    {
        return $this->hasOne(AircraftConfiguration::class, ['id' => 'aircraft_configuration_id']);
    }

    /**
     * Gets query for [[Location0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLocation0()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'location']);
    }

    /**
     * Gets query for [[SubmittedFlightPlan]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubmittedFlightPlan()
    {
        return $this->hasOne(SubmittedFlightPlan::class, ['aircraft_id' => 'id']);
    }
}
