<?php

namespace tests\functional\aircraft;

use tests\fixtures\AircraftFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftCreateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
        ];
    }

    public function openAircraftCreateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/create');

        $I->see('Create Aircraft');
        $I->see('Save', 'button');
    }

    public function openAircraftCreateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Aircraft');
        $I->dontSee('Save', 'button');
    }

    public function openAircraftCreateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Aircraft');
        $I->dontSee('Save', 'button');
    }

    public function submitEmptyAircraft(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('aircraft/create');
       $I->click('Save', 'button');

       $I->expectTo('see validations errors');
       $I->see('Aircraft Configuration ID cannot be blank.');
       $I->see('Registration cannot be blank.');
       $I->see('Name cannot be blank.');
       $I->see('Location cannot be blank.');

       $count = \app\models\Aircraft::find()->count();
       $I->assertEquals(7, $count);
    }

    public function submitValidAircraft(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('aircraft/create');

       $I->selectOption('form select[name="Aircraft[aircraft_configuration_id]"]', 'Boeing 737-800 (Standard)');
       $I->fillField('#aircraft-registration', 'EC-CCC');
       $I->fillField('#aircraft-name', 'Boeing Acft');
       $I->fillField('#aircraft-location', 'LEMD');
       $I->click('Save', 'button');

       $I->seeResponseCodeIs(200);
       $I->see('Boeing Acft');
       $I->see('EC-CCC');
       $I->see('LEMD');
       $I->see('0');

       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Aircraft::find()->where(['registration' => 'EC-CCC'])->one();
       $I->assertNotNull($model);
       $I->assertEquals(1, $model->aircraft_configuration_id);
       $I->assertEquals('Boeing Acft', $model->name);
       $I->assertEquals('LEMD', $model->location);
       $I->assertEquals(0, $model->hours_flown);

       $count = \app\models\Aircraft::find()->count();
       $I->assertEquals(8, $count);
    }

}