<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tour".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $start
 * @property string $end
 *
 * @property TourStage[] $tourStages
 * @property PilotTourCompletion[] $pilotTourCompletions
 * @property Pilot[] $pilots
 */
class Tour extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tour';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'description', 'start', 'end'], 'required'],
            [['name', 'description'], 'trim'],
            [['start', 'end'], 'safe'],
            [['start', 'end'], 'date', 'format' => 'php:Y-m-d'],
            [['end'], 'compare', 'compareAttribute' => 'start', 'operator' => '>', 'message' => 'The date of end must be later than start'],
            [['name'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'start' => 'Start',
            'end' => 'End',
        ];
    }

    /**
     * Gets query for [[PilotTourCompletions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPilotTourCompletions()
    {
        return $this->hasMany(PilotTourCompletion::class, ['tour_id' => 'id']);
    }

    /**
     * Gets query for [[Pilots]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPilots()
    {
        return $this->hasMany(Pilot::class, ['id' => 'pilot_id'])->viaTable('pilot_tour_completion', ['tour_id' => 'id']);
    }


    /**
     * Gets query for [[TourStages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTourStages()
    {
        return $this->hasMany(TourStage::class, ['tour_id' => 'id'])
            ->orderBy(['sequence' => SORT_ASC]);;
    }

    /**
     * Return the flights associated with stages of this tour
     */
    public function getFlights()
    {
        return $this->hasMany(Flight::class, ['tour_stage_id' => 'id'])
            ->via('tourStages');
    }
}
