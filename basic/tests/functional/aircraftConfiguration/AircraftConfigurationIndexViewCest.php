<?php

namespace tests\functional\aircraftConfiguration;

use tests\fixtures\AircraftFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftConfigurationIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
        ];
    }

    private function checkAircraftConfigIndexCommon(\FunctionalTester $I){
        $I->amOnRoute('aircraft-configuration/index');

        $I->see('Aircraft Configurations');
        $I->see('Showing 1-3 of 3 items.');
        $I->see('Boeing 737-800');
        $I->see('Cargo');
        $I->see('Standard');
        $I->see('Cessna 172');
    }

    public function openAircraftConfigurationIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkAircraftConfigIndexCommon($I);

        $I->see('Create Aircraft Configuration', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftConfigurationIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkAircraftConfigIndexCommon($I);

        $I->dontSee('Create Aircraft Configuration', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftConfigurationIndexAsVisitor(\FunctionalTester $I)
    {
        $this->checkAircraftConfigIndexCommon($I);

        $I->dontSee('Create Aircraft Configuration', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    private function checkAircraftConfigViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('aircraft-configuration/view', [ 'id' => '1' ]);

        $I->see('Aircraft Type Name');
        $I->see('Boeing 737-800');
        $I->see('Standard');
        $I->see('160');
        $I->see('4900');

        $I->see('Aircrafts');

        $I->see('Showing 1-3 of 3 items.');

        $I->see('Boeing Name Std');
        $I->see('EC-AAA');
        $I->see('LEMD');

        $I->see('Boeing Std 1');
        $I->see('EC-DOS');
        $I->see('Boeing Std 2');
        $I->see('EC-FOS');
        $I->see('LEBL');
    }

    public function openAircraftConfigurationViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkAircraftConfigViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftConfigurationViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkAircraftConfigViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftConfigurationViewAsVisitor(\FunctionalTester $I)
    {
        $this->checkAircraftConfigViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $this->checkAircraftConfigViewCommon($I);

        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }


}