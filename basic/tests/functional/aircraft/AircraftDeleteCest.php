<?php

namespace tests\functional\aircraft;

use tests\fixtures\AircraftFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftDeleteCest
{

    // TODO: Create acceptance tests to delete (lack of JS Support, POST not available in codeception API)

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
        ];
    }

    public function deleteAircraftAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/view', [ 'id' => '1' ]);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Aircraft::find()->count();
        $I->assertEquals(7, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Aircraft::find()->count();
        $I->assertEquals(7, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Aircraft::find()->count();
        $I->assertEquals(7, $count);
    }

}