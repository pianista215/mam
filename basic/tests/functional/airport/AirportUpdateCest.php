<?php

namespace tests\functional\airport;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AirportUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function openAirportUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('airport/update', [ 'id' => '1' ]);

        $I->see('Update Airport: Madrid-Barajas');
        $I->see('Save', 'button');
    }

    public function openAirportUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('airport/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('LEMD');
        $I->dontSee('Madrid-Barajas');
        $I->dontSee('Madrid');
        $I->dontSee('40.471926');
        $I->dontSee('-3.56264');
        $I->dontSee('Save', 'button');
    }

    public function openAirportUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('airport/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('LEMD');
        $I->dontSee('Madrid-Barajas');
        $I->dontSee('Madrid');
        $I->dontSee('40.471926');
        $I->dontSee('-3.56264');
        $I->dontSee('Save', 'button');
    }

    public function updateEmptyAirport(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('airport/update', [ 'id' => '1' ]);
       $I->fillField('#airport-icao_code','');
       $I->fillField('#airport-name','');
       $I->fillField('#airport-latitude','');
       $I->fillField('#airport-longitude','');
       $I->fillField('#airport-city','');
       $I->click('Save');
       $I->expectTo('see validations errors');
       $I->see('Icao Code cannot be blank.');
       $I->see('Name cannot be blank.');
       $I->see('Latitude cannot be blank.');
       $I->see('Longitude cannot be blank.');
       $I->see('City cannot be blank.');

       $count = \app\models\Airport::find()->count();
       $I->assertEquals(4, $count);
    }

    public function updateValidAirport(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('airport/update', [ 'id' => '1' ]);
       $I->fillField('#airport-icao_code','LEVD');
       $I->fillField('#airport-name','Valladolid');
       $I->fillField('#airport-latitude','41.7114');
       $I->fillField('#airport-longitude','-4.84472');
       $I->fillField('#airport-city','Valladolid');
       $I->click('Save');

       $I->seeResponseCodeIs(200);
       $I->see('Valladolid');
       $I->see('LEVD');
       $I->see('41.7114');
       $I->see('-4.84472');

       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Airport::find()->where(['icao_code' => 'LEVD'])->one();
       $I->assertNotNull($model);
       $I->assertEquals('Valladolid', $model->name);
       $I->assertEquals(41.7114, $model->latitude);
       $I->assertEquals(-4.84472, $model->longitude);
       $I->assertEquals('Valladolid', $model->city);

       $count = \app\models\Airport::find()->count();
       $I->assertEquals(4, $count);
    }

}