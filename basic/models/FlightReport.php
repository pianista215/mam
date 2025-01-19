<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_report".
 *
 * @property int $id
 * @property int $flight_id
 * @property string|null $start_time
 * @property string|null $end_time
 * @property int|null $flight_time_minutes
 * @property int|null $block_time_minutes
 * @property int|null $total_fuel_burn_kg
 * @property int|null $distance_nm
 * @property string|null $pilot_comments
 * @property string|null $validator_comments
 * @property int|null $initial_fuel_on_board
 * @property int|null $zero_fuel_weight
 * @property int|null $crash
 *
 * @property AcarsFile[] $acarsFiles
 * @property Flight $flight
 */
class FlightReport extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight_report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['flight_id'], 'required'],
            [['flight_id', 'flight_time_minutes', 'block_time_minutes', 'total_fuel_burn_kg', 'distance_nm', 'initial_fuel_on_board', 'zero_fuel_weight', 'crash'], 'integer'],
            [['start_time', 'end_time'], 'safe'],
            [['start_time', 'end_time'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['pilot_comments', 'validator_comments'], 'string', 'max' => 400],
            [['flight_id'], 'unique'],
            [['flight_id'], 'exist', 'skipOnError' => true, 'targetClass' => Flight::class, 'targetAttribute' => ['flight_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'flight_id' => 'Flight ID',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'flight_time_minutes' => 'Flight Time Minutes',
            'block_time_minutes' => 'Block Time Minutes',
            'total_fuel_burn_kg' => 'Total Fuel Burn Kg',
            'distance_nm' => 'Distance Nm',
            'pilot_comments' => 'Pilot Comments',
            'validator_comments' => 'Validator Comments',
            'initial_fuel_on_board' => 'Initial Fuel On Board',
            'zero_fuel_weight' => 'Zero Fuel Weight',
            'crash' => 'Crash',
        ];
    }

    /**
     * Gets query for [[AcarsFiles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAcarsFiles()
    {
        return $this->hasMany(AcarsFile::class, ['flight_report_id' => 'id']);
    }

    /**
     * Gets query for [[Flight]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlight()
    {
        return $this->hasOne(Flight::class, ['id' => 'flight_id']);
    }
}
