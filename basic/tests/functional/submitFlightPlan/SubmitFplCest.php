<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\AircraftFixture;
use tests\fixtures\RouteFixture;
use Yii;

class SubmitFplCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
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

}