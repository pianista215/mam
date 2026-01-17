<?php

namespace tests\unit\models;

use app\config\Config;
use app\models\AircraftType;
use app\models\Airport;
use app\models\Country;
use app\models\Image;
use app\models\Pilot;
use app\models\Rank;
use app\models\Tour;
use tests\unit\BaseUnitTest;
use Yii;

class ImageTest extends BaseUnitTest
{
    private string $basePath;

    protected Pilot $pilot;
    protected Rank $rank;
    protected Tour $tour;
    protected Country $country;
    protected AircraftType $aircraftType;

    protected function _before()
    {
        parent::_before();

        $this->country = new Country([
            'name' => 'Spain',
            'iso2_code' => 'ES',
        ]);
        $this->assertTrue($this->country->save(), print_r($this->country->errors, true));

        $airport = new Airport([
            'icao_code' => 'LEMD',
            'name' => 'Madrid-Barajas',
            'latitude' => 40.471926,
            'longitude' => -3.56264,
            'city' => 'Madrid',
            'country_id' => $this->country->id,
        ]);
        $this->assertTrue($airport->save(), print_r($airport->errors, true));

        $this->pilot = new Pilot([
            'license' => 'LIC1',
            'name' => 'A',
            'surname' => 'B',
            'email' => 'a@b.com',
            'location' => 'LEMD',
            'date_of_birth' => '1990-01-01',
            'city' => 'Madrid',
            'password' => Yii::$app->security->generatePasswordHash('Test123!'),
            'country_id' => $this->country->id,
        ]);
        $this->assertTrue($this->pilot->save(), print_r($this->pilot->errors, true));

        $this->rank = new Rank([
            'name' => 'Test Rank',
            'position' => 1,
        ]);
        $this->assertTrue($this->rank->save(), print_r($this->rank->errors, true));

        $this->tour = new Tour([
            'name' => 'Tour 1',
            'description' => 'x',
            'start' => '2020-01-01',
            'end' => '2030-01-01',
        ]);
        $this->assertTrue($this->tour->save(), print_r($this->tour->errors, true));

        $this->aircraftType = new AircraftType([
            'icao_type_code' => 'A320',
            'name' => 'A320',
            'max_nm_range' => 2000,
        ]);
        $this->assertTrue($this->aircraftType->save(), print_r($this->aircraftType->errors, true));

        $this->basePath = '/tmp/image-tests';
        Config::set('images_storage_path', $this->basePath);

        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }
    }

    private function prepareImage(string $type, string $filename, ?callable $manipulator = null)
    {
        $dir = $this->basePath . '/' . $type;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $source = Yii::getAlias(Image::getPlaceholder($type));
        $target = $dir . '/' . $filename;

        copy($source, $target);

        if ($manipulator) {
            $manipulator($target);
        }

        return $target;
    }

    private function getRelatedModel(string $type)
    {
        return match ($type) {
            Image::TYPE_PILOT_PROFILE      => $this->pilot,
            Image::TYPE_RANK_ICON          => $this->rank,
            Image::TYPE_TOUR_IMAGE         => $this->tour,
            Image::TYPE_COUNTRY_ICON       => $this->country,
            Image::TYPE_AIRCRAFT_TYPE_IMAGE => $this->aircraftType,
            default                        => null,
        };
    }

    public function testValidImageIsSaved()
    {
        $type = Image::TYPE_COUNTRY_ICON;
        $related = $this->getRelatedModel($type);
        $this->assertNotNull($related);

        $path = $this->prepareImage($type, 'ok.png');

        $image = new Image([
            'type' => $type,
            'related_id' => $related->id,
            'filename' => 'ok.png',
            'element' => 0
        ]);

        $this->assertTrue($image->save(), 'Image must be saved because it is valid');
    }

    public function testInvalidResolutionFails()
    {
        $type = Image::TYPE_COUNTRY_ICON;
        $related = $this->getRelatedModel($type);
        $this->assertNotNull($related);

        $path = $this->prepareImage($type, 'badres.png', function ($target) {
            $img = imagecreatetruecolor(200, 200);
            imagepng($img, $target);
            imagedestroy($img);
        });

        $image = new Image([
            'type' => $type,
            'related_id' => $related->id,
            'filename' => 'badres.png',
        ]);

        $this->assertFalse($image->save(), 'Image with wrong resolution should fail');
        $this->assertArrayHasKey('filename', $image->errors);
    }

    public function testInvalidExtensionFails()
    {
        $type = Image::TYPE_COUNTRY_ICON;
        $related = $this->getRelatedModel($type);
        $this->assertNotNull($related);

        $path = $this->prepareImage($type, 'test.txt', function ($target) {
            file_put_contents($target, "not an image");
        });

        $image = new Image([
            'type' => $type,
            'related_id' => $related->id,
            'filename' => 'test.txt',
        ]);

        $this->assertFalse($image->save());
        $this->assertArrayHasKey('filename', $image->errors);
    }

    public function testInvalidMimeFails()
    {
        $type = Image::TYPE_COUNTRY_ICON;
        $related = $this->getRelatedModel($type);
        $this->assertNotNull($related);

        $path = $this->prepareImage($type, 'badmime.png', function ($target) {
            file_put_contents($target, "<html></html>");
        });

        $image = new Image([
            'type' => $type,
            'related_id' => $related->id,
            'filename' => 'badmime.png',
        ]);

        $this->assertFalse($image->save());
        $this->assertArrayHasKey('filename', $image->errors);
    }

    public function testRelatedModelDoesNotExist()
    {
        $type = Image::TYPE_COUNTRY_ICON;

        $this->prepareImage($type, 'ok.png');

        $image = new Image([
            'type' => $type,
            'related_id' => 999999,
            'filename' => 'ok.png'
        ]);

        $this->assertFalse($image->save());
        $this->assertArrayHasKey('related_id', $image->errors);
    }

    public function testInvalidTypeFails()
    {
        $image = new Image([
            'type' => 'not_a_valid_type',
            'related_id' => 1,
            'filename' => 'something.png'
        ]);

        $this->assertFalse($image->save());
        $this->assertArrayHasKey('type', $image->errors);
    }

    public function testElementMustBeZeroForNonPageTypes()
    {
        $type = Image::TYPE_PILOT_PROFILE;
        $related = $this->getRelatedModel($type);

        $this->prepareImage($type, 'ok.png');

        $image = new Image([
            'type' => $type,
            'related_id' => $related->id,
            'filename' => 'ok.png',
            'element' => 5
        ]);

        $this->assertFalse($image->save());
        $this->assertArrayHasKey('element', $image->errors);
    }

    public function testUniqueConstraintOnTypeRelatedElement()
    {
        $type = Image::TYPE_COUNTRY_ICON;
        $related = $this->getRelatedModel($type);

        $this->prepareImage($type, 'img1.png');

        $img1 = new Image([
            'type' => $type,
            'related_id' => $related->id,
            'filename' => 'img1.png',
            'element' => 0
        ]);
        $this->assertTrue($img1->save());

        $this->prepareImage($type, 'img2.png');

        $img2 = new Image([
            'type' => $type,
            'related_id' => $related->id,
            'filename' => 'img2.png',
            'element' => 0
        ]);

        $this->assertFalse($img2->save(), 'Duplicate type+related_id+element should not be allowed');
        $this->assertArrayHasKey('type', $img2->errors);
    }

    public function testAfterDeleteRemovesFile()
    {
        $type = Image::TYPE_COUNTRY_ICON;
        $related = $this->getRelatedModel($type);

        $path = $this->prepareImage($type, 'deleteme.png');

        $image = new Image([
            'type' => $type,
            'related_id' => $related->id,
            'filename' => 'deleteme.png'
        ]);

        $this->assertTrue($image->save());
        $this->assertFileExists($path);

        $image->delete();

        $this->assertFileDoesNotExist($path, 'File should be removed by afterDelete()');
        $this->assertNull(Image::findOne($image->id));
    }
}
