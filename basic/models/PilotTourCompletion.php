<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "pilot_tour_completion".
 *
 * @property int $id
 * @property int $pilot_id
 * @property int $tour_id
 * @property string $completed_at
 *
 * @property Pilot $pilot
 * @property Tour $tour
 */
class PilotTourCompletion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pilot_tour_completion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pilot_id', 'tour_id', 'completed_at'], 'required'],
            [['pilot_id', 'tour_id'], 'integer'],
            [['completed_at'], 'safe'],
            ['completed_at', 'date', 'format' => 'php:Y-m-d'],
            [['pilot_id', 'tour_id'], 'unique', 'targetAttribute' => ['pilot_id', 'tour_id']],
            [['pilot_id'], 'exist', 'skipOnError' => true, 'targetClass' => Pilot::class, 'targetAttribute' => ['pilot_id' => 'id']],
            [['tour_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tour::class, 'targetAttribute' => ['tour_id' => 'id']],
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
            'tour_id' => 'Tour ID',
            'completed_at' => 'Completed At',
        ];
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
     * Gets query for [[Tour]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTour()
    {
        return $this->hasOne(Tour::class, ['id' => 'tour_id']);
    }
}
