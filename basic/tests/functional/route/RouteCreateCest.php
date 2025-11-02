<?php

namespace tests\functional\route;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\RouteFixture;
use Yii;

class RouteCreateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'route' => RouteFixture::class,
        ];
    }

    public function openRouteCreateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('route/create');

        $I->see('Create Route');
        $I->see('Save', 'button');
    }

    public function openRouteCreateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('route/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Route');
        $I->dontSee('Save', 'button');
    }

    public function openRouteCreateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('route/create');
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function submitEmptyRoute(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);

       $I->amOnRoute('route/create');
       $I->click('Save', 'button');

       $I->expectTo('see validations errors');
       $I->see('Code cannot be blank.');
       $I->see('Departure cannot be blank.');
       $I->see('Arrival cannot be blank.');

       $count = \app\models\Route::find()->count();
       $I->assertEquals(3, $count);
    }

    public function submitValidRoute(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('route/create');

       $I->fillField('#route-code','N001');
       $I->fillField('#route-departure','LEMD');
       $I->fillField('#route-arrival','LEVC');
       $I->click('Save', 'button');

       $I->seeResponseCodeIs(200);
       $I->see('LEMD');
       $I->see('LEVC');
       $I->see('153');
       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Route::find()->where(['code' => 'N001'])->one();
       $I->assertNotNull($model);
       $I->assertEquals('LEMD', $model->departure);
       $I->assertEquals('LEVC', $model->arrival);
       $I->assertEquals(153, $model->distance_nm);

       $count = \app\models\Route::find()->count();
       $I->assertEquals(4, $count);
    }

}