<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_phase_metric".
 *
 * @property int $flight_phase_id
 * @property int $metric_type_id
 * @property string $value
 *
 * @property FlightPhase $flightPhase
 * @property FlightPhaseMetricType $metricType
 */
class FlightPhaseMetric extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight_phase_metric';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['flight_phase_id', 'metric_type_id', 'value'], 'required'],
            [['flight_phase_id', 'metric_type_id'], 'integer'],
            [['value'], 'string', 'max' => 100],
            [['flight_phase_id', 'metric_type_id'], 'unique', 'targetAttribute' => ['flight_phase_id', 'metric_type_id']],
            [['flight_phase_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightPhase::class, 'targetAttribute' => ['flight_phase_id' => 'id']],
            [['metric_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightPhaseMetricType::class, 'targetAttribute' => ['metric_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'flight_phase_id' => 'Flight Phase ID',
            'metric_type_id' => 'Metric Type ID',
            'value' => 'Value',
        ];
    }

    /**
     * Gets query for [[FlightPhase]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightPhase()
    {
        return $this->hasOne(FlightPhase::class, ['id' => 'flight_phase_id']);
    }

    /**
     * Gets query for [[MetricType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMetricType()
    {
        return $this->hasOne(FlightPhaseMetricType::class, ['id' => 'metric_type_id']);
    }
}
