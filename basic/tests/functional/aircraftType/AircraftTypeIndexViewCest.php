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

    public function openAircraftTypeIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->amOnRoute('aircraft-type/index');

        $I->see('Aircraft Types');
        $I->see('Showing 1-3 of 3 item');
        $I->see('A320');
        $I->see('Airbus A320');
        $I->see('B738');
        $I->see('Boeing 737-800');
        $I->see('B350');
        $I->see('Beechcraft King Air 350i');

        $I->see('Create Aircraft Type', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftTypeIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('aircraft-type/index');

        $I->see('Aircraft Types');
        $I->see('Showing 1-3 of 3 item');
        $I->see('A320');
        $I->see('Airbus A320');
        $I->see('B738');
        $I->see('Boeing 737-800');
        $I->see('B350');
        $I->see('Beechcraft King Air 350i');

        $I->dontSee('Create Aircraft Type', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftTypeIndexAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/index');

        $I->see('Aircraft Types');
        $I->see('Showing 1-3 of 3 item');
        $I->see('A320');
        $I->see('Airbus A320');
        $I->see('B738');
        $I->see('Boeing 737-800');
        $I->see('B350');
        $I->see('Beechcraft King Air 350i');

        $I->dontSee('Create AircraftType', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftTypeViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->amOnRoute('aircraft-type/view', [ 'id' => '2' ]);

        $I->see('B738');
        $I->see('Boeing 737-800');
        $I->see('5665');

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->see('Configurations');
        $I->see('Showing 1-2 of 2 items.');
        $I->see('Standard');
        $I->see('160');
        $I->see('4900');
        $I->see('Cargo');
        $I->see('0');
        $I->see('23500');

        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftTypeViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('aircraft-type/view', [ 'id' => '2' ]);

        $I->see('B738');
        $I->see('Boeing 737-800');
        $I->see('5665');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->see('Configurations');
        $I->see('Showing 1-2 of 2 items.');
        $I->see('Standard');
        $I->see('160');
        $I->see('4900');
        $I->see('Cargo');
        $I->see('0');
        $I->see('23500');

        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftTypeViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/view', [ 'id' => '2' ]);

        $I->see('B738');
        $I->see('Boeing 737-800');
        $I->see('5665');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->see('Configurations');
        $I->see('Showing 1-2 of 2 items.');
        $I->see('Standard');
        $I->see('160');
        $I->see('4900');
        $I->see('Cargo');
        $I->see('0');
        $I->see('23500');

        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }


}