<?php

namespace tests\functional\aircraftConfiguration;

use tests\fixtures\AircraftConfigurationFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftConfigurationUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraftConfiguration' => AircraftConfigurationFixture::class,
        ];
    }

    public function openAircraftConfigurationUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-configuration/update', [ 'id' => '1' ]);

        $I->see('Update Aircraft Configuration: Boeing 737-800 (Standard)');
        $I->see('Save', 'button');
    }

    public function openAircraftConfigurationUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft-configuration/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Update Aircraft Configuration: Boeing 737-800 (Standard)');
        $I->dontSee('737-800');

        $I->dontSee('Save', 'button');
    }

    public function openAircraftConfigurationUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-configuration/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Update Aircraft Configuration: Boeing 737-800 (Standard)');
        $I->dontSee('737-800');

        $I->dontSee('Save', 'button');
    }

    public function updateEmptyAircraftConfiguration(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-configuration/update', [ 'id' => '1' ]);

        $I->fillField('#aircraftconfiguration-name', '');
        $I->fillField('#aircraftconfiguration-pax_capacity', '');
        $I->fillField('#aircraftconfiguration-cargo_capacity', '');
        $I->click('Save');

        $I->expectTo('see validations errors');
        $I->see('Name cannot be blank.');
        $I->see('Pax Capacity cannot be blank.');
        $I->see('Cargo Capacity cannot be blank.');

        $count = \app\models\AircraftConfiguration::find()->count();
        $I->assertEquals(2, $count);
    }

    public function updateValidAircraftConfiguration(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-configuration/update', [ 'id' => '1' ]);

        $I->fillField('#aircraftconfiguration-name', 'Other conf');
        $I->fillField('#aircraftconfiguration-pax_capacity', '150');
        $I->fillField('#aircraftconfiguration-cargo_capacity', '800');

        $I->click('Save');

        $I->seeResponseCodeIs(200);
        $I->see('Boeing 737-800 (Other conf)');
        $I->see('150');
        $I->see('800');

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $model = \app\models\AircraftConfiguration::find()->where(['id' => 1])->one();
        $I->assertNotNull($model);
        $I->assertEquals('Other conf', $model->name);
        $I->assertEquals(150, $model->pax_capacity);
        $I->assertEquals(800, $model->cargo_capacity);

        $count = \app\models\AircraftConfiguration::find()->count();
        $I->assertEquals(2, $count);
    }

}