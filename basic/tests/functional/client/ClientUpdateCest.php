<?php

namespace tests\functional\client;

use app\models\Client;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ClientHubFixture;

class ClientUpdateCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'clientHub' => ClientHubFixture::class,
        ];
    }

    public function openUpdateAsAdminShowsCurrentHubs(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('client/update', ['id' => 1]);

        $I->see('Update Client: Iberian Logistics');
        $I->seeInField('#client-name', 'Iberian Logistics');
        $I->seeInField('#client-headquarters_icao', 'LEMD');
        $I->seeElement('input[name="hubIcaos[]"][value="LEBL"]');
        $I->seeElement('input[name="hubIcaos[]"][value="LEVC"]');
    }

    public function openUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('client/update', ['id' => 1]);
        $I->seeResponseCodeIs(403);
    }

    public function openUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('client/update', ['id' => 1]);
        $I->seeCurrentUrlMatches('~login~');
    }

    public function updateKeepsHubsWhenSubmittingForm(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->amOnRoute('client/update', ['id' => 1]);

        $I->fillField('#client-name', 'Iberian Cargo');
        $I->click('Save', 'button');

        $I->see('Iberian Cargo', 'h1');
        $model = Client::findOne(1);
        $I->assertEquals('Iberian Cargo', $model->name);
        $I->assertEquals(['LEBL', 'LEVC'], $model->getHubIcaoList());
    }

    public function updateReplacesHubs(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/client/update?id=1', [
            'Client' => ['name' => 'Iberian Logistics', 'headquarters_icao' => 'LEMD'],
            'hubIcaos' => ['GCLP'],
        ]);

        $I->seeResponseCodeIsRedirection();
        $I->assertEquals(['GCLP'], Client::findOne(1)->getHubIcaoList());
    }

    public function updateRemovesAllHubs(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/client/update?id=1', [
            'Client' => ['name' => 'Iberian Logistics', 'headquarters_icao' => 'LEMD'],
        ]);

        $I->seeResponseCodeIsRedirection();
        $I->assertEquals([], Client::findOne(1)->getHubIcaoList());
    }

    public function userCannotUpdateViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/client/update?id=1', [
            'Client' => ['name' => 'Hacked'],
        ]);

        $I->seeResponseCodeIs(403);
        $I->assertEquals('Iberian Logistics', Client::findOne(1)->name);
    }
}
