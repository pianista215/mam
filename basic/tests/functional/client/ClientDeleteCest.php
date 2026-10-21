<?php

namespace tests\functional\client;

use app\models\Client;
use app\models\ClientHub;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ClientHubFixture;

class ClientDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'clientHub' => ClientHubFixture::class,
        ];
    }

    public function deleteOnlyPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('client/delete', ['id' => 1]);
        $I->seeResponseCodeIs(405);
        $I->assertEquals(2, Client::find()->count());
    }

    public function adminCanDeleteViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/client/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $I->assertNull(Client::findOne(1));
        $I->assertEquals(0, ClientHub::find()->where(['client_id' => 1])->count());
    }

    public function businessManagerCanDeleteViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(7);
        $I->sendAjaxPostRequest('/client/delete?id=2');

        $I->seeResponseCodeIsRedirection();
        $I->assertNull(Client::findOne(2));
    }

    public function userCannotDeleteViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/client/delete?id=1');

        $I->seeResponseCodeIs(403);
        $I->assertNotNull(Client::findOne(1));
    }

    public function guestCannotDeleteViaPost(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/client/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $I->assertNotNull(Client::findOne(1));
    }
}
