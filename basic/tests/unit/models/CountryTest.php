<?php

namespace tests\unit\models;

use app\config\Config;
use app\models\Country;
use app\models\Image;
use tests\unit\BaseUnitTest;
use Yii;

class CountryTest extends BaseUnitTest
{
    public function testCreateValidCountry()
    {
        $country = new Country([
            'name' => 'Spain',
            'iso2_code' => 'ES',
        ]);

        $this->assertTrue($country->save());
        $this->assertNotEmpty($country->id);
    }

    public function testCreateCountryWithoutRequiredFields()
    {
        $country = new Country(['name' => '']);
        $this->assertFalse($country->save());
        $this->assertArrayHasKey('name', $country->errors);
        $this->assertArrayHasKey('iso2_code', $country->errors);
    }

    public function testCreateCountryWithInvalidISO2CodeLength()
    {
        $country = new Country([
            'name' => 'Invalid Country',
            'iso2_code' => 'ABC',
        ]);

        $this->assertFalse($country->save());
        $this->assertArrayHasKey('iso2_code', $country->errors);
    }

    public function testCreateCountryWithDuplicateISO2Code()
    {
        $existingCountry = new Country([
            'name' => 'Country 1',
            'iso2_code' => 'US',
        ]);
        $existingCountry->save();

        $country = new Country([
            'name' => 'Country 2',
            'iso2_code' => 'US',
        ]);

        $this->assertFalse($country->save());
        $this->assertArrayHasKey('iso2_code', $country->errors);
    }

    public function testTrimToUpperCountry()
    {
        $country = new Country([
            'name' => '   Spain   ',
            'iso2_code' => 'es',
        ]);

        $this->assertTrue($country->save());
        $this->assertEquals($country->name, 'Spain');
        $this->assertEquals($country->iso2_code, 'ES');
    }

    public function testCountryIconIsDeletedOnCountryDelete()
    {
        Config::set('images_storage_path', '/tmp');

        $country = new Country([
            'name' => 'Spain',
            'iso2_code' => 'ES',
        ]);

        $this->assertTrue($country->save());

        $dir = '/tmp/country_icon';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $source = Yii::getAlias(Image::getPlaceHolder('country_icon'));
        $target = $dir . '/testfile.jpg';

        copy($source, $target);

        $this->assertFileExists($target);

        $image = new Image([
            'type' => 'country_icon',
            'related_id' => $country->id,
            'filename' => 'testfile.jpg',
        ]);
        $this->assertTrue($image->save());

        $country->delete();

        $deletedImage = Image::findOne([
            'type' => 'country_icon',
            'related_id' => $country->id,
        ]);

        $this->assertNull($deletedImage, 'Image must be deleted when country was deleted');
        $this->assertFileDoesNotExist($target);
    }
}
