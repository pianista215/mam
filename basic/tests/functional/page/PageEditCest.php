<?php

namespace tests\functional\page;

use app\models\Image;
use app\models\Page;
use app\models\Tour;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ImageFixture;
use tests\fixtures\PageContentFixture;
use tests\fixtures\TourFixture;

class PageEditCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'image' => ImageFixture::class,
            'pageContent' => PageContentFixture::class,
            'tour' => TourFixture::class,
        ];
    }

    // Site pages

    public function editSitePageAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // Admin
        $I->amOnRoute('page/edit', ['code' => 'staff', 'language' => 'en', 'type' => Page::TYPE_SITE]);
        $I->seeResponseCodeIs(200);
        $I->see('Editing');
        $I->see('Page');
        $I->seeElement('input[name="PageContent[title]"]');

        $I->fillField('input[name="PageContent[title]"]', 'Updated Staff Title');
        $I->fillField('textarea[name="PageContent[content_md]"]', 'Updated staff content with **bold** text.');

        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('/page/staff');
        $I->see('Updated staff content with');
        $I->see('bold');
    }

    public function editSitePageAsUserForbidden(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1); // Regular user
        $I->amOnRoute('page/edit', ['code' => 'staff', 'language' => 'en', 'type' => Page::TYPE_SITE]);
        $I->seeResponseCodeIs(403);
    }

    public function editSitePageAsGuestRedirectsToLogin(\FunctionalTester $I)
    {
        $I->amOnRoute('page/edit', ['code' => 'staff', 'language' => 'en', 'type' => Page::TYPE_SITE]);
        $I->seeCurrentUrlMatches('~login~');
    }

    // Component pages

    public function editComponentPageAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // Admin
        $I->amOnRoute('page/edit', ['code' => 'registration_closed', 'language' => 'en', 'type' => Page::TYPE_COMPONENT]);
        $I->seeResponseCodeIs(200);
        $I->see('Editing');
        $I->see('Component');
        $I->dontSeeElement('input[name="PageContent[title]"]');

        $I->fillField('textarea[name="PageContent[content_md]"]', 'Updated registration closed content.');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('/page/registration_closed');
        $I->see('Updated registration closed content.');
    }

    public function editComponentPageAsUserForbidden(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1); // Regular user
        $I->amOnRoute('page/edit', ['code' => 'registration_closed', 'language' => 'en', 'type' => Page::TYPE_COMPONENT]);
        $I->seeResponseCodeIs(403);
    }

    // Tour pages

    public function editTourPageAsTourManager(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10); // Tour manager

        $tour = Tour::findOne(1);
        $pageCode = $tour->getPageCode();

        $I->amOnRoute('page/edit', ['code' => $pageCode, 'language' => 'en', 'type' => Page::TYPE_TOUR]);
        $I->seeResponseCodeIs(200);
        $I->see('Editing');
        $I->see('Tour page');
        $I->see($tour->name);
        $I->dontSeeElement('input[name="PageContent[title]"]');

        $I->fillField('textarea[name="PageContent[content_md]"]', 'Updated tour content with *italic* text.');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('/tour/view?id=' . $tour->id);
        $I->see('Updated tour content with');
        $I->see('italic');
    }

    public function editTourPageAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // Admin

        $tour = Tour::findOne(1);
        $pageCode = $tour->getPageCode();

        $I->amOnRoute('page/edit', ['code' => $pageCode, 'language' => 'en', 'type' => Page::TYPE_TOUR]);
        $I->seeResponseCodeIs(200);
        $I->see('Editing');
    }

    public function editTourPageAsUserForbidden(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1); // Regular user

        $tour = Tour::findOne(1);
        $pageCode = $tour->getPageCode();

        $I->amOnRoute('page/edit', ['code' => $pageCode, 'language' => 'en', 'type' => Page::TYPE_TOUR]);
        $I->seeResponseCodeIs(403);
    }

    public function editNonExistentTourPageReturns404(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // Admin
        $I->amOnRoute('page/edit', ['code' => 'tour_content_99999', 'language' => 'en', 'type' => Page::TYPE_TOUR]);
        $I->seeResponseCodeIs(404);
    }

    public function editNonExistentSitePageReturns404(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // Admin
        $I->amOnRoute('page/edit', ['code' => 'nonexistent_page', 'language' => 'en', 'type' => Page::TYPE_SITE]);
        $I->seeResponseCodeIs(404);
    }

    // XSS Protection

    public function javascriptInMarkdownIsStripped(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // Admin
        $I->amOnRoute('page/edit', ['code' => 'staff', 'language' => 'en', 'type' => Page::TYPE_SITE]);
        $I->seeResponseCodeIs(200);

        $maliciousContent = 'Normal text <script>alert("XSS")</script> and more text.';

        $I->fillField('input[name="PageContent[title]"]', 'Test XSS');
        $I->fillField('textarea[name="PageContent[content_md]"]', $maliciousContent);
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('/page/staff');
        $I->see('Normal text');
        $I->see('and more text.');
        $I->dontSee('<script>');
        $I->dontSee('alert("XSS")');
        $I->dontSeeInSource('<script>alert("XSS")</script>');
    }

    public function javascriptEventHandlersAreStripped(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // Admin
        $I->amOnRoute('page/edit', ['code' => 'staff', 'language' => 'en', 'type' => Page::TYPE_SITE]);
        $I->seeResponseCodeIs(200);

        $maliciousContent = 'Click <a href="#" onclick="alert(1)">here</a> for info.';

        $I->fillField('input[name="PageContent[title]"]', 'Test Event Handlers');
        $I->fillField('textarea[name="PageContent[content_md]"]', $maliciousContent);
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('/page/staff');
        $I->see('Click');
        $I->see('for info.');
        $I->dontSeeInSource('onclick');
        $I->dontSeeInSource('alert(1)');
    }

    // Image carousel

    public function editPageShowsImagesSectionWithoutImages(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // Admin
        $I->amOnRoute('page/edit', ['code' => 'staff', 'language' => 'en', 'type' => Page::TYPE_SITE]);
        $I->seeResponseCodeIs(200);

        $I->see('Page Images');
        $I->see('Add Image');
        $I->seeElement('a[href*="image/upload"][href*="type=page_image"][href*="element=0"]');
    }

    public function editPageShowsExistingImages(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10); // Tour Manager

        $tour = Tour::findOne(1);
        $pageCode = $tour->getPageCode();

        $I->amOnRoute('page/edit', ['code' => $pageCode, 'language' => 'en', 'type' => Page::TYPE_TOUR]);
        $I->seeResponseCodeIs(200);

        $I->see('Page Images');
        // Page id=7 has one image (element=0), so next element should be 1
        $I->seeElement('a[href*="image/upload"][href*="type=page_image"][href*="element=1"]');
        // Should have copy URL button
        $I->seeElement('.copy-url-btn');
        // Should have edit button
        $I->seeElement('a[href*="image/upload"][href*="element=0"]');
    }

    public function editPageAddImageLinkHasCorrectNextElement(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // Admin

        // Page id=6 (registration_closed) has one image at element=0
        $I->amOnRoute('page/edit', ['code' => 'registration_closed', 'language' => 'en', 'type' => Page::TYPE_COMPONENT]);
        $I->seeResponseCodeIs(200);

        $I->see('Page Images');
        // Next element should be 1
        $I->seeElement('a[href*="image/upload"][href*="related_id=6"][href*="element=1"]');
    }
}
