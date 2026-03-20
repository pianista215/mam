<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "navaid".
 *
 * @property int $id
 * @property int $nav_point_id
 * @property string $frequency Formatted frequency: kHz for NDB (e.g. 362), MHz for VOR/ILS/DME (e.g. 116.80)
 * @property int|null $range_nm
 * @property float|null $true_bearing_deg
 * @property float|null $glideslope_deg
 * @property string|null $airport_icao
 * @property string|null $runway_designator
 *
 * @property Airport $airportIcao
 * @property NavPoint $navPoint
 */
class Navaid extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'navaid';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nav_point_id', 'frequency'], 'required'],
            [['nav_point_id', 'range_nm'], 'integer'],
            [['true_bearing_deg', 'glideslope_deg'], 'number'],
            [['frequency'], 'string', 'max' => 8],
            [['airport_icao'], 'string', 'max' => 4],
            [['runway_designator'], 'string', 'max' => 6],
            [['airport_icao'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['airport_icao' => 'icao_code']],
            [['nav_point_id'], 'exist', 'skipOnError' => true, 'targetClass' => NavPoint::class, 'targetAttribute' => ['nav_point_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nav_point_id' => 'Nav Point ID',
            'frequency' => 'Frequency',
            'range_nm' => 'Range Nm',
            'true_bearing_deg' => 'True Bearing Deg',
            'glideslope_deg' => 'Glideslope Deg',
            'airport_icao' => 'Airport Icao',
            'runway_designator' => 'Runway Designator',
        ];
    }

    /**
     * Gets query for [[AirportIcao]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAirportIcao()
    {
        return $this->hasOne(Airport::class, ['icao_code' => 'airport_icao']);
    }

    /**
     * Gets query for [[NavPoint]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNavPoint()
    {
        return $this->hasOne(NavPoint::class, ['id' => 'nav_point_id']);
    }
}
