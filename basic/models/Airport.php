<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "airport".
 *
 * @property int $id
 * @property string $icao_code
 * @property string $name
 * @property float $latitude
 * @property float $longitude
 * @property string $city
 * @property int $country_id
 *
 * @property Aircraft[] $aircrafts
 * @property Airport[] $arrivals
 * @property Country $country
 * @property Airport[] $departures
 * @property Route[] $routes
 * @property Route[] $routes0
 */
class Airport extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'airport';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['icao_code', 'name', 'latitude', 'longitude', 'city', 'country_id'], 'required'],
            [['latitude', 'longitude'], 'number'],
            [['latitude'], 'compare', 'compareValue' => -90, 'operator' => '>=', 'message' => 'Latitude must be between -90 and 90.'],
            [['latitude'], 'compare', 'compareValue' => 90, 'operator' => '<=', 'message' => 'Latitude must be between -90 and 90.'],
            [['longitude'], 'compare', 'compareValue' => -180, 'operator' => '>=', 'message' => 'Longitude must be between -180 and 180.'],
            [['longitude'], 'compare', 'compareValue' => 180, 'operator' => '<=', 'message' => 'Longitude must be between -180 and 180.'],
            [['country_id'], 'integer'],
            [['icao_code'], 'string', 'length' => 4],
            [['name'], 'string', 'max' => 100],
            [['city'], 'string', 'max' => 80],
            [['icao_code'], 'unique'],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'icao_code' => 'Icao Code',
            'name' => 'Name',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'city' => 'City',
            'country_id' => 'Country ID',
        ];
    }

    /**
     * Gets query for [[Aircrafts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAircrafts()
    {
        return $this->hasMany(Aircraft::class, ['location' => 'icao_code']);
    }

    /**
     * Gets query for [[Arrivals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArrivals()
    {
        return $this->hasMany(Airport::class, ['icao_code' => 'arrival'])->viaTable('route', ['departure' => 'icao_code']);
    }

    /**
     * Gets query for [[Country]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Country::class, ['id' => 'country_id']);
    }

    /**
     * Gets query for [[Departures]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDepartures()
    {
        return $this->hasMany(Airport::class, ['icao_code' => 'departure'])->viaTable('route', ['arrival' => 'icao_code']);
    }

    /**
     * Gets query for [[Routes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRoutes()
    {
        return $this->hasMany(Route::class, ['arrival' => 'icao_code']);
    }

    /**
     * Gets query for [[Routes0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRoutes0()
    {
        return $this->hasMany(Route::class, ['departure' => 'icao_code']);
    }
}
