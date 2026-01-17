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
            'type' => Page::TYPE_SITE,
        ]);

        $this->assertTrue($page->save());
        $this->assertNotEmpty($page->id);
    }

    public function testCreatePageWithoutRequiredFields()
    {
        $page = new Page(['code' => '', 'type' => '']);
        $this->assertFalse($page->save());
        $this->assertArrayHasKey('code', $page->errors);
        $this->assertArrayHasKey('type', $page->errors);
    }

    public function testCreatePageWithDuplicateCode()
    {
        $existingPage = new Page([
            'code' => 'about',
            'type' => Page::TYPE_SITE,
        ]);
        $existingPage->save();

        $page = new Page([
            'code' => 'about',
            'type' => Page::TYPE_SITE,
        ]);

        $this->assertFalse($page->save());
        $this->assertArrayHasKey('code', $page->errors);
    }

    public function testTypeValidation()
    {
        $page = new Page([
            'code' => 'contact',
            'type' => 'invalid_type',
        ]);

        $this->assertFalse($page->save());
        $this->assertArrayHasKey('type', $page->errors);
    }

    public function testValidTypes()
    {
        foreach ([Page::TYPE_SITE, Page::TYPE_COMPONENT, Page::TYPE_TOUR] as $type) {
            $page = new Page([
                'code' => 'test_' . $type,
                'type' => $type,
            ]);

            $this->assertTrue($page->save(), "Page with type $type should be valid");
        }
    }

    public function testCodeMaxLengthValidation()
    {
        $page = new Page([
            'code' => str_repeat('a', 51),
            'type' => Page::TYPE_SITE,
        ]);

        $this->assertFalse($page->save());
        $this->assertArrayHasKey('code', $page->errors);
    }

    public function testPageImageIsDeletedOnPageTypeDelete()
    {
        Config::set('images_storage_path', '/tmp');

        $page = new Page([
            'code' => 'home',
            'type' => Page::TYPE_SITE,
        ]);

        $this->assertTrue($page->save());

        $dir = '/tmp/page_image';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Page doesn't have placeholder or size limits, so use any of the images for testing
        $source = Yii::getAlias(Image::getPlaceHolder(Image::TYPE_AIRCRAFT_TYPE_IMAGE));
        $target = $dir . '/testfile.jpg';

        copy($source, $target);

        $this->assertFileExists($target);

        $image = new Image([
            'type' => Image::TYPE_PAGE_IMAGE,
            'related_id' => $page->id,
            'filename' => 'testfile.jpg',
        ]);
        $this->assertTrue($image->save());

        $page->delete();

        $deletedImage = Image::findOne([
            'type' => Image::TYPE_PAGE_IMAGE,
            'related_id' => $page->id,
        ]);

        $this->assertNull($deletedImage, 'Image must be deleted when page was deleted');
        $this->assertFileDoesNotExist($target);
    }
}
