<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "submitted_flight_plan".
 *
 * @property int $id
 * @property int $aircraft_id
 * @property string $flight_rules
 * @property string $alternative1_icao
 * @property string|null $alternative2_icao
 * @property string $cruise_speed_value
 * @property string $cruise_speed_unit
 * @property string $flight_level_value
 * @property string $flight_level_unit
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
            [['aircraft_id', 'flight_rules', 'alternative1_icao', 'cruise_speed_value', 'route', 'estimated_time', 'other_information', 'endurance_time', 'route_id', 'pilot_id', 'cruise_speed_unit', 'flight_level_unit'], 'required'],
            [['aircraft_id', 'route_id', 'pilot_id', 'cruise_speed_value', 'flight_level_value', 'estimated_time', 'endurance_time'], 'integer'],
            [['flight_rules', 'cruise_speed_unit'], 'string', 'length' => 1],
            [['flight_rules'], 'in', 'range' => array_keys(SubmittedFlightPlan::getFlightRulesTypes())],
            [['cruise_speed_unit'], 'in', 'range' => SubmittedFlightPlan::getValidSpeedUnits()],
            [['alternative1_icao', 'alternative2_icao', 'cruise_speed_value', 'flight_level_value', 'estimated_time', 'endurance_time'], 'string', 'max' => 4],
            [['route', 'other_information'], 'string', 'max' => 400],
            [['flight_level_unit'], 'string', 'max' => 3],
            [['flight_level_unit'], 'in', 'range' => SubmittedFlightPlan::getValidFlightLevelUnits()],
            [['flight_level_value'], 'validateFlightLevel'],
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
            'alternative1_icao' => 'Altn Aerodrome',
            'alternative2_icao' => 'Alternative2 Icao',
            'cruise_speed_unit' => 'Cruise Speed Unit',
            'cruise_speed_value' => 'Cruise Speed Value',
            'flight_level_unit' => 'Flight Level Unit',
            'flight_level_value' => 'Flight Level Value',
            'route' => 'Route',
            'estimated_time' => 'Total EET',
            'other_information' => 'Other Information',
            'endurance_time' => 'Endurance',
            'route_id' => 'Route ID',
            'pilot_id' => 'Pilot ID',
        ];
    }

    public static function getFlightRulesTypes(){
        return array(
                    'I' => 'IFR (Instrument Flight)',
                    'V' => 'VFR (Visual Flight)',
                    'Y' => 'IFR/VFR (IFR changing to VFR)',
                    'Z' => 'VFR/IFR (VFR changing to IFR)',
        );
    }


    public static function getValidSpeedUnits(){
        return ['N', 'M', 'K'];
    }

    public static function getValidFlightLevelUnits(){
        return ['F', 'A', 'S', 'M', 'VFR'];
    }

    public function validateFlightLevel(){
        if($this->flight_level_unit == 'VFR' && !empty($this->flight_level_value)){
            $this->addError('flight_level_value', "If VFR is selected flight level should be empty");
        }
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
