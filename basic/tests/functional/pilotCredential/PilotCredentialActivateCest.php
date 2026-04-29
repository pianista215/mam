<?php

namespace tests\functional\pilotCredential;

use app\models\PilotCredential;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\CredentialTypePrerequisiteFixture;
use tests\fixtures\PilotCredentialFixture;

class PilotCredentialActivateCest
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

    public function openActivateAsAdmin(\FunctionalTester $I)
    {
        // id=6: pilot 5, CPL student
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/activate', ['id' => 6]);

        $I->seeResponseCodeIs(200);
        // issued_date must be an editable input, not form-control-plaintext
        $I->seeElement('#pilotcredential-issued_date');
        $I->dontSeeElement('.form-control-plaintext');
    }

    public function openActivateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('pilot-credential/activate', ['id' => 6]);
        $I->seeResponseCodeIs(403);
    }

    public function openActivateAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot-credential/activate', ['id' => 6]);
        $I->seeCurrentUrlMatches('~login~');
    }

    public function activateStudentCredential(\FunctionalTester $I)
    {
        // id=2: Vfr Validator, PPL, Student → activate to Active
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/activate', ['id' => 2]);

        $I->fillField('#pilotcredential-issued_date', '2026-04-20');
        $I->fillField('#pilotcredential-expiry_date', '2028-12-31');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);

        $pc = PilotCredential::findOne(2);
        $I->assertEquals(PilotCredential::STATUS_ACTIVE, (int)$pc->status);
        $I->assertEquals('2026-04-20', $pc->issued_date);
    }

    public function activateClearsAncestorLicenseExpiry(\FunctionalTester $I)
    {
        // id=6: pilot 5 CPL student. Pilot 5 has PPL (id=5) with expiry='2026-12-31'.
        // After activating CPL, PPL expiry should be null.
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/activate', ['id' => 6]);

        $I->fillField('#pilotcredential-issued_date', '2026-04-20');
        $I->fillField('#pilotcredential-expiry_date', '2028-12-31');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);

        $ppl = PilotCredential::findOne(5);
        $I->assertNull($ppl->expiry_date);
    }

    public function activateAlreadyActive(\FunctionalTester $I)
    {
        // id=3: John Doe IR, active → activate must return 403
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/pilot-credential/activate?id=3', [
            'PilotCredential' => [
                'status'      => PilotCredential::STATUS_ACTIVE,
                'issued_date' => '2026-04-20',
                'expiry_date' => '2028-12-31',
                'pilot_id'    => 1,
                'issued_by'   => 2,
            ],
        ]);
        $I->seeResponseCodeIs(403);
    }

    public function activateNonExistent(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/activate', ['id' => 999]);
        $I->seeResponseCodeIs(404);
    }

    public function activateStatusInjectionForcedToActive(\FunctionalTester $I)
    {
        // id=2: Vfr Validator, PPL, Student. POST injects status=student to bypass activation.
        // The controller must ignore it and store STATUS_ACTIVE regardless.
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/pilot-credential/activate?id=2', [
            'PilotCredential' => [
                'status'      => PilotCredential::STATUS_STUDENT,
                'issued_date' => '2026-04-20',
                'expiry_date' => '2028-12-31',
                'pilot_id'    => 4,
                'issued_by'   => 2,
            ],
        ]);

        $pc = PilotCredential::findOne(2);
        $I->assertEquals(PilotCredential::STATUS_ACTIVE, (int)$pc->status);
    }
}
