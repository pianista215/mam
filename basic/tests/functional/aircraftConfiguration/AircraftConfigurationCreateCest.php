<?php

namespace tests\functional\aircraftConfiguration;

use tests\fixtures\AircraftFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftConfigurationCreateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
        ];
    }

    public function openAircraftConfigurationCreateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-configuration/create');

        $I->see('Create Aircraft Configuration');
        $I->see('Save', 'button');
    }

    public function openAircraftConfigurationCreateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft-configuration/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Aircraft Configuration');
        $I->dontSee('Save', 'button');
    }

    public function openAircraftConfigurationCreateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-configuration/create');
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function submitEmptyAircraftConfiguration(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('aircraft-configuration/create');
       $I->click('Save', 'button');

       $I->expectTo('see validations errors');
       $I->see('Aircraft Type ID cannot be blank.');
       $I->see('Name cannot be blank.');
       $I->see('Pax Capacity cannot be blank.');
       $I->see('Cargo Capacity cannot be blank.');

       $count = \app\models\AircraftConfiguration::find()->count();
       $I->assertEquals(3, $count);
    }

    public function submitValidAircraftConfiguration(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('aircraft-configuration/create');

       $I->selectOption('form select[name="AircraftConfiguration[aircraft_type_id]"]', 'Airbus A320');
       $I->fillField('#aircraftconfiguration-name', 'Business Special');
       $I->fillField('#aircraftconfiguration-pax_capacity', '140');
       $I->fillField('#aircraftconfiguration-cargo_capacity', '950');
       $I->click('Save', 'button');

       $I->seeResponseCodeIs(200);
       $I->see('Airbus A320 (Business Special)');
       $I->see('140');
       $I->see('950');

       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\AircraftConfiguration::find()->where(['name' => 'Business Special'])->one();
       $I->assertNotNull($model);
       $I->assertEquals(1, $model->aircraft_type_id);
       $I->assertEquals(140, $model->pax_capacity);
       $I->assertEquals(950, $model->cargo_capacity);

       $count = \app\models\AircraftConfiguration::find()->count();
       $I->assertEquals(4, $count);
    }

}