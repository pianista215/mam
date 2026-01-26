<?php

namespace tests\functional\country;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class CountryUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function openCountryUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('country/update', [ 'id' => '1' ]);

        $I->see('Name');
        $I->see('Spain');
        $I->see('Iso2 Code');
        $I->see('ES');
        $I->see('Save', 'button');
    }

    public function openCountryUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('country/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Name');
        $I->dontSee('Spain');
        $I->dontSee('Iso2 Code');
        $I->dontSee('ES');
        $I->dontSee('Save', 'button');
    }

    public function openCountryUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('country/update', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function updateEmptyCountry(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('country/update', [ 'id' => '1' ]);
       $I->fillField('#country-name', '');
       $I->fillField('#country-iso2_code', '');
       $I->click('Save');
       $I->expectTo('see validations errors');
       $I->see('Name cannot be blank.');
       $I->see('Iso2 Code cannot be blank.');

       $count = \app\models\Country::find()->count();
       $I->assertEquals(2, $count);
    }

    public function updateValidCountry(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('country/update', [ 'id' => '1' ]);
       $I->fillField('#country-name', 'Prueba2');
       $I->fillField('#country-iso2_code', 'PR');
       $I->click('Save');
       $I->seeResponseCodeIs(200);
       $I->see('Prueba2');
       $I->see('PR');
       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Country::find()->where(['iso2_code' => 'PR'])->one();
       $I->assertNotNull($model);
       $I->assertEquals('Prueba2', $model->name);

       $count = \app\models\Country::find()->count();
       $I->assertEquals(2, $count);
    }

}