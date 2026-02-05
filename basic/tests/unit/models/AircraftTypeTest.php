<?php

namespace tests\unit\models;

use app\config\Config;
use app\models\AircraftType;
use app\models\Image;
use tests\unit\BaseUnitTest;
use Yii;

class AircraftTypeTest extends BaseUnitTest
{
    public function testCreateValidAircraftType()
    {
        $aircraftType = new AircraftType([
            'icao_type_code' => 'B738',
            'name' => 'Boeing 737-800',
            'max_nm_range' => 2900,
        ]);

        $this->assertTrue($aircraftType->save());
        $this->assertNotEmpty($aircraftType->id);
    }

    public function testTrimAndToUpperAircraftType()
    {
        $aircraftType = new AircraftType([
            'icao_type_code' => ' b7 3 8',
            'name' => '   Boeing 737-800   ',
            'max_nm_range' => 2900,
        ]);

        $this->assertTrue($aircraftType->save());
        $this->assertEquals($aircraftType->icao_type_code, 'B738');
        $this->assertEquals($aircraftType->name, 'Boeing 737-800');
    }

    public function testCreateAircraftTypeWithoutRequiredFields()
    {
        $aircraftType = new AircraftType([]);
        $this->assertFalse($aircraftType->save());
        $this->assertArrayHasKey('icao_type_code', $aircraftType->errors);
        $this->assertArrayHasKey('name', $aircraftType->errors);
        $this->assertArrayHasKey('max_nm_range', $aircraftType->errors);
    }

    public function testCreateAircraftTypeWithShortIcaoTypeCode()
    {
        $aircraftType = new AircraftType([
            'icao_type_code' => 'DC3',
            'name' => 'Douglas DC-3',
            'max_nm_range' => 1300,
        ]);

        $this->assertTrue($aircraftType->save());
        $this->assertEquals('DC3', $aircraftType->icao_type_code);
    }

    public function testCreateAircraftTypeWithInvalidIcaoTypeCodeLength()
    {
        $tooLong = new AircraftType([
            'icao_type_code' => 'ABCDE',
            'name' => 'Invalid Aircraft',
            'max_nm_range' => 1500,
        ]);

        $this->assertFalse($tooLong->save());
        $this->assertArrayHasKey('icao_type_code', $tooLong->errors);

        $tooShort = new AircraftType([
            'icao_type_code' => 'A',
            'name' => 'Invalid Aircraft',
            'max_nm_range' => 1500,
        ]);

        $this->assertFalse($tooShort->save());
        $this->assertArrayHasKey('icao_type_code', $tooShort->errors);
    }

    public function testCreateAircraftTypeWithDuplicateIcaoTypeCode()
    {
        $aircraftType1 = new AircraftType([
            'icao_type_code' => 'A320',
            'name' => 'Airbus A320',
            'max_nm_range' => 3100,
        ]);
        $aircraftType1->save();

        $aircraftType2 = new AircraftType([
            'icao_type_code' => 'A320',
            'name' => 'Duplicate Airbus A320',
            'max_nm_range' => 3200,
        ]);

        $this->assertFalse($aircraftType2->save());
        $this->assertArrayHasKey('icao_type_code', $aircraftType2->errors);
    }

    public function testAircraftTypeImageIsDeletedOnAircrafTypeDelete()
    {
        Config::set('images_storage_path', '/tmp');

        $aircraftType = new AircraftType([
            'icao_type_code' => 'B738',
            'name' => 'Boeing 737-800',
            'max_nm_range' => 2900,
        ]);

        $this->assertTrue($aircraftType->save());

        $dir = '/tmp/aircraftType_image';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $source = Yii::getAlias(Image::getPlaceHolder('aircraftType_image'));
        $target = $dir . '/testfile.jpg';

        copy($source, $target);

        $this->assertFileExists($target);

        $image = new Image([
            'type' => 'aircraftType_image',
            'related_id' => $aircraftType->id,
            'filename' => 'testfile.jpg',
        ]);
        $this->assertTrue($image->save());

        $aircraftType->delete();

        $deletedImage = Image::findOne([
            'type' => 'aircraftType_image',
            'related_id' => $aircraftType->id,
        ]);

        $this->assertNull($deletedImage, 'Image must be deleted when aircraftType was deleted');
        $this->assertFileDoesNotExist($target);
    }
}