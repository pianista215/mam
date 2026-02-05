<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "runway".
 *
 * @property int $id
 * @property string $airport_icao
 * @property string $designators
 * @property float $width_m
 * @property float $length_m
 *
 * @property Airport $airportIcao
 * @property RunwayEnd[] $runwayEnds
 */
class Runway extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'runway';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['airport_icao', 'designators', 'width_m', 'length_m'], 'required'],
            [['width_m', 'length_m'], 'number'],
            [['airport_icao'], 'string', 'max' => 4],
            [['designators'], 'string', 'max' => 7],
            [['airport_icao', 'designators'], 'unique', 'targetAttribute' => ['airport_icao', 'designators']],
            [['airport_icao'], 'exist', 'skipOnError' => true, 'targetClass' => Airport::class, 'targetAttribute' => ['airport_icao' => 'icao_code']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'airport_icao' => Yii::t('app', 'Airport Icao'),
            'designators' => Yii::t('app', 'Designators'),
            'width_m' => Yii::t('app', 'Width M'),
            'length_m' => Yii::t('app', 'Length M'),
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
     * Gets query for [[RunwayEnds]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRunwayEnds()
    {
        return $this->hasMany(RunwayEnd::class, ['runway_id' => 'id']);
    }
}
