<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "nav_point".
 *
 * @property int $id
 * @property float $latitude
 * @property float $longitude
 * @property string $identifier
 * @property string $name
 * @property string $point_type FIX, NDB, VOR, DME, ILS-LOC, LOC
 *
 * @property AirwaySegment[] $airwaySegments
 * @property AirwaySegment[] $airwaySegments0
 * @property Navaid[] $navaids
 */
class NavPoint extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nav_point';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['latitude', 'longitude', 'identifier', 'name', 'point_type'], 'required'],
            [['latitude', 'longitude'], 'number'],
            [['identifier', 'point_type'], 'string', 'max' => 10],
            [['name'], 'string', 'max' => 60],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'identifier' => 'Identifier',
            'name' => 'Name',
            'point_type' => 'Point Type',
        ];
    }

    /**
     * Gets query for [[AirwaySegments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAirwaySegments()
    {
        return $this->hasMany(AirwaySegment::class, ['from_nav_point_id' => 'id']);
    }

    /**
     * Gets query for [[AirwaySegments0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAirwaySegments0()
    {
        return $this->hasMany(AirwaySegment::class, ['to_nav_point_id' => 'id']);
    }

    /**
     * Gets query for [[Navaids]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNavaids()
    {
        return $this->hasMany(Navaid::class, ['nav_point_id' => 'id']);
    }
}
