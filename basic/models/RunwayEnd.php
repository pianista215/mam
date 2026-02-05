<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "runway_end".
 *
 * @property int $id
 * @property int $runway_id
 * @property string $designator
 * @property float $latitude
 * @property float $longitude
 * @property float $true_heading_deg
 * @property float $displaced_threshold_m
 * @property float $stopway_m
 *
 * @property Runway $runway
 */
class RunwayEnd extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'runway_end';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['runway_id', 'designator', 'latitude', 'longitude', 'true_heading_deg'], 'required'],
            [['runway_id'], 'integer'],
            [['latitude', 'longitude', 'true_heading_deg', 'displaced_threshold_m', 'stopway_m'], 'number'],
            [['designator'], 'string', 'max' => 3],
            [['runway_id', 'designator'], 'unique', 'targetAttribute' => ['runway_id', 'designator']],
            [['runway_id'], 'exist', 'skipOnError' => true, 'targetClass' => Runway::class, 'targetAttribute' => ['runway_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'runway_id' => 'Runway ID',
            'designator' => 'Designator',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'true_heading_deg' => 'True Heading Deg',
            'displaced_threshold_m' => 'Displaced Threshold M',
            'stopway_m' => 'Stopway M',
        ];
    }

    /**
     * Gets query for [[Runway]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRunway()
    {
        return $this->hasOne(Runway::class, ['id' => 'runway_id']);
    }
}
