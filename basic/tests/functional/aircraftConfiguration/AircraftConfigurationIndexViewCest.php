<?php

namespace tests\functional\aircraftConfiguration;

use tests\fixtures\AircraftConfigurationFixture;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AircraftConfigurationIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraftConfiguration' => AircraftConfigurationFixture::class,
        ];
    }

    public function openAircraftConfigurationIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->amOnRoute('aircraft-configuration/index');

        $I->see('Aircraft Configurations');
        $I->see('Showing 1-2 of 2 items.');
        $I->see('Boeing 737-800');
        $I->see('Cargo');
        $I->see('Standard');

        $I->see('Create Aircraft Configuration', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftConfigurationIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('aircraft-configuration/index');

        $I->see('Aircraft Configurations');
        $I->see('Showing 1-2 of 2 items.');
        $I->see('Boeing 737-800');
        $I->see('Cargo');
        $I->see('Standard');

        $I->dontSee('Create Aircraft Configuration', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftConfigurationIndexAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/index');

        $I->amOnRoute('aircraft-configuration/index');

        $I->see('Aircraft Configurations');
        $I->see('Showing 1-2 of 2 items.');
        $I->see('Boeing 737-800');
        $I->see('Cargo');
        $I->see('Standard');

        $I->dontSee('Create Aircraft Configuration', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftConfigurationViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->amOnRoute('aircraft-configuration/view', [ 'id' => '1' ]);

        $I->see('Aircraft Type Name');
        $I->see('Boeing 737-800');
        $I->see('Standard');
        $I->see('160');
        $I->see('4900');

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openAircraftConfigurationViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('aircraft-configuration/view', [ 'id' => '1' ]);

        $I->see('Aircraft Type Name');
        $I->see('Boeing 737-800');
        $I->see('Standard');
        $I->see('160');
        $I->see('4900');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openAircraftConfigurationViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/view', [ 'id' => '2' ]);

        $I->amOnRoute('aircraft-configuration/view', [ 'id' => '1' ]);

        $I->see('Aircraft Type Name');
        $I->see('Boeing 737-800');
        $I->see('Standard');
        $I->see('160');
        $I->see('4900');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }


}