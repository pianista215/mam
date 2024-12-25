<?php

namespace tests\functional\Route;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\RouteFixture;
use Yii;

class RouteUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'route' => RouteFixture::class,
        ];
    }

    public function openRouteUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('route/update', [ 'id' => '1' ]);

        $I->see('Update Route: R001');
        $I->see('Save', 'button');
    }

    public function openRouteUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('route/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Update Route: R001');
        $I->dontSee('Save', 'button');
    }

    public function openRouteUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('route/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Update Route: R001');
        $I->dontSee('Save', 'button');
    }

    public function updateEmptyRoute(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('route/update', [ 'id' => '1' ]);

       $I->fillField('#route-code','');
       $I->fillField('#route-departure','');
       $I->fillField('#route-arrival','');
       $I->click('Save');

       $I->expectTo('see validations errors');
       $I->expectTo('see validations errors');
       $I->see('Code cannot be blank.');
       $I->see('Departure cannot be blank.');
       $I->see('Arrival cannot be blank.');

       $count = \app\models\Route::find()->count();
       $I->assertEquals(3, $count);
    }

    public function updateValidRoute(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('route/update', [ 'id' => '1' ]);

       $I->fillField('#route-code','N001');
       $I->fillField('#route-departure','LEBL');
       $I->fillField('#route-arrival','LEMD');
       $I->click('Save');

       $I->seeResponseCodeIs(200);
       $I->see('N001');
       $I->see('LEBL');
       $I->see('LEMD');
       $I->see('260');

       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Route::find()->where(['code' => 'N001'])->one();
       $I->assertNotNull($model);
       $I->assertEquals('N001', $model->code);
       $I->assertEquals('LEBL', $model->departure);
       $I->assertEquals('LEMD', $model->arrival);
       $I->assertEquals(260, $model->distance_nm);

       $count = \app\models\Route::find()->count();
       $I->assertEquals(3, $count);
    }

}