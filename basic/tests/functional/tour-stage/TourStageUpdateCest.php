<?php

namespace tests\functional\country;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightFixture;

use Yii;

class TourStageUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flight' => FlightFixture::class,
        ];
    }

    public function openTourStageUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour-stage/update', ['id' => '1']);
        $I->seeResponseCodeIs(200);

        $I->see('Update Stage 1 from Tour: Tour previous');
        $I->see('Save Stage', 'button');
    }

    public function openTourStageUpdateAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);
        $I->amOnRoute('tour-stage/update', ['id' => '1']);
        $I->seeResponseCodeIs(200);

        $I->see('Update Stage 1 from Tour: Tour previous');
        $I->see('Save Stage', 'button');
    }

    public function openTourStageUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('tour-stage/update', ['id' => '1']);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Update Stage 1 from Tour: Tour previous');
        $I->dontSee('Save Stage', 'button');
    }

    public function openTourStageUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour-stage/update', ['id' => '1']);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function tourStageCantUpdateStageAirports(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('tour-stage/update', ['id' => '1']);
        $I->seeElement('#tourstage-departure[readonly]');
        $I->seeElement('#tourstage-arrival[readonly]');
        $I->fillField('#tourstage-departure','LEVC');
        $I->fillField('#tourstage-arrival','LEVC');
        $I->fillField('#tourstage-description','New description');
        $I->click('Save Stage', 'button');
        $I->seeResponseCodeIs(200);
        $model = \app\models\TourStage::find()->where(['id' => '1'])->one();
        $I->assertEquals('LEBL', $model->departure);
        $I->assertEquals('LEMD', $model->arrival);
        $I->assertEquals(260, $model->distance_nm);
        $I->assertEquals('New description', $model->description);
        $I->assertEquals(1, $model->sequence);
        $I->assertEquals(1, $model->tour_id);
    }
}