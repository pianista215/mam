<?php

namespace tests\functional\country;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class CountryCreateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function openCountryIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->amOnRoute('country/index');

        $I->see('Countries');
        $I->see('Showing 1-1 of 1 item');
        $I->see('Spain');
        $I->see('ES');

        $I->see('Create Country', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openCountryIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('country/index');

        $I->see('Countries');
        $I->see('Showing 1-1 of 1 item');
        $I->see('Spain');
        $I->see('ES');

        $I->dontSee('Create Country', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openCountryViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->amOnRoute('country/view', [ 'id' => '1' ]);

        $I->see('Spain');
        $I->see('ES');

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openCountryViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('country/view', [ 'id' => '1' ]);

        $I->see('Spain');
        $I->see('ES');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }


}