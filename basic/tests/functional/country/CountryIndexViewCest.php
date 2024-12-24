<?php

namespace tests\functional\country;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class CountryIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    private function checkCountryIndexCommon(\FunctionalTester $I){
        $I->amOnRoute('country/index');

        $I->see('Countries');
        $I->see('Showing 1-1 of 1 item');
        $I->see('Spain');
        $I->see('ES');
    }

    public function openCountryIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkCountryIndexCommon($I);

        $I->see('Create Country', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openCountryIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkCountryIndexCommon($I);

        $I->dontSee('Create Country', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openCountryIndexAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('country/index');

        $this->checkCountryIndexCommon($I);

        $I->dontSee('Create Country', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    private function checkCountryViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('country/view', [ 'id' => '1' ]);

        $I->see('Spain');
        $I->see('ES');
    }

    public function openCountryViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkCountryViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openCountryViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkCountryViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openCountryViewAsVisitor(\FunctionalTester $I)
    {
        $this->checkCountryViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }


}