<?php

namespace tests\functional\airport;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class AirportIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function openAirportIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->amOnRoute('airport/index');

        $I->see('Airports');
        $I->see('Showing 1-1 of 1 item');
        $I->see('LEMD');
        $I->see('Madrid-Barajas');

        $I->see('Create Airport', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAirportIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('airport/index');

        $I->see('Airports');
        $I->see('Showing 1-1 of 1 item');
        $I->see('LEMD');
        $I->see('Madrid-Barajas');

        $I->dontSee('Create Airport', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAirportIndexAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('airport/index');

        $I->see('Airports');
        $I->see('Showing 1-1 of 1 item');
        $I->see('LEMD');
        $I->see('Madrid-Barajas');

        $I->dontSee('Create Airport', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAirportViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $I->amOnRoute('airport/view', [ 'id' => '1' ]);

        $I->see('LEMD');
        $I->see('Madrid-Barajas');
        $I->see('Madrid');
        $I->see('40.471926');
        $I->see('-3.56264');

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openAirportViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('airport/view', [ 'id' => '1' ]);

        $I->see('LEMD');
        $I->see('Madrid-Barajas');
        $I->see('Madrid');
        $I->see('40.471926');
        $I->see('-3.56264');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openAirportViewAsVisitor(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('airport/view', [ 'id' => '1' ]);

        $I->see('LEMD');
        $I->see('Madrid-Barajas');
        $I->see('Madrid');
        $I->see('40.471926');
        $I->see('-3.56264');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }


}