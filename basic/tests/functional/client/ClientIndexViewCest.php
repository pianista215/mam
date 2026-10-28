<?php

namespace tests\functional\client;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ClientHubFixture;

class ClientIndexViewCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'clientHub' => ClientHubFixture::class,
        ];
    }

    public function guestCannotSeeIndex(\FunctionalTester $I)
    {
        $I->amOnRoute('client/index');
        $I->seeCurrentUrlMatches('~login~');
        $I->dontSee('Iberian Logistics');
    }

    public function guestCannotSeeView(\FunctionalTester $I)
    {
        $I->amOnRoute('client/view', ['id' => 1]);
        $I->seeCurrentUrlMatches('~login~');
    }

    public function guestDoesNotSeeMenuEntry(\FunctionalTester $I)
    {
        $I->amOnRoute('site/login');
        $I->dontSeeLink('Clients');
    }

    public function userSeesMenuEntry(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('rank/index');
        $I->seeLink('Clients', '/client/index');
    }

    public function userSeesIndexWithoutCrudButtons(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/index');

        $I->seeResponseCodeIs(200);
        $I->see('Clients', 'h1');
        $I->see('Iberian Logistics');
        $I->see('Canary Fresh Fish');
        $I->see('LEBL');
        $I->see('LEVC');
        $I->dontSee('Create Client');
        $I->dontSeeElement('a[title="Update"]');
        $I->dontSeeElement('a[title="Delete"]');
    }

    public function indexIsSortedByNameByDefault(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/index');

        $source = $I->grabPageSource();
        $I->assertLessThan(strpos($source, 'Iberian Logistics'), strpos($source, 'Canary Fresh Fish'));
        $I->seeElement('a[data-sort="-name"]');
        $I->seeElement('a[data-sort="headquarters_icao"]');
    }

    public function indexCanBeSortedByNameDescending(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/index', ['sort' => '-name']);

        $source = $I->grabPageSource();
        $I->assertLessThan(strpos($source, 'Canary Fresh Fish'), strpos($source, 'Iberian Logistics'));
    }

    public function indexShowsHeadquartersIcaoOnly(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/index');

        $I->see('LEMD');
        $I->dontSee('Madrid-Barajas');
    }

    public function indexFiltersByHeadquarters(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/index', ['ClientSearch' => ['headquarters_icao' => 'GCLP']]);

        $I->see('Canary Fresh Fish');
        $I->dontSee('Iberian Logistics');
    }

    public function indexFiltersByHub(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/index', ['ClientSearch' => ['hub_icao' => 'LEBL']]);

        $I->see('Iberian Logistics');
        $I->dontSee('Canary Fresh Fish');
    }

    public function indexHubFilterIgnoresHeadquarters(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/index', ['ClientSearch' => ['hub_icao' => 'LEMD']]);

        $I->dontSee('Iberian Logistics');
        $I->dontSee('Canary Fresh Fish');
    }

    public function userSeesViewWithoutCrudButtons(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/view', ['id' => 1]);

        $I->seeResponseCodeIs(200);
        $I->see('Iberian Logistics', 'h1');
        $I->see('Headquarters');
        $I->see('LEMD - Madrid-Barajas');
        $I->see('LEBL - Barcelona-El Prat');
        $I->see('LEVC - Valencia-Manises');
        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function viewOfClientWithoutHubs(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/view', ['id' => 2]);

        $I->see('Canary Fresh Fish', 'h1');
        $I->see('This client has no hubs.');
    }

    public function viewNonExistentClient(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/view', ['id' => 999]);
        $I->seeResponseCodeIs(404);
    }

    public function adminSeesCrudButtons(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('client/index');
        $I->see('Create Client');

        $I->amOnRoute('client/view', ['id' => 1]);
        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function businessManagerSeesCrudButtons(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->amOnRoute('client/index');
        $I->see('Create Client');

        $I->amOnRoute('client/view', ['id' => 1]);
        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }
}
