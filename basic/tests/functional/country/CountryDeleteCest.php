<?php

namespace tests\functional\country;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class CountryDeleteCest
{

    // TODO: Create acceptance tests to delete (lack of JS Support, POST not available in codeception API)

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function deleteCountryAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('country/view', [ 'id' => '1' ]);

        $I->see('Delete');
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('country/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('country/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
    }

    public function deleteOnlyPostAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('country/delete', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(405);
    }

}