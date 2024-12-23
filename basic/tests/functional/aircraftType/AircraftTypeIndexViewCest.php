<?php

namespace tests\functional\aircraftType;

use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftTypeIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraftType' => AircraftTypeFixture::class,
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
        $I->see('Boeing 737-800 ER');
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
        $I->see('Boeing 737-800 ER');
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
        $I->see('Boeing 737-800 ER');
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

        $I->amOnRoute('aircraft-type/view', [ 'id' => '1' ]);

        $I->see('A320');
        $I->see('Airbus A320');
        $I->see('3186');

        // TODO: Add configurations and aircrafts to test view
        $I->see('No results found');

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openAircraftTypeViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('aircraft-type/view', [ 'id' => '1' ]);

        $I->see('A320');
        $I->see('Airbus A320');
        $I->see('3186');

        // TODO: Add configurations and aircrafts to test view
        $I->see('No results found');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openAircraftTypeViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/view', [ 'id' => '1' ]);

        $I->see('A320');
        $I->see('Airbus A320');
        $I->see('3186');

        // TODO: Add configurations and aircrafts to test view
        $I->see('No results found');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }


}