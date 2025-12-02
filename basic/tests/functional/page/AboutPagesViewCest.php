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

    public function checkLanguageChangeViaForm(\FunctionalTester $I)
    {
        $I->amOnRoute('/', [], ['HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9']);
        $I->see('This is the home page');
        $I->seeInField('select[name=language]', 'en');

        $I->submitForm('form', ['language' => 'es']);
        $I->see('Esta es la página principal');
        $I->seeInField('select[name=language]', 'es');

        $I->amOnRoute('/', [], ['HTTP_ACCEPT_LANGUAGE' => 'es-ES,es;q=0.9']);
        $I->see('Esta es la página principal');
        $I->seeInField('select[name=language]', 'es');

        $I->submitForm('form', ['language' => 'en']);
        $I->see('This is the home page');
        $I->seeInField('select[name=language]', 'en');
    }

}