<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "submitted_flight_plan".
 *
 * @property int $id
 * @property int $aircraft_id
 * @property string $flight_rules
 * @property string $flight_type
 * @property string $alternative1_icao
 * @property string|null $alternative2_icao
 * @property string $cruise_speed
 * @property string $flight_level
 * @property string $route
 * @property string $estimated_time
 * @property string $other_information
 * @property string $endurance_time
 * @property int $route_id
 * @property int $pilot_id
 *
 * @property Aircraft $aircraft
 * @property Airport $alternative1Icao
 * @property Airport $alternative2Icao
 * @property Pilot $pilot
 * @property Route $route0
 */
class SubmittedFlightPlan extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'submitted_flight_plan';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['aircraft_id', 'flight_rules', 'flight_type', 'alternative1_icao', 'cruise_speed', 'flight_level', 'route', 'estimated_time', 'other_information', 'endurance_time', 'route_id', 'pilot_id'], 'required'],
            [['aircraft_id', 'route_id', 'pilot_id'], 'integer'],
            [['flight_rules', 'flight_type'], 'string', 'max' => 1],
            [['alternative1_icao', 'alternative2_icao', 'estimated_time', 'endurance_time'], 'string', 'max' => 4],
            [['cruise_speed', 'flight_level'], 'string', 'max' => 5],
            [['route', 'other_information'], 'string', 'max' => 400],
            [['pilot_id'], 'unique'],
            [['aircraft_id'], 'unique'],
            [['alternative1_icao'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['alternative1_icao' => 'icao_code']],
            [['alternative2_icao'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['alternative2_icao' => 'icao_code']],
            [['aircraft_id'], 'exist', 'skipOnError' => true, 'targetClass' => Aircraft::class, 'targetAttribute' => ['aircraft_id' => 'id']],
            [['pilot_id'], 'exist', 'skipOnError' => true, 'targetClass' => Pilot::class, 'targetAttribute' => ['pilot_id' => 'id']],
            [['route_id'], 'exist', 'skipOnError' => true, 'targetClass' => Route::class, 'targetAttribute' => ['route_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'aircraft_id' => 'Aircraft ID',
            'flight_rules' => 'Flight Rules',
            'flight_type' => 'Flight Type',
            'alternative1_icao' => 'Alternative1 Icao',
            'alternative2_icao' => 'Alternative2 Icao',
            'cruise_speed' => 'Cruise Speed',
            'flight_level' => 'Flight Level',
            'route' => 'Route',
            'estimated_time' => 'Estimated Time',
            'other_information' => 'Other Information',
            'endurance_time' => 'Endurance Time',
            'route_id' => 'Route ID',
            'pilot_id' => 'Pilot ID',
        ];
    }

    /**
     * Gets query for [[Aircraft]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAircraft()
    {
        return $this->hasOne(Aircraft::class, ['id' => 'aircraft_id']);
    }

    /**
     * Gets query for [[Alternative1Icao]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAlternative1Icao()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'alternative1_icao']);
    }

    /**
     * Gets query for [[Alternative2Icao]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAlternative2Icao()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'alternative2_icao']);
    }

    /**
     * Gets query for [[Pilot]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPilot()
    {
        return $this->hasOne(Pilot::class, ['id' => 'pilot_id']);
    }

    /**
     * Gets query for [[Route0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRoute0()
    {
        return $this->hasOne(Route::class, ['id' => 'route_id']);
    }
}
