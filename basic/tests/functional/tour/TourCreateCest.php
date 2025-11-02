<?php

namespace tests\functional\country;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class TourCreateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function openTourCreateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/create');

        $I->see('Create Tour');
        $I->see('Save', 'button');
    }

    public function openTourCreateAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);
        $I->amOnRoute('tour/create');

        $I->see('Create Tour');
        $I->see('Save', 'button');
    }

    public function openTourCreateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('tour/create');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Create Tour');
        $I->dontSee('Save', 'button');
    }

    public function openTourCreateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour/create');
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function submitEmptyTour(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('tour/create');
       $I->click('Save', 'button');
       $I->expectTo('see validations errors');
       $I->see('Name cannot be blank.');
       $I->see('Description cannot be blank.');
       $I->see('Start cannot be blank.');
       $I->see('End cannot be blank.');
       $count = \app\models\Tour::find()->count();
       $I->assertEquals(0, $count);
    }

    public function submitInvalidDatesTour(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('tour/create');
       $I->fillField('#tour-name','Prueba');
       $I->fillField('#tour-description','Tour example');
       $I->fillField('#tour-start','2025-02-02');
       $I->fillField('#tour-end','2022-02-02');
       $I->click('Save', 'button');
       $I->expectTo('see validations errors');
       $I->see('The date of end must be later than start.');
       $count = \app\models\Tour::find()->count();
       $I->assertEquals(0, $count);
    }

    public function submitValidTour(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('tour/create');
       $I->fillField('#tour-name','Prueba');
       $I->fillField('#tour-description','Tour example');
       $I->fillField('#tour-start','2022-02-02');
       $I->fillField('#tour-end','2025-02-02');
       $I->click('Save', 'button');
       $I->seeResponseCodeIs(200);
       $I->see('Prueba');
       $I->see('Tour example');
       $I->see('Start: Feb 2, 2022');
       $I->see('End: Feb 2, 2025');
       $I->see('This tour has no stages yet.');
       $I->see('Update', 'a');
       $I->see('Delete', 'a');
       $I->see('Add First Stage', 'a');

       $model = \app\models\Tour::find()->where(['name' => 'Prueba'])->one();
       $I->assertNotNull($model);
       $I->assertEquals('Prueba', $model->name);
       $I->assertEquals('Tour example', $model->description);
       $I->assertEquals('2022-02-02', $model->start);
       $I->assertEquals('2025-02-02', $model->end);
       $count = \app\models\Tour::find()->count();
       $I->assertEquals(1, $count);
    }

}