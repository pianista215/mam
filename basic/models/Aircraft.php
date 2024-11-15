<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "aircraft".
 *
 * @property int $id
 * @property int $aircraft_type_id
 * @property string $registration
 * @property string $name
 * @property string $location
 * @property float $hours_flown
 *
 * @property AircraftType $aircraftType
 * @property Airport $location0
 * @property SubmittedFlightplan[] $submittedFlightplans
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
            [['aircraft_type_id', 'registration', 'name', 'location'], 'required'],
            [['aircraft_type_id'], 'integer'],
            [['hours_flown'], 'number'],
            [['registration'], 'string', 'max' => 10],
            [['name'], 'string', 'max' => 20],
            [['location'], 'string', 'max' => 4],
            [['registration'], 'unique'],
            [['name'], 'unique'],
            [['aircraft_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => AircraftType::class, 'targetAttribute' => ['aircraft_type_id' => 'id']],
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
            'aircraft_type_id' => 'Aircraft Type ID',
            'registration' => 'Registration',
            'name' => 'Name',
            'location' => 'Location',
            'hours_flown' => 'Hours Flown',
        ];
    }

    /**
     * Gets query for [[AircraftType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAircraftType()
    {
        return $this->hasOne(AircraftType::class, ['id' => 'aircraft_type_id']);
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
     * Gets query for [[SubmittedFlightplans]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubmittedFlightplans()
    {
        return $this->hasMany(SubmittedFlightplan::class, ['aircraft_id' => 'id']);
    }
}
