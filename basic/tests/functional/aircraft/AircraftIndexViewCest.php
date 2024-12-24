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

    public function openAircraftIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->amOnRoute('aircraft/index');

        $I->see('Aircrafts');
        $I->see('Showing 1-2 of 2 items.');

        $I->see('Boeing 737-800 (Standard)');
        $I->see('Boeing Name Std');
        $I->see('EC-AAA');
        $I->see('LEMD');

        $I->see('Boeing 737-800 (Cargo)');
        $I->see('Boeing Name Cargo');
        $I->see('EC-BBB');
        $I->see('LEBL');

        $I->see('Create Aircraft', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('aircraft/index');

        $I->see('Aircrafts');
        $I->see('Showing 1-2 of 2 items.');

        $I->see('Boeing 737-800 (Standard)');
        $I->see('Boeing Name Std');
        $I->see('EC-AAA');
        $I->see('LEMD');

        $I->see('Boeing 737-800 (Cargo)');
        $I->see('Boeing Name Cargo');
        $I->see('EC-BBB');
        $I->see('LEBL');

        $I->dontSee('Create Aircraft', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftIndexAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/index');

        $I->amOnRoute('aircraft/index');

        $I->see('Aircrafts');
        $I->see('Showing 1-2 of 2 items.');

        $I->see('Boeing 737-800 (Standard)');
        $I->see('Boeing Name Std');
        $I->see('EC-AAA');
        $I->see('LEMD');

        $I->see('Boeing 737-800 (Cargo)');
        $I->see('Boeing Name Cargo');
        $I->see('EC-BBB');
        $I->see('LEBL');

        $I->dontSee('Create Aircraft', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAircraftViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->amOnRoute('aircraft/view', [ 'id' => '1' ]);

        $I->see('Boeing Name Std');
        $I->see('Boeing 737-800 (Standard)');
        $I->see('EC-AAA');
        $I->see('LEMD');
        $I->see('255.7');

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openAircraftViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('aircraft/view', [ 'id' => '1' ]);

        $I->see('Boeing Name Std');
        $I->see('Boeing 737-800 (Standard)');
        $I->see('EC-AAA');
        $I->see('LEMD');
        $I->see('255.7');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openAircraftViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('aircraft-type/view', [ 'id' => '2' ]);

        $I->amOnRoute('aircraft/view', [ 'id' => '1' ]);

        $I->see('Boeing Name Std');
        $I->see('Boeing 737-800 (Standard)');
        $I->see('EC-AAA');
        $I->see('LEMD');
        $I->see('255.7');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }


}