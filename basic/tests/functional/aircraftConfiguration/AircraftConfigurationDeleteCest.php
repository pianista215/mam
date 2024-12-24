<?php

namespace tests\functional\aircraftConfiguration;

use tests\fixtures\AircraftConfigurationFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftConfigurationDeleteCest
{

    // TODO: Create acceptance tests to delete (lack of JS Support, POST not available in codeception API)

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraftConfiguration' => AircraftConfigurationFixture::class,
        ];
    }

    public function deleteAircraftConfigurationAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-configuration/view', [ 'id' => '1' ]);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('aircraft-configuration/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\AircraftConfiguration::find()->count();
        $I->assertEquals(2, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('aircraft-configuration/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\AircraftConfiguration::find()->count();
        $I->assertEquals(2, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-configuration/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\AircraftConfiguration::find()->count();
        $I->assertEquals(2, $count);
    }

}