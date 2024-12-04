<?php

namespace tests\unit\models;

use app\models\Airport;
use app\models\Country;
use app\models\Pilot;
use tests\unit\DbTestCase;
use Yii;

class PilotTest extends DbTestCase
{

    protected function _before(){
        parent::_before();

        $country = new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']);
        $country->save(false);

        $airport = new Airport(
            [
                'id' => 1,
                'icao_code' => 'LEVD',
                'name' => 'Villanubla',
                'latitude' => 0.0,
                'longitude' => 0.0,
                'city' => 'Valladolid',
                'country_id' => 1
            ]
        );
        $airport->save(false);
    }

    public function testValidationRules()
    {
        $pilot = new Pilot();

        $this->assertFalse($pilot->validate());

        $pilot->name = 'John';
        $pilot->surname = 'Doe';
        $pilot->email = 'john.doe@example.com';
        $pilot->country_id = 1;
        $pilot->city = 'New York';
        $pilot->location = 'LEVD';
        $pilot->password = 'SecurePass123!';
        $pilot->date_of_birth = '1990-01-01';
        $this->assertTrue(
            $pilot->validate(),
            'Validation failed: ' . json_encode($pilot->getErrors())
        );
    }

    public function testFindByLicense()
    {
        $pilot = new Pilot([
            'license' => 'ABC12345',
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => Yii::$app->security->generatePasswordHash('SecurePass123!'),
            'country_id' => 1,
            'city' => 'New York',
            'location' => 'LEVD',
            'date_of_birth' => '1990-01-01',
        ]);
        $pilot->save(false);

        $foundPilot = Pilot::findByLicense('ABC12345');
        $this->assertInstanceOf(Pilot::class, $foundPilot);
        $this->assertEquals('John', $foundPilot->name);
        $this->assertEquals('Doe', $foundPilot->surname);
    }

    public function testCountryRelation()
    {
        $country = new Country(['id' => 2, 'name' => 'USA', 'iso2_code' => 'US']);
        $country->save(false);

        $pilot = new Pilot([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'country_id' => 2,
            'city' => 'New York',
            'location' => 'LEVD',
            'password' => 'SecurePass123!',
            'date_of_birth' => '1990-01-01',
        ]);
        $pilot->save(false);

        $this->assertInstanceOf(Country::class, $pilot->country);
        $this->assertEquals('USA', $pilot->country->name);
    }

    public function testBeforeSave()
    {
        $pilot = new Pilot([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'country_id' => 1,
            'city' => 'New York',
            'location' => 'LEVD',
            'password' => 'SecurePass123!',
            'date_of_birth' => '1990-01-01',
        ]);
        $pilot->save(false);

        $this->assertNotEmpty($pilot->auth_key);
        $this->assertNotEmpty($pilot->access_token);
        $this->assertNotEquals('SecurePass123!', $pilot->password);
        $this->assertTrue(Yii::$app->security->validatePassword('SecurePass123!', $pilot->password));
    }

    public function testSaveWithoutLicense()
    {
        $pilot = new Pilot([
            'name' => 'Jane',
            'surname' => 'Doe',
            'email' => 'jane.doe@example.com',
            'country_id' => 1,
            'city' => 'New York',
            'location' => 'LEVD',
            'password' => 'SecurePass123!',
            'date_of_birth' => '1995-05-10',
        ]);
        $this->assertTrue($pilot->save(false));
    }

    public function testInvalidCountry()
    {
        $pilot = new Pilot([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'country_id' => 999,
            'city' => 'New York',
            'location' => 'LEVD',
            'password' => 'SecurePass123!',
            'date_of_birth' => '1990-01-01',
        ]);
        $this->assertFalse($pilot->validate());
        $this->assertArrayHasKey('country_id', $pilot->getErrors());
    }

    public function testInvalidLocation()
    {
        $pilot = new Pilot([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'country_id' => 1,
            'city' => 'New York',
            'location' => 'INVALID',
            'password' => 'SecurePass123!',
            'date_of_birth' => '1990-01-01',
        ]);
        $this->assertFalse($pilot->validate());
        $this->assertArrayHasKey('location', $pilot->getErrors());
    }

    public function testInvalidDateOfBirth()
    {
        $pilot = new Pilot([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'country_id' => 1,
            'city' => 'New York',
            'location' => 'LEVD',
            'password' => 'SecurePass123!',
            'date_of_birth' => date('Y-m-d', strtotime('tomorrow')),
        ]);
        $this->assertFalse($pilot->validate());
        $this->assertArrayHasKey('date_of_birth', $pilot->getErrors());
    }
}