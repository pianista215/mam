<?php

namespace tests\functional\credentialType;

use app\models\CredentialType;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\CredentialTypePrerequisiteFixture;
use tests\fixtures\PilotCredentialFixture;

class CredentialTypeDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment'             => AuthAssignmentFixture::class,
            'credentialType'             => CredentialTypeFixture::class,
            'credentialTypePrerequisite' => CredentialTypePrerequisiteFixture::class,
            'pilotCredential'            => PilotCredentialFixture::class,
        ];
    }

    public function deleteButtonVisibleForAdmin(\FunctionalTester $I)
    {
        // id=6: NVG — no dependents, no pilots → canDelete() = true → button shown
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/view', ['id' => '6']);

        $I->see('Delete', 'a');
    }

    public function deleteButtonNotVisibleForUser(\FunctionalTester $I)
    {
        // id=6: deletable type, but user has no CREDENTIAL_CRUD → button hidden
        $I->amLoggedInAs(1);
        $I->amOnRoute('credential-type/view', ['id' => '6']);

        $I->dontSee('Delete', 'a');
    }

    public function deleteButtonNotVisibleForTypeWithDependents(\FunctionalTester $I)
    {
        // id=1: PPL — IR and CPL depend on it → canDelete() = false → button hidden even for admin
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/view', ['id' => '1']);

        $I->dontSee('Delete', 'a');
    }

    public function deleteButtonNotVisibleForTypeWithPilots(\FunctionalTester $I)
    {
        // id=2: IR — has pilot credentials → canDelete() = false → button hidden even for admin
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/view', ['id' => '2']);

        $I->dontSee('Delete', 'a');
    }

    public function deleteViaGetForbiddenAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/delete', ['id' => '6']);
        $I->seeResponseCodeIs(405);

        $count = CredentialType::find()->count();
        $I->assertEquals(6, $count);
    }

    public function deleteViaGetForbiddenAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('credential-type/delete', ['id' => '6']);
        $I->seeResponseCodeIs(405);

        $count = CredentialType::find()->count();
        $I->assertEquals(6, $count);
    }

    public function deleteViaGetRedirectsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('credential-type/delete', ['id' => '6']);
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function adminCanDeleteViaPost(\FunctionalTester $I)
    {
        // id=6: NVG — canDelete() = true (no pilots, no dependents)
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/credential-type/delete?id=6');

        $I->seeResponseCodeIsRedirection();

        $count = CredentialType::find()->where(['id' => 6])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/credential-type/delete?id=6');

        $I->seeResponseCodeIs(403);

        $count = CredentialType::find()->where(['id' => 6])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPost(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/credential-type/delete?id=6');

        $I->seeResponseCodeIsRedirection();

        $count = CredentialType::find()->where(['id' => 6])->count();
        $I->assertEquals(1, $count);
    }

    public function adminCannotDeleteTypeWithDependents(\FunctionalTester $I)
    {
        // id=1: PPL — IR (id=2) and CPL (id=3) depend on it → 403
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/credential-type/delete?id=1');

        $I->seeResponseCodeIs(403);

        $count = CredentialType::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }

    public function adminCannotDeleteTypeWithPilots(\FunctionalTester $I)
    {
        // id=2: IR — has active pilot credentials, no dependents → 403
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/credential-type/delete?id=2');

        $I->seeResponseCodeIs(403);

        $count = CredentialType::find()->where(['id' => 2])->count();
        $I->assertEquals(1, $count);
    }
}
