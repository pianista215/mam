<?php

namespace tests\functional\aircraftType;

use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftTypeUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraftType' => AircraftTypeFixture::class,
        ];
    }

    public function openAircraftTypeUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-type/update', [ 'id' => '1' ]);

        $I->see('Update Aircraft Type: Airbus A320');
        $I->see('Save', 'button');
    }

    public function openAircraftTypeUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft-type/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('A320');
        $I->dontSee('Airbus A320');
        $I->dontSee('3186');

        $I->dontSee('Save', 'button');
    }

    public function openAircraftTypeUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('A320');
        $I->dontSee('Airbus A320');
        $I->dontSee('3186');

        $I->dontSee('Save', 'button');
    }

    public function updateEmptyAircraftType(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-type/update', [ 'id' => '1' ]);

        $I->fillField('#aircrafttype-icao_type_code','');
        $I->fillField('#aircrafttype-name','');
        $I->fillField('#aircrafttype-max_nm_range','');
        $I->click('Save');

        $I->expectTo('see validations errors');
        $I->see('Icao Type Code cannot be blank.');
        $I->see('Name cannot be blank.');
        $I->see('Max Nm Range cannot be blank.');

        $count = \app\models\AircraftType::find()->count();
        $I->assertEquals(3, $count);
    }

    public function updateValidAircraftType(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-type/update', [ 'id' => '1' ]);

        $I->fillField('#aircrafttype-icao_type_code','C182');
        $I->fillField('#aircrafttype-name','Cessna 182 RG');
        $I->fillField('#aircrafttype-max_nm_range','915');

        $I->click('Save');

        $I->seeResponseCodeIs(200);
        $I->see('C182');
        $I->see('Cessna 182 RG');
        $I->see('915');

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $model = \app\models\AircraftType::find()->where(['icao_type_code' => 'C182'])->one();
        $I->assertNotNull($model);
        $I->assertEquals('Cessna 182 RG', $model->name);
        $I->assertEquals(915, $model->max_nm_range);

        $count = \app\models\AircraftType::find()->count();
        $I->assertEquals(3, $count);
    }

}