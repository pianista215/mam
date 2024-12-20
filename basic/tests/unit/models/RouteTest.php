<?php

namespace tests\unit\models;

use app\models\Airport;
use app\models\Country;
use app\models\Route;
use tests\unit\BaseUnitTest;
use Yii;

class RouteTest extends BaseUnitTest
{

    protected function _before()
    {
        parent::_before();

        $country = new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']);
        $country->save(false);

        $airport1 = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'Madrid-Barajas',
            'latitude' => 40.471926,
            'longitude' => -3.56264,
            'city' => 'Madrid',
            'country_id' => 1,
        ]);
        $airport1->save(false);

        $airport2 = new Airport([
            'icao_code' => 'LEBL',
            'name' => 'Barcelona-El Prat',
            'latitude' => 41.297445,
            'longitude' => 2.0833,
            'city' => 'Barcelona',
            'country_id' => 1,
        ]);
        $airport2->save(false);
    }

    public function testCreateValidRoute()
    {
        $route = new Route([
            'code' => 'MAD-BCN',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
        ]);
        $this->assertTrue($route->save());
        $this->assertNotEmpty($route->distance_nm);
    }

    public function testUniqueCode()
    {
        $route1 = new Route([
            'code' => 'MAD-BCN',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
        ]);
        $route1->save(false);

        $route2 = new Route([
            'code' => 'MAD-BCN',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
        ]);
        $this->assertFalse($route2->save());
        $this->assertArrayHasKey('code', $route2->getErrors());
    }

    public function testTrimToUpper()
    {
        $route = new Route([
            'code' => '   Mad - Bcn ',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
        ]);
        $this->assertTrue($route->save());
        $this->assertEquals($route->code, 'MAD-BCN');
    }

    public function testUniqueDepartureArrivalPair()
    {
        $route1 = new Route([
            'code' => 'MAD-BCN',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
        ]);
        $route1->save(false);

        $route2 = new Route([
            'code' => 'MAD-BCN-ALT',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
        ]);
        $this->assertFalse($route2->save());
        $this->assertArrayHasKey('departure', $route2->getErrors());
    }

    public function testInvalidDeparture()
    {
        $route = new Route([
            'code' => 'INVALID-BCN',
            'departure' => 'XXXX',
            'arrival' => 'LEBL',
        ]);
        $this->assertFalse($route->save());
        $this->assertArrayHasKey('departure', $route->getErrors());
    }

    public function testInvalidArrival()
    {
        $route = new Route([
            'code' => 'MAD-INVALID',
            'departure' => 'LEMD',
            'arrival' => 'YYYY',
        ]);
        $this->assertFalse($route->save());
        $this->assertArrayHasKey('arrival', $route->getErrors());
    }

    public function testMaxLengthCode()
    {
        $route = new Route([
            'code' => str_repeat('A', 11),
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
        ]);
        $this->assertFalse($route->save());
        $this->assertArrayHasKey('code', $route->getErrors());
    }

    public function testDistanceCalculation()
    {
        $route = new Route([
            'code' => 'MAD-BCN',
            'departure' => 'LEMD',
            'arrival' => 'LEBL',
        ]);
        $this->assertTrue($route->save(false));
        $this->assertEqualsWithDelta(261, $route->distance_nm, 1); // Approximate distance with a margin
    }

}