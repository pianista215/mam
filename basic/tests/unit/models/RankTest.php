<?php

namespace tests\unit\models;

use app\config\Config;
use app\models\Image;
use app\models\Rank;
use tests\unit\BaseUnitTest;
use Yii;

class RankTest extends BaseUnitTest
{
    public function testCreateValidRank()
    {
        $rank = new Rank([
            'name' => 'Captain',
            'position' => 1,
        ]);

        $this->assertTrue($rank->save());
        $this->assertNotEmpty($rank->id);
    }

    public function testCreateRankWithoutRequiredFields()
    {
        $rank = new Rank([
            'name' => '',
            'position' => null,
        ]);

        $this->assertFalse($rank->save());
        $this->assertArrayHasKey('name', $rank->errors);
        $this->assertArrayHasKey('position', $rank->errors);
    }

    public function testCreateRankWithNonIntegerPosition()
    {
        $rank = new Rank([
            'name' => 'Invalid Rank',
            'position' => 'not-integer',
        ]);

        $this->assertFalse($rank->save());
        $this->assertArrayHasKey('position', $rank->errors);
    }

    public function testCreateRankWithDuplicateName()
    {
        $existingRank = new Rank([
            'name' => 'First Officer',
            'position' => 2,
        ]);
        $existingRank->save();

        $rank = new Rank([
            'name' => 'First Officer',
            'position' => 3,
        ]);

        $this->assertFalse($rank->save());
        $this->assertArrayHasKey('name', $rank->errors);
    }

    public function testCreateRankWithDuplicatePosition()
    {
        $existingRank = new Rank([
            'name' => 'Senior Captain',
            'position' => 4,
        ]);
        $existingRank->save();

        $rank = new Rank([
            'name' => 'Junior Captain',
            'position' => 4,
        ]);

        $this->assertFalse($rank->save());
        $this->assertArrayHasKey('position', $rank->errors);
    }

    public function testRankIconIsDeletedOnRankDelete()
    {
        Config::set('images_storage_path', '/tmp');

        $rank = new Rank([
            'name' => 'Captain',
            'position' => 1,
        ]);

        $this->assertTrue($rank->save());

        $dir = '/tmp/rank_icon';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $source = Yii::getAlias(Image::getPlaceHolder('rank_icon'));
        $target = $dir . '/testfile.jpg';

        copy($source, $target);

        $this->assertFileExists($target);

        $image = new Image([
            'type' => 'rank_icon',
            'related_id' => $rank->id,
            'filename' => 'testfile.jpg',
        ]);
        $this->assertTrue($image->save());

        $rank->delete();

        $deletedImage = Image::findOne([
            'type' => 'rank_icon',
            'related_id' => $rank->id,
        ]);

        $this->assertNull($deletedImage, 'Image must be deleted when rank was deleted');
        $this->assertFileDoesNotExist($target);
    }
}
