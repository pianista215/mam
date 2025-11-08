<?php

namespace tests\functional\route;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\RouteFixture;
use Yii;

class RouteIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'route' => RouteFixture::class,
        ];
    }

    private function checkRouteIndexCommon(\FunctionalTester $I){
        $I->amOnRoute('route/index');

        $I->see('Routes');
        $I->see('Showing 1-3 of 3 items.');
        $I->see('LEBL');
        $I->see('LEVC');
        $I->see('GCLP');
        $I->see('R001');
        $I->see('R002');
        $I->see('R003');
    }

    public function openRouteIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkRouteIndexCommon($I);

        $I->see('Create Route', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openRouteIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkRouteIndexCommon($I);

        $I->dontSee('Create Route', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openRouteIndexAsVisitor(\FunctionalTester $I)
    {
        $this->checkRouteIndexCommon($I);

        $I->dontSee('Create Route', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    private function checkRouteViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('route/view', [ 'id' => '1' ]);

        $I->see('LEBL');
        $I->see('LEVC');
        $I->see('R001');
        $I->see('165');
    }

    public function openRouteViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkRouteViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openRouteViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkRouteViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openRouteViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('route/view', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }


}