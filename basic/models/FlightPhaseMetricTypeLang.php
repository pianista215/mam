<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_phase_metric_type_lang".
 *
 * @property int $id
 * @property int $flight_phase_metric_type_id
 * @property string $language
 * @property string $name
 *
 * @property FlightPhaseMetricType $flightPhaseMetricType
 */
class FlightPhaseMetricTypeLang extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'flight_phase_metric_type_lang';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['flight_phase_metric_type_id', 'language', 'name'], 'required'],
            [['flight_phase_metric_type_id'], 'integer'],
            [['language'], 'string', 'max' => 2],
            [['name'], 'string', 'max' => 50],
            [['flight_phase_metric_type_id', 'language'], 'unique', 'targetAttribute' => ['flight_phase_metric_type_id', 'language']],
            [['flight_phase_metric_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => FlightPhaseMetricType::class, 'targetAttribute' => ['flight_phase_metric_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'flight_phase_metric_type_id' => 'Flight Phase Metric Type ID',
            'language' => 'Language',
            'name' => 'Name',
        ];
    }

    /**
     * Gets query for [[FlightPhaseMetricType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFlightPhaseMetricType()
    {
        return $this->hasOne(FlightPhaseMetricType::class, ['id' => 'flight_phase_metric_type_id']);
    }
}
