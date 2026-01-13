<?php

namespace tests\functional\airport;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AirportDeleteCest
{

    // TODO: Create acceptance tests to delete (lack of JS Support, POST not available in codeception API)

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function deleteAirportAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('airport/view', [ 'id' => '1' ]);

        $I->see('Delete');
    }

    public function deleteAirportAsAirportManager(\FunctionalTester $I)
    {
        $I->amLoggedInAs(12);
        $I->amOnRoute('airport/view', [ 'id' => '1' ]);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('airport/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Airport::find()->count();
        $I->assertEquals(5, $count);
    }

    public function deleteOnlyPostAsAirportManager(\FunctionalTester $I)
    {
        $I->amLoggedInAs(12);
        $I->amOnRoute('airport/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Airport::find()->count();
        $I->assertEquals(5, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('airport/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Airport::find()->count();
        $I->assertEquals(5, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('airport/delete', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

}