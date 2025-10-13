<?php

namespace tests\functional\auth;

use tests\fixtures\PageContentFixture;
use tests\fixtures\PilotFixture;

class LoginFormCest
{
    public function _before(\FunctionalTester $I)
    {
        $I->amOnRoute('site/login');
        $I->haveFixtures([
            'pilot' => PilotFixture::class,
            'pageContent' => PageContentFixture::class
        ]);
    }

    public function openLoginPage(\FunctionalTester $I)
    {
        $I->see('Login', 'h1');
    }

    // demonstrates `amLoggedInAs` method
    public function internalLoginById(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnPage('/');
        $I->see('Logout (AB1234)');
    }

    // demonstrates `amLoggedInAs` method
    public function internalLoginByInstance(\FunctionalTester $I)
    {
        $I->amLoggedInAs(\app\models\Pilot::findByLicense('AB1234'));
        $I->amOnPage('/');
        $I->see('Logout (AB1234)');
    }

    public function loginWithEmptyCredentials(\FunctionalTester $I)
    {
        $I->submitForm('#login-form', []);
        $I->expectTo('see validations errors');
        $I->see('Username cannot be blank.');
        $I->see('Password cannot be blank.');
    }

    public function loginWithWrongCredentials(\FunctionalTester $I)
    {
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'AB1234',
            'LoginForm[password]' => 'wrong',
        ]);
        $I->expectTo('see validations errors');
        $I->see('Incorrect username or password.');
    }

    public function loginSuccessfully(\FunctionalTester $I)
    {
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'AB1234',
            'LoginForm[password]' => 'SecurePass123!',
        ]);
        $I->see('Logout (AB1234)');
        $I->dontSeeElement('form#login-form');              
    }

    public function loginCaseInsensitiveSuccessfully(\FunctionalTester $I)
    {
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'ab1234',
            'LoginForm[password]' => 'SecurePass123!',
        ]);
        $I->see('Logout (AB1234)');
        $I->dontSeeElement('form#login-form');
    }
}