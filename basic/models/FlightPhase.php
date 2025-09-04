<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_phase".
 *
 * @property int $id
 * @property int $flight_report_id
 * @property int $flight_phase_type_id
 * @property string $start
 * @property string $end
 *
 * @property FlightEvent[] $flightEvents
 * @property FlightPhaseMetric[] $flightPhaseMetrics
 * @property FlightPhaseType $flightPhaseType
 * @property FlightReport $flightReport
 * @property FlightPhaseMetricType[] $metricTypes
 */
class FlightPhase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight_phase';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['flight_report_id', 'flight_phase_type_id', 'start', 'end'], 'required'],
            [['flight_report_id', 'flight_phase_type_id'], 'integer'],
            [['start', 'end'], 'safe'],
            [['flight_report_id', 'flight_phase_type_id', 'start', 'end'], 'unique', 'targetAttribute' => ['flight_report_id', 'flight_phase_type_id', 'start', 'end']],
            [['flight_phase_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightPhaseType::class, 'targetAttribute' => ['flight_phase_type_id' => 'id']],
            [['flight_report_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightReport::class, 'targetAttribute' => ['flight_report_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'flight_report_id' => 'Flight Report ID',
            'flight_phase_type_id' => 'Flight Phase Type ID',
            'start' => 'Start',
            'end' => 'End',
        ];
    }

    /**
     * Gets query for [[FlightEvents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightEvents()
    {
        return $this->hasMany(FlightEvent::class, ['phase_id' => 'id']);
    }

    /**
     * Gets query for [[FlightPhaseMetrics]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightPhaseMetrics()
    {
        return $this->hasMany(FlightPhaseMetric::class, ['flight_phase_id' => 'id']);
    }

    /**
     * Gets query for [[FlightPhaseType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightPhaseType()
    {
        return $this->hasOne(FlightPhaseType::class, ['id' => 'flight_phase_type_id']);
    }

    /**
     * Gets query for [[FlightReport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightReport()
    {
        return $this->hasOne(FlightReport::class, ['id' => 'flight_report_id']);
    }

    /**
     * Gets query for [[MetricTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMetricTypes()
    {
        return $this->hasMany(FlightPhaseMetricType::class, ['id' => 'metric_type_id'])->viaTable('flight_phase_metric', ['flight_phase_id' => 'id']);
    }
}
