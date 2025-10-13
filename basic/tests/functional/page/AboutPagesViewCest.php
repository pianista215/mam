<?php

namespace tests\functional\page;

use tests\fixtures\ConfigFixture;
use tests\fixtures\PageContentFixture;
use Yii;

class AboutPagesViewCest
{
    public function _fixtures(){
        return [
            'config' => ConfigFixture::class,
            'pageContent' => PageContentFixture::class
        ];
    }

    public function checkStaffPage(\FunctionalTester $I)
    {
        $I->amOnRoute('page/view', ['code' => 'staff']);
        $I->see('Our staff page test content.');
    }

    public function checkRulesPage(\FunctionalTester $I)
    {
        $I->amOnRoute('page/view', ['code' => 'rules']);
        $I->see('These are the rules page test content.');
    }

    public function checkRanksPage(\FunctionalTester $I)
    {
        $I->amOnRoute('page/view', ['code' => 'ranks']);
        $I->see('Ranks page test content.');
    }

    public function checkSchoolPage(\FunctionalTester $I)
    {
        $I->amOnRoute('page/view', ['code' => 'school']);
        $I->see('School page test content.');
    }

}