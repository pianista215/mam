<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_phase_metric_type".
 *
 * @property int $id
 * @property int $flight_phase_type_id
 * @property string $code
 *
 * @property FlightPhaseMetric[] $flightPhaseMetrics
 * @property FlightPhaseType $flightPhaseType
 * @property FlightPhase[] $flightPhases
 */
class FlightPhaseMetricType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight_phase_metric_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['flight_phase_type_id', 'code'], 'required'],
            [['flight_phase_type_id'], 'integer'],
            [['code'], 'string', 'max' => 32],
            [['flight_phase_type_id', 'code'], 'unique', 'targetAttribute' => ['flight_phase_type_id', 'code']],
            [['flight_phase_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightPhaseType::class, 'targetAttribute' => ['flight_phase_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'flight_phase_type_id' => 'Flight Phase Type ID',
            'code' => 'Code',
        ];
    }

    /**
     * Gets query for [[FlightPhaseMetrics]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightPhaseMetrics()
    {
        return $this->hasMany(FlightPhaseMetric::class, ['metric_type_id' => 'id']);
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
     * Gets query for [[FlightPhases]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightPhases()
    {
        return $this->hasMany(FlightPhase::class, ['id' => 'flight_phase_id'])->viaTable('flight_phase_metric', ['metric_type_id' => 'id']);
    }

	public function getFlightPhaseMetricTypeLangs()
    {
        return $this->hasMany(FlightPhaseMetricTypeLang::class, ['flight_phase_metric_type_id' => 'id']);
    }

    public function getLang()
    {
        return $this->hasOne(FlightPhaseMetricTypeLang::class, ['flight_phase_metric_type_id' => 'id'])
            ->andWhere(['language' => Yii::$app->language]);
    }
}
