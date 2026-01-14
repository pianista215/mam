<?php

namespace tests\functional\admin;

use app\config\Config;
use app\config\ConfigHelper as CK;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class SiteSettingsCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
        ];
    }

    public function _before(\FunctionalTester $I)
    {
        Yii::$app->cache->flush();
    }

    public function accessAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('admin/site-settings');
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function accessAsNormalUserForbidden(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('admin/site-settings');

        $I->seeResponseCodeIs(403);
        $I->dontSee('Site Settings');
        $I->dontSeeElement('form');
    }

    public function accessAsAdminAllowed(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('admin/site-settings');

        $I->seeResponseCodeIs(200);
        $I->see('Site Settings');
        $I->seeElement('form');
    }

    public function updateSettingsSuccessfully(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        Config::set(CK::AIRLINE_NAME, 'Old Airline');
        Config::set(CK::TOKEN_LIFE_H, '24');

        $I->amOnRoute('admin/site-settings');
        $I->see('Old Airline');

        $I->fillField('input[name="SiteSettingsForm[airline_name]"]', 'New Airline');
        $I->fillField('input[name="SiteSettingsForm[token_life_h]"]', '48');
        $I->click('Save');

        $I->seeResponseCodeIs(200);
        $I->see('Site settings successfully saved.');

        $I->assertEquals('New Airline', Config::get(CK::AIRLINE_NAME));
        $I->assertEquals('48', Config::get(CK::TOKEN_LIFE_H));
    }

    public function invalidPathIsNotSaved(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        Config::set(CK::CHUNKS_STORAGE_PATH, '/tmp/chunks_ok');

        $I->amOnRoute('admin/site-settings');

        $I->fillField('input[name="SiteSettingsForm[chunks_storage_path]"]', '/path/that/does/not/exist');
        $I->click('Save');

        $I->see('Please fix the following errors');
        $I->see('Chunks storage path');

        $I->assertEquals('/tmp/chunks_ok', Config::get(CK::CHUNKS_STORAGE_PATH));
    }

    public function invalidRegistrationDatesAreNotSaved(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        Config::set(CK::REGISTRATION_START, '2024-01-01');

        $I->amOnRoute('admin/site-settings');

        $I->fillField('input[name="SiteSettingsForm[registration_start]"]', 'invalid-date');
        $I->click('Save');

        $I->see('Please fix the following errors');
        $I->see('Registration start date');

        $I->assertEquals('2024-01-01', Config::get(CK::REGISTRATION_START));
    }

    public function invalidCharterRatioIsNotSaved(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        Config::set(CK::CHARTER_RATIO, '0.2');

        $I->amOnRoute('admin/site-settings');

        $I->fillField('input[name="SiteSettingsForm[charter_ratio]"]', 'abc');
        $I->click('Save');

        $I->see('Please fix the following errors');
        $I->see('Charter ratio');

        $I->assertEquals('0.2', Config::get(CK::CHARTER_RATIO));
    }

    public function invalidEmailIsNotSaved(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        Config::set(CK::NO_REPLY_MAIL, 'no-reply@test.com');

        $I->amOnRoute('admin/site-settings');

        $I->fillField('input[name="SiteSettingsForm[no_reply_mail]"]', 'not-an-email');
        $I->click('Save');

        $I->see('Please fix the following errors');
        $I->see('No-reply email');

        $I->assertEquals('no-reply@test.com', Config::get(CK::NO_REPLY_MAIL));
    }
}
