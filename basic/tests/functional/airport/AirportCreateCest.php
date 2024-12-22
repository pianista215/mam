<?php

namespace tests\functional\airport;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AirportCreateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function openAirportCreateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('airport/create');

        $I->see('Create Airport');
        $I->see('Save', 'button');
    }

    public function openAirportCreateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('airport/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Airport');
        $I->dontSee('Save', 'button');
    }

    public function openAirportCreateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('airport/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Airport');
        $I->dontSee('Save', 'button');
    }

    public function submitEmptyAirport(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('airport/create');
       $I->click('Save', 'button');
       $I->expectTo('see validations errors');
       $I->see('Icao Code cannot be blank.');
       $I->see('Name cannot be blank.');
       $I->see('Latitude cannot be blank.');
       $I->see('Longitude cannot be blank.');
       $I->see('Country ID cannot be blank.');
       $I->see('City cannot be blank.');
    }

    public function submitValidAirport(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('airport/create');
       $I->fillField('#airport-icao_code','LEVD');
       $I->fillField('#airport-name','Valladolid');
       $I->fillField('#airport-latitude','41.7114');
       $I->fillField('#airport-longitude','-4.84472');
       $I->selectOption('form select[name="Airport[country_id]"]', 'Spain');
       $I->fillField('#airport-city','Valladolid');
       $I->click('Save', 'button');

       $I->seeResponseCodeIs(200);
       $I->see('LEVD');
       $I->see('Valladolid');
       $I->see('41.7114');
       $I->see('-4.84472');
       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Airport::find()->where(['icao_code' => 'LEVD'])->one();
       $I->assertNotNull($model);
       $I->assertEquals('Valladolid', $model->name);
       $I->assertEquals('Valladolid', $model->city);
       $I->assertEquals(1, $model->country_id);
       $I->assertEquals(41.7114, $model->latitude);
       $I->assertEquals(-4.84472, $model->longitude);
    }

}