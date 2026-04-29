<?php

namespace tests\functional\pilotCredential;

use app\models\PilotCredential;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\CredentialTypePrerequisiteFixture;
use tests\fixtures\PilotCredentialFixture;

class PilotCredentialViewCest
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

    public function viewActiveAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/view', ['id' => 3]);

        $I->seeResponseCodeIs(200);
        $I->see('Renew', 'a');
        $I->see('Revoke', 'a');
        $I->dontSee('Issue', 'a');
    }

    public function viewStudentAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/view', ['id' => 2]);

        $I->seeResponseCodeIs(200);
        $I->see('Issue', 'a');
        $I->dontSee('Renew', 'a');
    }

    public function viewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('pilot-credential/view', ['id' => 3]);

        $I->seeResponseCodeIs(200);
        $I->dontSee('Renew', 'a');
        $I->dontSee('Revoke', 'a');
        $I->dontSee('Issue', 'a');
    }

    public function viewAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot-credential/view', ['id' => 3]);
        $I->seeCurrentUrlMatches('~login~');
    }

    public function viewNonExistent(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/view', ['id' => 999]);
        $I->seeResponseCodeIs(404);
    }

    public function viewExpiredShowsBadge(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/view', ['id' => 4]);

        $I->seeResponseCodeIs(200);
        $I->see('Expired');
    }

    public function noRenewButtonWhenHigherLicenseHeld(\FunctionalTester $I)
    {
        // id=7: pilot 6 PPL — active, but pilot 6 also holds CPL (descendant license) → canRenew() = false
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/view', ['id' => 7]);

        $I->seeResponseCodeIs(200);
        $I->dontSee('Renew', 'a');
        $I->dontSee('Issue', 'a');
    }

    public function renewButtonVisibleForLowestActiveLicense(\FunctionalTester $I)
    {
        // id=1: John Doe PPL — active, no expiry, but no higher license held → canRenew() = true
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/view', ['id' => 1]);

        $I->seeResponseCodeIs(200);
        $I->see('Renew', 'a');
    }
}
