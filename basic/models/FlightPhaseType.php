<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_phase_type".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 *
 * @property FlightPhaseMetricType[] $flightPhaseMetricTypes
 * @property FlightPhase[] $flightPhases
 */
class FlightPhaseType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight_phase_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['code'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 50],
            [['code'], 'unique'],
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
            'name' => 'Name',
        ];
    }

    /**
     * Gets query for [[FlightPhaseMetricTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightPhaseMetricTypes()
    {
        return $this->hasMany(FlightPhaseMetricType::class, ['flight_phase_type_id' => 'id']);
    }

    /**
     * Gets query for [[FlightPhases]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightPhases()
    {
        return $this->hasMany(FlightPhase::class, ['flight_phase_type_id' => 'id']);
    }
}
