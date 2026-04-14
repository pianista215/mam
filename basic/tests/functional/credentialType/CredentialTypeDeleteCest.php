<?php

namespace tests\functional\credentialType;

use app\models\CredentialType;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeFixture;

class CredentialTypeDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'credentialType' => CredentialTypeFixture::class,
        ];
    }

    public function deleteButtonVisibleForAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/view', ['id' => '1']);

        $I->see('Delete', 'a');
    }

    public function deleteButtonNotVisibleForUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('credential-type/view', ['id' => '1']);

        $I->dontSee('Delete', 'a');
    }

    public function deleteViaGetForbiddenAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);

        $count = CredentialType::find()->count();
        $I->assertEquals(4, $count);
    }

    public function deleteViaGetForbiddenAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('credential-type/delete', ['id' => '1']);
        $I->seeResponseCodeIs(405);

        $count = CredentialType::find()->count();
        $I->assertEquals(4, $count);
    }

    public function deleteViaGetRedirectsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('credential-type/delete', ['id' => '1']);
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function adminCanDeleteViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/credential-type/delete?id=3');

        $I->seeResponseCodeIsRedirection();

        $count = CredentialType::find()->where(['id' => 3])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/credential-type/delete?id=3');

        $I->seeResponseCodeIs(403);

        $count = CredentialType::find()->where(['id' => 3])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPost(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/credential-type/delete?id=3');

        $I->seeResponseCodeIsRedirection();

        $count = CredentialType::find()->where(['id' => 3])->count();
        $I->assertEquals(1, $count);
    }
}
