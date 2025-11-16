<?php

namespace tests\unit\models;

use app\models\Page;
use app\models\PageContent;
use tests\unit\BaseUnitTest;
use Yii;

class PageContentTest extends BaseUnitTest
{
    public function testCreateValidPageContent()
    {
        $page = new Page(['code' => 'home']);
        $this->assertTrue($page->save());

        $content = new PageContent([
            'page_id' => $page->id,
            'language' => 'en',
            'title' => 'Welcome',
            'content_md' => 'Hello **world**!',
        ]);

        $this->assertTrue($content->save());
        $this->assertNotEmpty($content->id);
    }

    public function testCreatePageContentWithoutRequiredFields()
    {
        $content = new PageContent([
            'page_id' => null,
            'language' => '',
            'title' => '',
            'content_md' => '',
        ]);

        $this->assertFalse($content->save());
        $this->assertArrayHasKey('page_id', $content->errors);
        $this->assertArrayHasKey('language', $content->errors);
        $this->assertArrayHasKey('title', $content->errors);
        $this->assertArrayHasKey('content_md', $content->errors);
    }

    public function testPageIdMustExist()
    {
        $content = new PageContent([
            'page_id' => 9999,
            'language' => 'en',
            'title' => 'Title',
            'content_md' => 'Some content',
        ]);

        $this->assertFalse($content->save());
        $this->assertArrayHasKey('page_id', $content->errors);
    }

    public function testUniquePageIdLanguageConstraint()
    {
        $page = new Page(['code' => 'about']);
        $this->assertTrue($page->save());

        $content1 = new PageContent([
            'page_id' => $page->id,
            'language' => 'en',
            'title' => 'About Us',
            'content_md' => 'Content 1',
        ]);
        $this->assertTrue($content1->save());

        $content2 = new PageContent([
            'page_id' => $page->id,
            'language' => 'en',
            'title' => 'About Us Duplicate',
            'content_md' => 'Content 2',
        ]);
        $this->assertFalse($content2->save());
        $this->assertArrayHasKey('page_id', $content2->errors);
    }

    public function testLanguageMaxLengthValidation()
    {
        $page = new Page(['code' => 'services']);
        $this->assertTrue($page->save());

        $content = new PageContent([
            'page_id' => $page->id,
            'language' => 'eng',
            'title' => 'Services',
            'content_md' => 'Content',
        ]);

        $this->assertFalse($content->save());
        $this->assertArrayHasKey('language', $content->errors);
    }

    public function testTitleMaxLengthValidation()
    {
        $page = new Page(['code' => 'contact']);
        $this->assertTrue($page->save());

        $content = new PageContent([
            'page_id' => $page->id,
            'language' => 'es',
            'title' => str_repeat('a', 101),
            'content_md' => 'Contenido',
        ]);

        $this->assertFalse($content->save());
        $this->assertArrayHasKey('title', $content->errors);
    }

    public function testGetPageRelation()
    {
        $page = new Page(['code' => 'blog']);
        $this->assertTrue($page->save());

        $content = new PageContent([
            'page_id' => $page->id,
            'language' => 'en',
            'title' => 'Blog Post',
            'content_md' => 'Lorem ipsum',
        ]);
        $this->assertTrue($content->save());

        $this->assertEquals($page->id, $content->page->id);
        $this->assertEquals($page->code, $content->page->code);
    }
}
