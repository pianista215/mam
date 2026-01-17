<?php

namespace tests\functional\page;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ConfigFixture;
use tests\fixtures\PageContentFixture;

class HiddenPagesViewCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'config' => ConfigFixture::class,
            'pageContent' => PageContentFixture::class
        ];
    }

    public function checkSitePageVisibleToGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('page/view', ['code' => 'staff']);
        $I->seeResponseCodeIs(200);
        $I->see('Our staff page test content.');
    }

    public function checkComponentPageForbiddenForGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('page/view', ['code' => 'registration_closed']);
        $I->seeResponseCodeIs(403);
        $I->dontSee('Registration closed content.');
    }

    public function checkComponentPageVisibleToLoggedUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('page/view', ['code' => 'registration_closed']);
        $I->seeResponseCodeIs(200);
        $I->see('Registration closed content.');
    }
}
