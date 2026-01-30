<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "flight_phase_type".
 *
 * @property int $id
 * @property string $code
 *
 * @property FlightPhaseMetricType[] $flightPhaseMetricTypes
 * @property FlightPhase[] $flightPhases
 */
class FlightPhaseType extends \yii\db\ActiveRecord
{
    const CODE_FINAL_LANDING = 'final_landing';

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
            [['code'], 'required'],
            [['code'], 'string', 'max' => 32],
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

    public function getFlightPhaseTypeLangs()
	{
        return $this->hasMany(FlightPhaseTypeLang::class, ['flight_phase_type_id' => 'id']);
    }

    public function getLang()
    {
        return $this->hasOne(FlightPhaseTypeLang::class, ['flight_phase_type_id' => 'id'])
            ->andWhere(['language' => Yii::$app->language]);
    }
}
