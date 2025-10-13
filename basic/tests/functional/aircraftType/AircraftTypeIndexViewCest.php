<?php

namespace tests\functional\aircraftType;

use tests\fixtures\AircraftConfigurationFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftTypeIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraftConfiguration' => AircraftConfigurationFixture::class,
        ];
    }

    private function checkAircraftTypeIndexCommon(\FunctionalTester $I){
        $I->amOnRoute('aircraft-type/index');

        $I->see('Aircraft Types');
        $I->see('Showing 1-4 of 4 items.');
        $I->see('A320');
        $I->see('Airbus A320');
        $I->see('B738');
        $I->see('Boeing 737-800');
        $I->see('B350');
        $I->see('Beechcraft King Air 350i');
        $I->see('C172');
        $I->see('Cessna 172');
    }

    public function openAircraftTypeIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkAircraftTypeIndexCommon($I);

        $I->see('Create Aircraft Type', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftTypeIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkAircraftTypeIndexCommon($I);

        $I->dontSee('Create Aircraft Type', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftTypeIndexAsVisitor(\FunctionalTester $I)
    {
        $this->checkAircraftTypeIndexCommon($I);

        $I->dontSee('Create AircraftType', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    private function checkAircraftTypeViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('aircraft-type/view', [ 'id' => '2' ]);

        $I->see('B738');
        $I->see('Boeing 737-800');
        $I->see('5665');

        $I->see('Configurations');
        $I->see('Showing 1-2 of 2 items.');
        $I->see('Standard');
        $I->see('160');
        $I->see('4900');
        $I->see('Cargo');
        $I->see('0');
        $I->see('23500');
    }

    public function openAircraftTypeViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkAircraftTypeViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftTypeViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkAircraftTypeViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftTypeViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/view', [ 'id' => '2' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }


}