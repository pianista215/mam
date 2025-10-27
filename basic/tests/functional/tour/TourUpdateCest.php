<?php

namespace tests\functional\country;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\TourStageFixture;
use Yii;

class TourUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'tourStage' => TourStageFixture::class,
        ];
    }

    public function openTourUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour/update', ['id' => '1']);

        $I->see('Update Tour: Tour previous');
        $I->see('Save', 'button');
    }

    public function openTourUpdateAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);
        $I->amOnRoute('tour/update', ['id' => '1']);

        $I->see('Update Tour: Tour previous');
        $I->see('Save', 'button');
    }

    public function openTourUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('tour/update', ['id' => '1']);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Update Tour: Tour previous');
        $I->dontSee('Save', 'button');
    }

    public function openTourUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour/update', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function updateEmptyTour(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('tour/update', ['id' => '1']);
       $I->fillField('#tour-name','');
       $I->fillField('#tour-description','');
       $I->fillField('#tour-start','');
       $I->fillField('#tour-end','');
       $I->click('Save', 'button');
       $I->expectTo('see validations errors');
       $I->see('Name cannot be blank.');
       $I->see('Description cannot be blank.');
       $I->see('Start cannot be blank.');
       $I->see('End cannot be blank.');
       $model = \app\models\Tour::find()->where(['id' => 1])->one();
       $I->assertEquals('Tour previous', $model->name);
       $count = \app\models\Tour::find()->count();
       $I->assertEquals(4, $count);
    }

    public function updateInvalidDatesTour(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('tour/update', ['id' => '1']);
       $I->fillField('#tour-start','2025-02-02');
       $I->fillField('#tour-end','2022-02-02');
       $I->click('Save', 'button');
       $I->expectTo('see validations errors');
       $I->see('The date of end must be later than start.');
       $model = \app\models\Tour::find()->where(['id' => 1])->one();
       $I->assertEquals(date('Y-m-d', strtotime('-100 day')), $model->start);
       $I->assertEquals(date('Y-m-d', strtotime('-5 day')), $model->end);
       $count = \app\models\Tour::find()->count();
       $I->assertEquals(4, $count);
    }

    public function updateValidTour(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('tour/update', ['id' => '1']);
       $I->fillField('#tour-name','Changed name');
       $I->fillField('#tour-description','Changed desc');
       $I->fillField('#tour-start','2018-02-02');
       $I->fillField('#tour-end','2035-02-02');
       $I->click('Save', 'button');
       $I->seeResponseCodeIs(200);
       $I->see('Changed name');
       $I->see('Changed desc');
       $I->see('Start: Feb 2, 2018');
       $I->see('End: Feb 2, 2035');
       $I->see('Update', 'a');
       $I->see('Delete', 'a');
       $I->see('Add Stage', 'a');

       $model = \app\models\Tour::find()->where(['id' => 1])->one();
       $I->assertNotNull($model);
       $I->assertEquals('Changed name', $model->name);
       $I->assertEquals('Changed desc', $model->description);
       $I->assertEquals('2018-02-02', $model->start);
       $I->assertEquals('2035-02-02', $model->end);
       $count = \app\models\Tour::find()->count();
       $I->assertEquals(4, $count);
    }

}