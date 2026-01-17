<?php

namespace tests\unit\models;

use app\config\Config;
use app\models\Image;
use app\models\Page;
use app\models\PageContent;
use app\models\Tour;
use tests\unit\BaseUnitTest;
use Yii;

class TourTest extends BaseUnitTest
{

    public function testCreateValidTour()
    {
        $tour = new Tour([
            'name' => 'Test',
            'description' => 'Description',
            'start' => '2020-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertTrue($tour->save());
    }

    public function testTourNotValidWithoutDescription()
    {
        $tour = new Tour([
            'name' => 'Test',
            'description' => '',
            'start' => '2020-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertFalse($tour->save());
        $this->assertArrayHasKey('description', $tour->getErrors());
    }

    public function testTourInvalidDates()
    {
        $tour = new Tour([
            'name' => ' Test ',
            'description' => ' Description ',
            'start' => '2035-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertFalse($tour->save());
        $this->assertArrayHasKey('end', $tour->getErrors());
    }

    public function testTourTrim()
    {
        $tour = new Tour([
            'name' => ' Test ',
            'description' => ' Description ',
            'start' => '2020-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertTrue($tour->save());
        $this->assertEquals('Test', $tour->name);
        $this->assertEquals('Description', $tour->description);
    }

    public function testMaxLengthCode()
    {
        $tour = new Tour([
            'name' => str_repeat('A', 110),
            'description' => str_repeat('A', 210),
            'start' => '2035-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertFalse($tour->save());
        $this->assertArrayHasKey('name', $tour->getErrors());
        $this->assertArrayHasKey('description', $tour->getErrors());
    }

    public function testTourImageIsDeletedOnTourDelete()
    {
        Config::set('images_storage_path', '/tmp');

        $tour = new Tour([
            'name' => 'Test',
            'description' => 'Description',
            'start' => '2020-01-01',
            'end' => '2023-01-01',
        ]);

        $this->assertTrue($tour->save());

        $dir = '/tmp/tour_image';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $source = Yii::getAlias(Image::getPlaceHolder('tour_image'));
        $target = $dir . '/testfile.jpg';

        copy($source, $target);

        $this->assertFileExists($target);

        $image = new Image([
            'type' => 'tour_image',
            'related_id' => $tour->id,
            'filename' => 'testfile.jpg',
        ]);
        $this->assertTrue($image->save());

        $tour->delete();

        $deletedImage = Image::findOne([
            'type' => 'tour_image',
            'related_id' => $tour->id,
        ]);

        $this->assertNull($deletedImage, 'Image must be deleted when tour was deleted');
        $this->assertFileDoesNotExist($target);
    }

    public function testTourPageIsDeletedOnTourDelete()
    {
        $tour = new Tour([
            'name' => 'Test Tour',
            'description' => 'Description',
            'start' => '2020-01-01',
            'end' => '2023-01-01',
        ]);
        $this->assertTrue($tour->save());

        $page = new Page([
            'code' => $tour->getPageCode(),
            'type' => Page::TYPE_TOUR,
        ]);
        $this->assertTrue($page->save());

        $pageContent = new PageContent([
            'page_id' => $page->id,
            'language' => 'en',
            'title' => '',
            'content_md' => 'Test content',
        ]);
        $this->assertTrue($pageContent->save());

        $pageId = $page->id;
        $pageContentId = $pageContent->id;

        $tour->delete();

        $deletedPage = Page::findOne($pageId);
        $this->assertNull($deletedPage, 'Page must be deleted when tour was deleted');

        $deletedPageContent = PageContent::findOne($pageContentId);
        $this->assertNull($deletedPageContent, 'PageContent must be deleted when tour was deleted (cascade)');
    }

}