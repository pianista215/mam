<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "airway_segment".
 *
 * @property int $id
 * @property int $from_nav_point_id
 * @property int $to_nav_point_id
 * @property string $direction BOTH or FORWARD
 * @property string $airway_type LOW or HIGH
 * @property int $base_alt_ft
 * @property int $top_alt_ft
 * @property string $airway_names
 *
 * @property NavPoint $fromNavPoint
 * @property NavPoint $toNavPoint
 */
class AirwaySegment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'airway_segment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['from_nav_point_id', 'to_nav_point_id', 'airway_type', 'base_alt_ft', 'top_alt_ft', 'airway_names'], 'required'],
            [['from_nav_point_id', 'to_nav_point_id', 'base_alt_ft', 'top_alt_ft'], 'integer'],
            [['direction'], 'string', 'max' => 8],
            [['airway_type'], 'string', 'max' => 4],
            [['airway_names'], 'string', 'max' => 100],
            [['from_nav_point_id'], 'exist', 'skipOnError' => true, 'targetClass' => NavPoint::class, 'targetAttribute' => ['from_nav_point_id' => 'id']],
            [['to_nav_point_id'], 'exist', 'skipOnError' => true, 'targetClass' => NavPoint::class, 'targetAttribute' => ['to_nav_point_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'from_nav_point_id' => 'From Nav Point ID',
            'to_nav_point_id' => 'To Nav Point ID',
            'direction' => 'Direction',
            'airway_type' => 'Airway Type',
            'base_alt_ft' => 'Base Alt Ft',
            'top_alt_ft' => 'Top Alt Ft',
            'airway_names' => 'Airway Names',
        ];
    }

    /**
     * Gets query for [[FromNavPoint]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFromNavPoint()
    {
        return $this->hasOne(NavPoint::class, ['id' => 'from_nav_point_id']);
    }

    /**
     * Gets query for [[ToNavPoint]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getToNavPoint()
    {
        return $this->hasOne(NavPoint::class, ['id' => 'to_nav_point_id']);
    }
}
