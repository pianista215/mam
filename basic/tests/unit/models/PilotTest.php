<?php

namespace tests\unit\models;

use app\config\ConfigHelper as CK;
use app\models\Airport;
use app\models\Country;
use app\models\Image;
use app\models\Pilot;
use tests\unit\BaseUnitTest;
use Yii;

class PilotTest extends BaseUnitTest
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

        $this->assertFalse($pilot->save());

        $pilot->name = 'John';
        $pilot->surname = 'Doe';
        $pilot->email = 'john.doe@example.com';
        $pilot->country_id = 1;
        $pilot->city = 'New York';
        $pilot->location = 'LEVD';
        $pilot->password = 'SecurePass123!';
        $pilot->date_of_birth = '1990-01-01';
        $this->assertTrue(
            $pilot->save(),
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
        $this->assertFalse($pilot->save());
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
        $this->assertFalse($pilot->save());
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
        $this->assertFalse($pilot->save());
        $this->assertArrayHasKey('date_of_birth', $pilot->getErrors());

        $pilot->date_of_birth = '10/02/1980';
        $this->assertFalse($pilot->save());
        $this->assertArrayHasKey('date_of_birth', $pilot->getErrors());
    }

    public function testPasswordValidation()
    {
        $pilot = new Pilot([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'country_id' => 1,
            'city' => 'New York',
            'location' => 'LEVD',
            'date_of_birth' => '1990-01-01',
        ]);

        $pilot->password = 'short';
        $this->assertFalse($pilot->save(), 'Password should fail because it is less than 8 characters.');

        $pilot->password = 'password123';
        $this->assertTrue($pilot->save(), 'Password should pass because it has more than 8 characters and includes both letters and numbers.'. json_encode($pilot->getErrors()));

        $pilot->password = 'password';
        $this->assertFalse($pilot->save(), 'Password should fail because it does not contain a number.');

        $pilot->password = '12345678';
        $this->assertFalse($pilot->save(), 'Password should fail because it does not contain a letter.');
    }

    public function testTrimToUpper()
    {
        $pilot = new Pilot([
            'name' => '  John  ',
            'surname' => '  Doe  ',
            'email' => '  john.doe@example.com  ',
            'country_id' => 1,
            'city' => '  New York   ',
            'location' => 'LEVD',
            'password' => 'SecurePass123!',
            'license' => ' l i c 1 2 3',
            'date_of_birth' => '1990-01-01',
        ]);
        $this->assertTrue($pilot->save());
        $this->assertEquals($pilot->name, 'John');
        $this->assertEquals($pilot->surname, 'Doe');
        $this->assertEquals($pilot->email, 'john.doe@example.com');
        $this->assertEquals($pilot->city, 'New York');
        $this->assertEquals($pilot->license, 'LIC123');
    }

    public function testIsPasswordResetTokenExpired()
    {
        $pilot = new Pilot();

        $pilot->pwd_reset_token_created_at = null;
        $this->assertTrue($pilot->isPasswordResetTokenExpired());

        $pilot->pwd_reset_token_created_at = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $this->assertFalse($pilot->isPasswordResetTokenExpired());

        $hours = CK::getTokenLifeH() ?? 24;
        $pilot->pwd_reset_token_created_at = date('Y-m-d H:i:s', strtotime('-'.($hours + 1).' hours'));
        $this->assertTrue($pilot->isPasswordResetTokenExpired());

        $pilot->pwd_reset_token_created_at = 'invalid-date';
        $this->assertTrue($pilot->isPasswordResetTokenExpired());
    }

    public function testPilotImageIsDeletedOnPilotDelete()
    {
        Config::set('images_storage_path', '/tmp');

        $pilot = new Pilot([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'country_id' => 1,
            'city' => 'Madrid',
            'location' => 'LEVD',
            'password' => 'SecurePass123!',
            'license' => 'LIC999',
            'date_of_birth' => '1980-01-01',
        ]);

        $this->assertTrue($pilot->save());

        $dir = '/tmp/pilot_profile';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $source = Yii::getAlias(Image::getPlaceHolder('pilot_profile'));
        $target = $dir . '/testfile.jpg';

        copy($source, $target);

        $this->assertFileExists($target);

        $image = new Image([
            'type' => 'pilot_profile',
            'related_id' => $pilot->id,
            'filename' => 'testfile.jpg',
        ]);
        $this->assertTrue($image->save());

        $pilot->delete();

        $deletedImage = Image::findOne([
            'type' => 'pilot_profile',
            'related_id' => $pilot->id,
        ]);

        $this->assertNull($deletedImage, 'Image must be deleted when pilot was deleted');
        $this->assertFileDoesNotExist($target);
    }


}