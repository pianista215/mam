<?php

namespace tests\functional\country;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightFixture;

use Yii;

class TourStageAddCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flight' => FlightFixture::class,
        ];
    }

    public function openTourStageAddAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour-stage/add-stage', ['tour_id' => '1']);
        $I->seeResponseCodeIs(200);

        $I->see('Add Stage 2 to Tour: Tour previous');
        $I->see('Save Stage', 'button');
    }

    public function openTourStageAddAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);
        $I->amOnRoute('tour-stage/add-stage', ['tour_id' => '1']);
        $I->seeResponseCodeIs(200);

        $I->see('Add Stage 2 to Tour: Tour previous');
        $I->see('Save Stage', 'button');
    }

    public function openTourStageAddAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('tour-stage/add-stage', ['tour_id' => '1']);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Add Stage 2 to Tour: Tour previous');
        $I->dontSee('Save Stage', 'button');
    }

    public function openTourStageAddAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour-stage/add-stage', ['tour_id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function submitEmptyStage(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour-stage/add-stage', ['tour_id' => '1']);
        $I->click('Save Stage', 'button');
        $I->expectTo('see validations errors');
        $I->see('Departure cannot be blank.');
        $I->see('Arrival cannot be blank.');
        $count = \app\models\TourStage::find()->where(['tour_id' => '1'])->count();
        $I->assertEquals(1, $count);
    }

    public function submitInvalidAirport(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour-stage/add-stage', ['tour_id' => '1']);
        $I->fillField('#tourstage-departure','xxxx');
        $I->fillField('#tourstage-arrival','xxxx');
        $I->click('Save Stage', 'button');
        $I->expectTo('see validations errors');
        $I->see('Departure is invalid.');
        $I->see('Arrival is invalid.');
        $count = \app\models\TourStage::find()->where(['tour_id' => '1'])->count();
        $I->assertEquals(1, $count);
    }

    public function submitValidStage(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour-stage/add-stage', ['tour_id' => '1']);
        $I->fillField('#tourstage-departure','LEVC');
        $I->fillField('#tourstage-arrival','GCLP');
        $I->fillField('#tourstage-description','Ex Description');
        $I->click('Save Stage', 'button');
        $I->seeResponseCodeIs(200);
        $count = \app\models\TourStage::find()->where(['tour_id' => '1'])->count();
        $I->assertEquals(2, $count);

        $model = \app\models\TourStage::find()->where(['tour_id' => '1', 'departure' => 'LEVC'])->one();
        $I->assertNotNull($model);
        $I->assertEquals('LEVC', $model->departure);
        $I->assertEquals('GCLP', $model->arrival);
        $I->assertEquals('Ex Description', $model->description);
        $I->assertEquals(1015, $model->distance_nm);
        $I->assertEquals(2, $model->sequence);
        $I->assertEquals(1, $model->tour_id);
    }

    public function cantAddStageToFlownTour(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour-stage/add-stage', ['tour_id' => '3']);
        $I->seeCurrentUrlMatches('~view~');
        $I->see('Cannot add stages to a tour that already has flown stages.');
    }

}