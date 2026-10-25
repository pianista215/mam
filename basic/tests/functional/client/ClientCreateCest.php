<?php

namespace tests\functional\client;

use app\models\Client;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ClientHubFixture;

class ClientCreateCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'clientHub' => ClientHubFixture::class,
        ];
    }

    public function openCreateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('client/create');

        $I->see('Create Client');
        $I->see('Save', 'button');
    }

    public function openCreateAsBusinessManager(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->amOnRoute('client/create');

        $I->seeResponseCodeIs(200);
        $I->see('Create Client');
    }

    public function openCreateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/create');

        $I->seeResponseCodeIs(403);
        $I->dontSee('Save', 'button');
    }

    public function openCreateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('client/create');
        $I->seeCurrentUrlMatches('~login~');
    }

    public function submitEmptyClient(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('client/create');
        $I->click('Save', 'button');

        $I->see('Name cannot be blank.');
        $I->see('Headquarters cannot be blank.');
        $I->assertEquals(2, Client::find()->count());
    }

    public function submitValidClientWithoutHubs(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->amOnRoute('client/create');

        $I->fillField('#client-name', 'Valencia Orange Co');
        $I->fillField('#client-headquarters_icao', 'levc');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Valencia Orange Co', 'h1');
        $I->see('This client has no hubs.');

        $model = Client::findOne(['name' => 'Valencia Orange Co']);
        $I->assertNotNull($model);
        $I->assertEquals('LEVC', $model->headquarters_icao);
        $I->assertEquals(3, Client::find()->count());
    }

    public function submitValidClientWithHubs(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        // sendAjaxPostRequest sends exactly the specified payload (hub inputs are added by JS)
        $I->sendAjaxPostRequest('/client/create', [
            'Client' => ['name' => 'Valencia Orange Co', 'headquarters_icao' => 'LEVC'],
            'hubIcaos' => ['LEMD', 'lebl', 'LEVC'],
        ]);

        $I->seeResponseCodeIsRedirection();
        $model = Client::findOne(['name' => 'Valencia Orange Co']);
        $I->assertNotNull($model);
        $I->assertEquals(['LEBL', 'LEMD'], $model->getHubIcaoList());
    }

    public function submitUnknownHub(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/client/create', [
            'Client' => ['name' => 'Valencia Orange Co', 'headquarters_icao' => 'LEVC'],
            'hubIcaos' => ['ZZZZ'],
        ]);

        $I->seeResponseCodeIs(200);
        // Ajax requests don't fill the page history, so check the raw response
        $I->assertStringContainsString('Unknown hub airports: ZZZZ', $I->grabPageSource());
        $I->assertEquals(2, Client::find()->count());
    }

    public function submitUnknownHeadquarters(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('client/create');

        $I->fillField('#client-name', 'Ghost Corp');
        $I->fillField('#client-headquarters_icao', 'XXXX');
        $I->click('Save', 'button');

        $I->see('Headquarters is invalid.');
        $I->assertEquals(2, Client::find()->count());
    }

    public function submitDuplicateName(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('client/create');

        $I->fillField('#client-name', 'Iberian Logistics');
        $I->fillField('#client-headquarters_icao', 'LEBL');
        $I->click('Save', 'button');

        $I->see('Name "Iberian Logistics" has already been taken.');
        $I->assertEquals(2, Client::find()->count());
    }

    public function userCannotCreateViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/client/create', [
            'Client' => ['name' => 'Hacker Corp', 'headquarters_icao' => 'LEMD'],
        ]);

        $I->seeResponseCodeIs(403);
        $I->assertEquals(2, Client::find()->count());
    }
}
