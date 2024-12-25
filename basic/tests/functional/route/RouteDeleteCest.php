<?php

namespace tests\functional\Route;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\RouteFixture;
use Yii;

class RouteDeleteCest
{

    // TODO: Create acceptance tests to delete (lack of JS Support, POST not available in codeception API)

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'route' => RouteFixture::class,
        ];
    }

    public function deleteRouteAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('route/view', [ 'id' => '1' ]);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('route/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Route::find()->count();
        $I->assertEquals(3, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('route/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Route::find()->count();
        $I->assertEquals(3, $count);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('route/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
        $count = \app\models\Route::find()->count();
        $I->assertEquals(3, $count);
    }

}