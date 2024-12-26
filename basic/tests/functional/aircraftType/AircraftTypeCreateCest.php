<?php

namespace tests\functional\aircraftType;

use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftTypeCreateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraftType' => AircraftTypeFixture::class,
        ];
    }

    public function openAircraftTypeCreateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-type/create');

        $I->see('Create Aircraft Type');
        $I->see('Save', 'button');
    }

    public function openAircraftTypeCreateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft-type/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Aircraft Type');
        $I->dontSee('Save', 'button');
    }

    public function openAircraftTypeCreateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Aircraft Type');
        $I->dontSee('Save', 'button');
    }

    public function submitEmptyAircraftType(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('aircraft-type/create');
       $I->click('Save', 'button');

       $I->expectTo('see validations errors');
       $I->see('Icao Type Code cannot be blank.');
       $I->see('Name cannot be blank.');
       $I->see('Max Nm Range cannot be blank.');

       $count = \app\models\AircraftType::find()->count();
       $I->assertEquals(4, $count);
    }

    public function submitValidAircraftType(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('aircraft-type/create');

       $I->fillField('#aircrafttype-icao_type_code','BE58');
       $I->fillField('#aircrafttype-name','Baron 58');
       $I->fillField('#aircrafttype-max_nm_range','1250');
       $I->click('Save', 'button');

       $I->seeResponseCodeIs(200);
       $I->see('BE58');
       $I->see('Baron 58');
       $I->see('1250');

       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\AircraftType::find()->where(['icao_type_code' => 'BE58'])->one();
       $I->assertNotNull($model);
       $I->assertEquals('Baron 58', $model->name);
       $I->assertEquals(1250, $model->max_nm_range);

       $count = \app\models\AircraftType::find()->count();
       $I->assertEquals(5, $count);
    }

}