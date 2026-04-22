<?php

namespace tests\functional\pilotCredential;

use app\models\PilotCredential;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\CredentialTypePrerequisiteFixture;
use tests\fixtures\PilotCredentialFixture;

class PilotCredentialRevokeCest
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

    public function revokeViaGetForbidden(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/revoke', ['id' => 3]);
        $I->seeResponseCodeIs(405);

        $I->assertNotNull(PilotCredential::findOne(3));
    }

    public function revokeAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/pilot-credential/revoke?id=3');
        $I->seeResponseCodeIs(403);

        $I->assertNotNull(PilotCredential::findOne(3));
    }

    public function revokeAsGuest(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/pilot-credential/revoke?id=3');
        $I->seeResponseCodeIsRedirection();

        $I->assertNotNull(PilotCredential::findOne(3));
    }

    public function revokeRating(\FunctionalTester $I)
    {
        // id=3: John Doe IR (rating — always revocable)
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/pilot-credential/revoke?id=3');
        $I->seeResponseCodeIsRedirection();

        $I->assertNull(PilotCredential::findOne(3));

        $count = PilotCredential::find()->where(['pilot_id' => 1])->count();
        $I->assertEquals(1, $count);
    }

    public function revokeBlockedLicense(\FunctionalTester $I)
    {
        // id=7: pilot 6 PPL — cannot revoke because pilot 6 holds CPL (id=8), a descendant license
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/pilot-credential/revoke?id=7');
        $I->seeResponseCodeIs(403);

        $I->assertNotNull(PilotCredential::findOne(7));
    }

    public function revokeLicenseCascades(\FunctionalTester $I)
    {
        // id=8: pilot 6 CPL (expiry='2026-06-01') — revoking cascades to IR (id=9); PPL (id=7) must survive
        // CPL's expiry_date must be restored to ancestor PPL (id=7)
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/pilot-credential/revoke?id=8');
        $I->seeResponseCodeIsRedirection();

        $I->assertNull(PilotCredential::findOne(8));
        $I->assertNull(PilotCredential::findOne(9));

        $ppl = PilotCredential::findOne(7);
        $I->assertNotNull($ppl);
        $I->assertEquals('2026-06-01', $ppl->expiry_date);
    }

    public function revokeViewShowsCascadeNames(\FunctionalTester $I)
    {
        // id=8: pilot 6 CPL — view should show IR in the revoke confirm message
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/view', ['id' => 8]);

        $I->seeResponseCodeIs(200);
        $I->seeInSource('IR — Instrument Rating');
    }
}
