<?php

namespace tests\functional\country;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class CountryCreateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function openCountryCreateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('country/create');

        $I->see('Create Country');
        $I->see('Save', 'button');
    }

    public function openCountryCreateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('country/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Country');
        $I->dontSee('Save', 'button');
    }

    public function openCountryCreateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('country/create');
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function submitEmptyCountry(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('country/create');
       $I->submitForm('#country-form', []);
       $I->expectTo('see validations errors');
       $I->see('Name cannot be blank.');
       $I->see('Iso2 Code cannot be blank.');
       $count = \app\models\Country::find()->count();
       $I->assertEquals(1, $count);
    }

    public function submitValidCountry(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('country/create');
       $I->submitForm('#country-form', [
            'Country[name]' => 'Prueba',
            'Country[iso2_code]' => 'PR',
       ]);
       $I->seeResponseCodeIs(200);
       $I->see('Prueba');
       $I->see('PR');
       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Country::find()->where(['iso2_code' => 'PR'])->one();
       $I->assertNotNull($model);
       $I->assertEquals('Prueba', $model->name);
       $count = \app\models\Country::find()->count();
       $I->assertEquals(2, $count);
    }

}