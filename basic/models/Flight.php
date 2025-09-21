<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight".
 *
 * @property int $id
 * @property int $pilot_id
 * @property int $aircraft_id
 * @property string $code
 * @property string $departure
 * @property string $arrival
 * @property string $alternative1_icao
 * @property string|null $alternative2_icao
 * @property string $flight_rules
 * @property string $cruise_speed_value
 * @property string $cruise_speed_unit
 * @property string $flight_level_value
 * @property string $flight_level_unit
 * @property string $route
 * @property string $estimated_time
 * @property string $other_information
 * @property string $endurance_time
 * @property string $report_tool
 * @property string $status
 * @property string $creation_date
 * @property string|null $network
 * @property string|null $validator_comments
 * @property int|null $validator_id
 * @property string|null $validation_date
 *
 * @property Aircraft $aircraft
 * @property Airport $alternative1Icao
 * @property Airport $alternative2Icao
 * @property Airport $arrival0
 * @property Airport $departure0
 * @property FlightReport $flightReport
 * @property Pilot $pilot
 * @property Pilot $validator
 */
class Flight extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pilot_id', 'aircraft_id', 'code', 'departure', 'arrival', 'alternative1_icao', 'flight_rules', 'cruise_speed_value', 'cruise_speed_unit', 'flight_level_unit', 'route', 'estimated_time', 'other_information', 'endurance_time', 'report_tool'], 'required'],
            [['pilot_id', 'aircraft_id', 'validator_id'], 'integer'],
            [['creation_date', 'validation_date'], 'safe'],
            [['code'], 'string', 'max' => 10],
            [['departure', 'arrival', 'alternative1_icao', 'alternative2_icao', 'cruise_speed_value', 'flight_level_value', 'estimated_time', 'endurance_time'], 'string', 'max' => 4],
            [['cruise_speed_unit', 'status', 'flight_rules'], 'string', 'max' => 1],
            [['flight_level_unit'], 'string', 'max' => 3],
            [['route', 'other_information', 'validator_comments'], 'string', 'max' => 400],
            [['report_tool'], 'string', 'max' => 20],
            [['network'], 'string', 'max' => 50],
            [['aircraft_id'], 'exist', 'skipOnError' => true, 'targetClass' => Aircraft::class, 'targetAttribute' => ['aircraft_id' => 'id']],
            [['alternative1_icao'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['alternative1_icao' => 'icao_code']],
            [['alternative2_icao'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['alternative2_icao' => 'icao_code']],
            [['arrival'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['arrival' => 'icao_code']],
            [['departure'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['departure' => 'icao_code']],
            [['pilot_id'], 'exist', 'skipOnError' => true, 'targetClass' => Pilot::class, 'targetAttribute' => ['pilot_id' => 'id']],
            [['validator_id'], 'exist', 'skipOnError' => true, 'targetClass' => Pilot::class, 'targetAttribute' => ['validator_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pilot_id' => 'Pilot ID',
            'aircraft_id' => 'Aircraft ID',
            'code' => 'Code',
            'departure' => 'Departure',
            'arrival' => 'Arrival',
            'alternative1_icao' => 'Alternative1 Icao',
            'alternative2_icao' => 'Alternative2 Icao',
            'flight_rules' => 'Flight Rules',
            'cruise_speed_value' => 'Cruise Speed Value',
            'cruise_speed_unit' => 'Cruise Speed Unit',
            'flight_level_value' => 'Flight Level Value',
            'flight_level_unit' => 'Flight Level Unit',
            'route' => 'Route',
            'estimated_time' => 'Estimated Time',
            'other_information' => 'Other Information',
            'endurance_time' => 'Endurance Time',
            'report_tool' => 'Report Tool',
            'status' => 'Status',
            'creation_date' => 'Creation Date',
            'network' => 'Network',
            'validator_comments' => 'Validator Comments',
            'validator_id' => 'Validator ID',
            'validation_date' => 'Validation Date',
        ];
    }

    public static function getFlightStatus(){
        return array(
            'C' => 'Created. Basic information received. Awaiting ACARS files to be uploaded.',
            'S' => 'ACARS files received. Awaiting processing.',
            'V' => 'Pending validation.',
            'F' => 'Finished',
            'R' => 'Rejected'
        );
    }

    public function isProcessed(){
        return $this->status === 'V' || $this->status === 'F' || $this->status === 'R';
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
     * Gets query for [[FlightReport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightReport()
    {
        return $this->hasOne(FlightReport::class, ['flight_id' => 'id']);
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
    * Gets query for [[Validator]].
    *
    * @return \yii\db\ActiveQuery
    */
   public function getValidator()
   {
       return $this->hasOne(Pilot::class, ['id' => 'validator_id']);
   }
}
