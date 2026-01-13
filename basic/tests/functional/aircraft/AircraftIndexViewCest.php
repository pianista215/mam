<?php

namespace tests\functional\aircraft;

use tests\fixtures\AircraftFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'Aircraft' => AircraftFixture::class,
        ];
    }

    private function checkAircraftIndexCommon(\FunctionalTester $I){
        $I->amOnRoute('aircraft/index');

        $I->see('Aircrafts');
        $I->see('Showing 1-10 of 10 items.');

        $I->see('Boeing 737-800 (Standard)');
        $I->see('Boeing Name Std');
        $I->see('EC-AAA');
        $I->see('LEMD');

        $I->see('Boeing 737-800 (Cargo)');
        $I->see('Boeing Name Cargo');
        $I->see('EC-BBB');
        $I->see('LEBL');

        $I->see('Cessna 172 (Standard)');
        $I->see('C172 Std');
        $I->see('EC-UUU');
        $I->see('LEBL');
    }

    public function openAircraftIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkAircraftIndexCommon($I);

        $I->see('Create Aircraft', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkAircraftIndexCommon($I);

        $I->dontSee('Create Aircraft', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftIndexAsVisitor(\FunctionalTester $I)
    {
        $this->checkAircraftIndexCommon($I);

        $I->dontSee('Create Aircraft', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    private function checkAircraftViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('aircraft/view', [ 'id' => '1' ]);

        $I->see('Boeing Name Std');
        $I->see('Boeing 737-800 (Standard)');
        $I->see('EC-AAA');
        $I->see('LEMD');
        $I->see('255:42');
    }

    public function openAircraftViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkAircraftViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
        $I->see('Move Aircraft', 'a');
    }

    public function openAircraftViewAsFleetManager(\FunctionalTester $I)
    {
        $I->amLoggedInAs(9);

        $this->checkAircraftViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
        $I->dontSee('Move Aircraft', 'a');
    }

    public function openAircraftViewAsFleetOperator(\FunctionalTester $I)
    {
        $I->amLoggedInAs(11);

        $this->checkAircraftViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
        $I->see('Move Aircraft', 'a');
    }

    public function openAircraftViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkAircraftViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
        $I->dontSee('Move Aircraft', 'a');
    }

    public function openAircraftViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft/view', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }


}