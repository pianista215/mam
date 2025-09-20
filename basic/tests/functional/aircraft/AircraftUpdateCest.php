<?php

namespace tests\functional\aircraft;

use tests\fixtures\AircraftFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
        ];
    }

    public function openAircraftUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/update', [ 'id' => '1' ]);

        $I->see('Update Aircraft: Boeing Name Std');
        $I->see('Save', 'button');
    }

    public function openAircraftUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Update Aircraft: Boeing Name Std');
        $I->dontSee('EC-AAA');

        $I->dontSee('Save', 'button');
    }

    public function openAircraftUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Update Aircraft: Boeing Name Std');
        $I->dontSee('EC-AAA');

        $I->dontSee('Save', 'button');
    }

    public function updateEmptyAircraft(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/update', [ 'id' => '1' ]);

        $I->fillField('#aircraft-registration', '');
        $I->fillField('#aircraft-name', '');
        $I->click('Save');

        $I->expectTo('see validations errors');
        $I->see('Registration cannot be blank.');
        $I->see('Name cannot be blank.');

        $count = \app\models\Aircraft::find()->count();
        $I->assertEquals(7, $count);
    }

    public function updateValidAircraft(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/update', [ 'id' => '1' ]);

        $I->fillField('#aircraft-registration', 'EC-XXX');
        $I->fillField('#aircraft-name', 'Other Boeing');

        $I->click('Save');

        $I->seeResponseCodeIs(200);
        $I->see('Other Boeing');
        $I->see('EC-XXX');
        $I->see('LEMD');
        $I->see('255:42');

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $model = \app\models\Aircraft::find()->where(['id' => 1])->one();
        $I->assertNotNull($model);
        $I->assertEquals('EC-XXX', $model->registration);
        $I->assertEquals('Other Boeing', $model->name);
        $I->assertEquals('LEMD', $model->location);
        $I->assertEquals(255.7, $model->hours_flown);

        $count = \app\models\Aircraft::find()->count();
        $I->assertEquals(7, $count);
    }

}