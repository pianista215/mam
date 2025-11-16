<?php

namespace tests\unit\models;

use app\config\Config;
use app\models\Image;
use app\models\Page;
use app\models\PageContent;
use tests\unit\BaseUnitTest;
use Yii;

class PageTest extends BaseUnitTest
{
    public function testCreateValidPage()
    {
        $page = new Page([
            'code' => 'home',
            'public' => 1,
        ]);

        $this->assertTrue($page->save());
        $this->assertNotEmpty($page->id);
    }

    public function testCreatePageWithoutRequiredFields()
    {
        $page = new Page(['code' => '']);
        $this->assertFalse($page->save());
        $this->assertArrayHasKey('code', $page->errors);
    }

    public function testCreatePageWithDuplicateCode()
    {
        $existingPage = new Page([
            'code' => 'about',
        ]);
        $existingPage->save();

        $page = new Page([
            'code' => 'about',
        ]);

        $this->assertFalse($page->save());
        $this->assertArrayHasKey('code', $page->errors);
    }

    public function testPublicDefaultsToZero()
    {
        $page = new Page([
            'code' => 'contact',
        ]);

        $this->assertTrue($page->save());
        $this->assertEquals(0, $page->public);
    }

    public function testPublicAcceptsBoolean()
    {
        $page = new Page([
            'code' => 'services',
            'public' => true,
        ]);

        $this->assertTrue($page->save());
        $this->assertEquals(1, $page->public);
    }

    public function testCodeMaxLengthValidation()
    {
        $page = new Page([
            'code' => str_repeat('a', 51),
        ]);

        $this->assertFalse($page->save());
        $this->assertArrayHasKey('code', $page->errors);
    }

    public function testPageImageIsDeletedOnPageTypeDelete()
    {
        Config::set('images_storage_path', '/tmp');

        $page = new Page([
            'code' => 'home',
            'public' => 1,
        ]);

        $this->assertTrue($page->save());

        $dir = '/tmp/page_image';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Page doesn't have placeholder or size limits, so use any of the images for testing
        $source = Yii::getAlias(Image::getPlaceHolder('aircraftType_image'));
        $target = $dir . '/testfile.jpg';

        copy($source, $target);

        $this->assertFileExists($target);

        $image = new Image([
            'type' => 'page_image',
            'related_id' => $page->id,
            'filename' => 'testfile.jpg',
        ]);
        $this->assertTrue($image->save());

        $page->delete();

        $deletedImage = Image::findOne([
            'type' => 'page_image',
            'related_id' => $page->id,
        ]);

        $this->assertNull($deletedImage, 'Image must be deleted when page was deleted');
        $this->assertFileDoesNotExist($target);
    }
}
