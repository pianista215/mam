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
 * @property int|null $route_id
 * @property int $pilot_id
 * @property int|null $tour_stage_id
 * @property int|null $charter_route_id
 *
 * @property Aircraft $aircraft
 * @property Airport $alternative1Icao
 * @property Airport $alternative2Icao
 * @property Pilot $pilot
 * @property Route $route0
 * @property TourStage $tourStage
 * @property CharterRoute $charterRoute
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
            [['alternative1_icao', 'alternative2_icao', 'flight_level_value', 'cruise_speed_value', 'route', 'estimated_time', 'other_information', 'endurance_time'], 'trim'],
            [['aircraft_id', 'flight_rules', 'alternative1_icao', 'cruise_speed_value', 'route', 'estimated_time', 'other_information', 'endurance_time', 'pilot_id', 'cruise_speed_unit', 'flight_level_unit'], 'required'],
            [['aircraft_id', 'route_id', 'pilot_id', 'tour_stage_id', 'charter_route_id', 'cruise_speed_value', 'flight_level_value', 'estimated_time', 'endurance_time'], 'integer'],
            [['flight_rules', 'cruise_speed_unit'], 'string', 'length' => 1],
            [['cruise_speed_value', 'flight_level_value', 'estimated_time', 'endurance_time'], 'number', 'min' => 0],
            [['flight_rules'], 'in', 'range' => array_keys(SubmittedFlightPlan::getFlightRulesTypes())],
            [['cruise_speed_unit'], 'in', 'range' => SubmittedFlightPlan::getValidSpeedUnits()],
            [['alternative1_icao', 'alternative2_icao', 'cruise_speed_value', 'flight_level_value', 'estimated_time', 'endurance_time'], 'string', 'max' => 4],
            [['route', 'other_information'], 'string', 'max' => 400],
            [['flight_level_unit'], 'string', 'max' => 3],
            [['flight_level_unit'], 'in', 'range' => SubmittedFlightPlan::getValidFlightLevelUnits()],
            [['flight_level_value'], 'validateFlightLevel', 'skipOnEmpty' => false],
            [['pilot_id'], 'unique'],
            [['aircraft_id'], 'unique'],
            [['alternative1_icao', 'alternative2_icao'], 'filter', 'filter' => 'strtoupper'],
            [['alternative2_icao'], 'default', 'value' => null],
            [['alternative1_icao'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['alternative1_icao' => 'icao_code']],
            [['alternative2_icao'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['alternative2_icao' => 'icao_code']],
            [['aircraft_id'], 'exist', 'skipOnError' => true, 'targetClass' => Aircraft::class, 'targetAttribute' => ['aircraft_id' => 'id']],
            [['pilot_id'], 'exist', 'skipOnError' => true, 'targetClass' => Pilot::class, 'targetAttribute' => ['pilot_id' => 'id']],
            [['route_id'], 'exist', 'skipOnError' => true, 'targetClass' => Route::class, 'targetAttribute' => ['route_id' => 'id']],
            [['tour_stage_id'], 'exist', 'skipOnError' => true, 'targetClass' => TourStage::class, 'targetAttribute' => ['tour_stage_id' => 'id']],
	        [['charter_route_id'], 'exist', 'skipOnError' => true, 'targetClass' => CharterRoute::class, 'targetAttribute' => ['charter_route_id' => 'id']],
            [['pilot_id'], 'validatePilotLocation'],
            [['aircraft_id'], 'validateAircraftLocation'],
            [['route_id', 'tour_stage_id', 'charter_route_id'], 'validateRouteOrStageOrCharter', 'skipOnEmpty' => false],
        ];
    }

    public function beforeValidate()
    {
        if($this->flight_level_unit == 'VFR' && $this->flight_level_value == null){
            $this->flight_level_value = '';
        }
        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'aircraft_id' => Yii::t('app', 'Aircraft'),
            'flight_rules' => Yii::t('app', 'Flight Rules'),
            'alternative1_icao' => Yii::t('app', 'Altn Aerodrome'),
            'alternative2_icao' => Yii::t('app', '2nd Altn Aerodrome'),
            'cruise_speed_unit' => Yii::t('app', 'Cruise Speed Unit'),
            'cruise_speed_value' => Yii::t('app', 'Cruise Speed Value'),
            'flight_level_unit' => Yii::t('app', 'Flight Level Unit'),
            'flight_level_value' => Yii::t('app', 'Flight Level Value'),
            'route' => Yii::t('app', 'Route'),
            'estimated_time' => Yii::t('app', 'Total EET'),
            'other_information' => Yii::t('app', 'Other Information'),
            'endurance_time' => Yii::t('app', 'Endurance'),
            'route_id' => Yii::t('app', 'Route'),
            'pilot_id' => Yii::t('app', 'Pilot'),
            'tour_stage_id' => Yii::t('app', 'Tour Stage'),
            'charter_route_id' => Yii::t('app', 'Charter Route'),
        ];
    }

    public static function getFlightRulesTypes(){
        return array(
                    'I' => 'I - IFR (Instrument Flight)',
                    'V' => 'V - VFR (Visual Flight)',
                    'Y' => 'Y - IFR/VFR (IFR changing to VFR)',
                    'Z' => 'Z - VFR/IFR (VFR changing to IFR)',
        );
    }

    public function isVfrFlight(){
        return isset($this->flight_rules) && 'V' === $this->flight_rules;
    }

    public function isIfrFlight(){
        return isset($this->flight_rules) && 'V' !== $this->flight_rules;
    }


    public static function getValidSpeedUnits(){
        return ['N', 'M', 'K'];
    }

    public static function getValidFlightLevelUnits(){
        return ['F', 'A', 'S', 'M', 'VFR'];
    }

    public function validateFlightLevel($attribute, $params){
        if($this->flight_level_unit == 'VFR'){
            if(!empty($this->flight_level_value)){
                $this->addError('flight_level_value', Yii::t('app', 'If VFR is selected flight level should be empty'));
            }
        } else {
            if(!isset($this->flight_level_value) || empty($this->flight_level_value)){
                $this->addError('flight_level_value', Yii::t('app', 'Flight Level Value cannot be blank if VFR is not selected.'));
            }
        }
    }

    public function validatePilotLocation($attribute, $params){
        if($this->tour_stage_id !== null){
            if ($this->tourStage->departure != $this->pilot->location) {
                $this->addError($attribute, Yii::t('app', 'The pilot is not in the correct location.'));
            }
        } else {
            if ($this->route0->departure != $this->pilot->location) {
                $this->addError($attribute, Yii::t('app', 'The pilot is not in the correct location.'));
            }
        }
    }

    public function validateAircraftLocation($attribute, $params)
    {
        if($this->tour_stage_id !== null){
            if ($this->tourStage->departure != $this->aircraft->location) {
                $this->addError($attribute, Yii::t('app', 'The aircraft is not in the correct location.'));
            }
        } else {
            if ($this->route0->departure != $this->aircraft->location) {
                $this->addError($attribute, Yii::t('app', 'The aircraft is not in the correct location.'));
            }
        }
    }

    public function validateRouteOrStageOrCharter($attribute, $params)
    {
        $set = array_filter([$this->route_id, $this->tour_stage_id, $this->charter_route_id]);

        $count = count($set);

        if ($count === 0) {
            $msg = Yii::t('app', 'There are no route, tour stage or charter associated.');
            $this->addError('route_id', $msg);
            $this->addError('tour_stage_id', $msg);
            $this->addError('charter_route_id', $msg);
        } elseif ($count > 1) {
            $msg = Yii::t('app', 'Only one of route, tour stage or charter route can be associated.');
            $this->addError('route_id', $msg);
            $this->addError('tour_stage_id', $msg);
            $this->addError('charter_route_id', $msg);
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

    /**
     * Gets query for [[TourStage]].
     *
     * @return \yii\db\ActiveQuery
     */
   public function getTourStage()
   {
       return $this->hasOne(TourStage::class, ['id' => 'tour_stage_id']);
   }

   /**
    * Gets query for [[CharterRoute]].
    *
    * @return \yii\db\ActiveQuery
    */
   public function getCharterRoute()
   {
       return $this->hasOne(CharterRoute::class, ['id' => 'charter_route_id']);
   }

}
