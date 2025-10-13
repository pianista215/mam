<?php

namespace tests\functional\main;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ConfigFixture;
use tests\fixtures\PageContentFixture;
use Yii;

class MainViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'config' => ConfigFixture::class,
            'pageContent' => PageContentFixture::class
        ];
    }

    private function checkAlwaysVisibleParts(\FunctionalTester $I)
    {
        $I->seeInTitle('TestAirlines');
        // Footer
        $I->see('TestAirlines');
        $I->seeLink('Mam', 'https://github.com/pianista215/mam');
        $I->seeLink('', 'https://testfacebook.com/');
        $I->seeLink('', 'https://testinstagram.com/');
        $I->seeLink('', 'https://testx.com/');

        // Main content loaded
        $I->see('This is the home page');
    }

    public function checkMainAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('/');

        $this->checkAlwaysVisibleParts($I);

        // Header
        $I->seeLink('Home', '/site/index');
        $I->seeLink('About');
        $I->seeLink('Pilots');
        $I->seeLink('Operations');
        $I->seeLink('Login');

        $I->dontSeeLink('Flights');
        $I->dontSeeLink('Actions');
        $I->dontSeeLink('Validations');
        $I->dontSeeLink('Admin');
        $I->dontSee('Logout');

    }

    public function checkMainAsPilot(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('/');

        $this->checkAlwaysVisibleParts($I);

        // Header
        $I->seeLink('Home', '/site/index');
        $I->seeLink('About');
        $I->seeLink('Pilots');
        $I->seeLink('Operations');
        $I->dontSee('Login');

        $I->seeLink('Flights');
        $I->seeLink('Actions');
        $I->dontSeeLink('Validations');
        $I->dontSeeLink('Admin');
        $I->see('Logout (AB1234)', 'button');
    }

    public function checkMainAsVfrValidator(\FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('/');

        $this->checkAlwaysVisibleParts($I);

        // Header
        $I->seeLink('Home', '/site/index');
        $I->seeLink('About');
        $I->seeLink('Pilots');
        $I->seeLink('Operations');
        $I->dontSee('Login');

        $I->seeLink('Flights');
        $I->seeLink('Actions');
        $I->seeLink('Validations');
        $I->dontSeeLink('Admin');
        $I->see('Logout (AB2345)', 'button');
    }

    public function checkMainAsIfrValidator(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);
        $I->amOnRoute('/');

        $this->checkAlwaysVisibleParts($I);

        // Header
        $I->seeLink('Home', '/site/index');
        $I->seeLink('About');
        $I->seeLink('Pilots');
        $I->seeLink('Operations');
        $I->dontSee('Login');

        $I->seeLink('Flights');
        $I->seeLink('Actions');
        $I->seeLink('Validations');
        $I->dontSeeLink('Admin');
        $I->see('Logout (AB3456)', 'button');
    }

    public function checkMainAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('/');

        $this->checkAlwaysVisibleParts($I);

        // Header
        $I->seeLink('Home', '/site/index');
        $I->seeLink('About');
        $I->seeLink('Pilots');
        $I->seeLink('Operations');
        $I->dontSee('Login');

        $I->seeLink('Flights');
        $I->seeLink('Actions');
        $I->seeLink('Validations');
        $I->seeLink('Admin');
        $I->see('Logout (ADM123)', 'button');
    }


}