<?php

namespace tests\unit\models;

use app\models\Airport;
use app\models\Country;
use tests\unit\DbTestCase;
use Yii;

class AirportTest extends DbTestCase
{

    protected function _before(){
        parent::_before();

        $country = new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']);
        $country->save(false);
    }

    public function testCreateValidAirport()
    {
        $airport = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'Madrid-Barajas',
            'latitude' => 40.471926,
            'longitude' => -3.56264,
            'city' => 'Madrid',
            'country_id' => 1,
        ]);
        $this->assertTrue($airport->save());
    }

    public function testIcaoCodeLength()
    {
        $airport = new Airport([
            'icao_code' => 'LE',
            'name' => 'Invalid Airport',
            'latitude' => 40.0,
            'longitude' => -3.0,
            'city' => 'Madrid',
            'country_id' => 1,
        ]);
        $this->assertFalse($airport->save());
        $this->assertArrayHasKey('icao_code', $airport->getErrors());
    }

    public function testCountryExists()
    {
        $airport = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'Madrid-Barajas',
            'latitude' => 40.471926,
            'longitude' => -3.56264,
            'city' => 'Madrid',
            'country_id' => 999,
        ]);
        $this->assertFalse($airport->save());
        $this->assertArrayHasKey('country_id', $airport->getErrors());
    }

    public function testLatitudeRange()
    {
        $airport = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'Madrid-Barajas',
            'latitude' => 95.0,
            'longitude' => -3.56264,
            'city' => 'Madrid',
            'country_id' => 1,
        ]);
        $this->assertFalse($airport->save());
        $this->assertArrayHasKey('latitude', $airport->getErrors());
    }

    public function testLongitudeRange()
    {
        $airport = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'Madrid-Barajas',
            'latitude' => 40.471926,
            'longitude' => -185.0,
            'city' => 'Madrid',
            'country_id' => 1,
        ]);
        $this->assertFalse($airport->save());
        $this->assertArrayHasKey('longitude', $airport->getErrors());
    }

}