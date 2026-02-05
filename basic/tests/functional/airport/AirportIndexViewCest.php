<?php

namespace tests\functional\airport;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\RunwayEndFixture;
use Yii;

class AirportIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'runwayEnd' => RunwayEndFixture::class,
        ];
    }

    private function checkAirportIndexCommon(\FunctionalTester $I){
        $I->amOnRoute('airport/index');

        $I->see('Airports');
        $I->see('Showing 1-6 of 6 items.');
        $I->see('LEMD');
        $I->see('Madrid-Barajas');
        $I->see('LEBL');
        $I->see('Barcelona-El Prat');
        $I->see('LEVC');
        $I->see('Valencia-Manises');
        $I->see('GCLP');
        $I->see('Gran Canaria');
        $I->see('LEAL');
        $I->see('Alicante');
        $I->see('LEZG');
        $I->see('Zaragoza');
    }

    public function openAirportIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkAirportIndexCommon($I);

        $I->see('Create Airport', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAirportIndexAsAirportManager(\FunctionalTester $I)
    {
        $I->amLoggedInAs(12);

        $this->checkAirportIndexCommon($I);

        $I->see('Create Airport', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openAirportIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkAirportIndexCommon($I);

        $I->dontSee('Create Airport', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openAirportIndexAsVisitor(\FunctionalTester $I)
    {
        $this->checkAirportIndexCommon($I);

        $I->dontSee('Create Airport', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    private function checkAirportViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('airport/view', [ 'id' => '1' ]);

        $I->see('LEMD');
        $I->see('Madrid-Barajas');
        $I->see('Madrid');
        $I->see('40.471926');
        $I->see('-3.56264');
    }

    public function openAirportViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkAirportViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openAirportViewAsAirportManager(\FunctionalTester $I)
    {
        $I->amLoggedInAs(12);

        $this->checkAirportViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openAirportViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkAirportViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openAirportViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('airport/view', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openAirportViewWithRunways(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('airport/view', ['id' => '1']); // LEMD has runways

        $I->see('Runways');
        $I->see('14L');
        $I->see('32R');
        $I->see('18L');
        $I->see('36R');
        $I->see('142');
        $I->see('322');
        $I->see('180');
        $I->see('360');
        $I->seeElement('table.table-striped');
    }

    public function openAirportViewWithoutRunways(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('airport/view', ['id' => '2']); // LEBL has no runways

        $I->see('Barcelona-El Prat');
        $I->dontSee('Runways');
        $I->dontSeeElement('.airport-runways');
    }
}