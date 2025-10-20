<?php

namespace tests\functional\page;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ConfigFixture;
use tests\fixtures\PageContentFixture;
use Yii;

class HidenPagesViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'config' => ConfigFixture::class,
            'pageContent' => PageContentFixture::class
        ];
    }

    public function checkPublicPage(\FunctionalTester $I)
    {
        $I->amOnRoute('page/view', ['code' => 'staff']);
        $I->see('Our staff page test content.');
    }

    public function checkHiddenPageGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('page/view', ['code' => 'hidden']);
        $I->dontSee('Secret content.');
        $I->seeResponseCodeIs(403);
    }

    public function checkHiddenPageLoggedUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('page/view', ['code' => 'hidden']);
        $I->seeResponseCodeIs(200);
        $I->see('Secret content.');
    }



}