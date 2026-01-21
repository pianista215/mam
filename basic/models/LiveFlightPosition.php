<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "live_flight_position".
 *
 * @property int $submitted_flight_plan_id
 * @property float $latitude
 * @property float $longitude
 * @property int $altitude
 * @property int $heading
 * @property int $ground_speed
 * @property string $updated_at
 *
 * @property SubmittedFlightPlan $submittedFlightPlan
 */
class LiveFlightPosition extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'live_flight_position';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['submitted_flight_plan_id', 'latitude', 'longitude', 'altitude', 'heading', 'ground_speed'], 'required'],
            [['submitted_flight_plan_id', 'altitude', 'heading', 'ground_speed'], 'integer'],
            [['latitude', 'longitude'], 'number'],
            [['updated_at'], 'safe'],
            [['submitted_flight_plan_id'], 'unique'],
            [['submitted_flight_plan_id'], 'exist', 'skipOnError' => true, 'targetClass' => SubmittedFlightPlan::class, 'targetAttribute' => ['submitted_flight_plan_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'submitted_flight_plan_id' => Yii::t('app', 'Submitted Flight Plan'),
            'latitude' => Yii::t('app', 'Latitude'),
            'longitude' => Yii::t('app', 'Longitude'),
            'altitude' => Yii::t('app', 'Altitude'),
            'heading' => Yii::t('app', 'Heading'),
            'ground_speed' => Yii::t('app', 'Ground Speed'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[SubmittedFlightPlan]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubmittedFlightPlan()
    {
        return $this->hasOne(SubmittedFlightPlan::class, ['id' => 'submitted_flight_plan_id']);
    }

    /**
     * Find all active live flight positions (updated within the last X minutes)
     *
     * @param int $minutes Maximum age in minutes (default 2)
     * @return LiveFlightPosition[]
     */
    public static function findActive($minutes = 2)
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        return self::find()
            ->with(['submittedFlightPlan.pilot', 'submittedFlightPlan.route0', 'submittedFlightPlan.tourStage', 'submittedFlightPlan.charterRoute'])
            ->where(['>', 'updated_at', $cutoff])
            ->all();
    }
}
