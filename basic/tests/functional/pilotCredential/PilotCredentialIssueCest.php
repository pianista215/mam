<?php

namespace tests\functional\pilotCredential;

use app\models\PilotCredential;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\CredentialTypePrerequisiteFixture;
use tests\fixtures\PilotCredentialFixture;

class PilotCredentialIssueCest
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

    public function openIssueAsAdmin(\FunctionalTester $I)
    {
        // Pilot 7 has PPL → CPL (prereq met) should appear; PPL should not (already has it)
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/issue', ['pilotId' => 7]);

        $I->seeResponseCodeIs(200);
        $I->see('CPL');
        $I->dontSee('PPL', 'option');
    }

    public function openIssueAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('pilot-credential/issue', ['pilotId' => 7]);
        $I->seeResponseCodeIs(403);
    }

    public function openIssueAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot-credential/issue', ['pilotId' => 7]);
        $I->seeCurrentUrlMatches('~login~');
    }

    public function submitEmpty(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/issue', ['pilotId' => 7]);
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);

        $count = PilotCredential::find()->count();
        $I->assertEquals(10, $count);
    }

    public function submitValidActiveLicense(\FunctionalTester $I)
    {
        // Pilot 7 has PPL with expiry='2026-06-01'; issuing CPL (active) should clear PPL expiry
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/issue', ['pilotId' => 7]);

        $I->selectOption('#pilotcredential-credential_type_id', 3);
        $I->selectOption('[name="PilotCredential[status]"]', PilotCredential::STATUS_ACTIVE);
        $I->fillField('#pilotcredential-issued_date', '2026-01-01');
        $I->fillField('#pilotcredential-expiry_date', '2028-12-31');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);

        $cpl = PilotCredential::find()->where(['pilot_id' => 7, 'credential_type_id' => 3])->one();
        $I->assertNotNull($cpl);
        $I->assertEquals(PilotCredential::STATUS_ACTIVE, (int)$cpl->status);

        // PPL expiry must be cleared (ancestor license)
        $ppl = PilotCredential::find()->where(['pilot_id' => 7, 'credential_type_id' => 1])->one();
        $I->assertNull($ppl->expiry_date);
    }

    public function submitStudentLicense(\FunctionalTester $I)
    {
        // Issuing MNPS as student to pilot 7 — no prerequisites, no ancestor clearing
        $pplBefore = PilotCredential::find()->where(['pilot_id' => 7, 'credential_type_id' => 1])->one();
        $expiryBefore = $pplBefore->expiry_date;

        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/issue', ['pilotId' => 7]);

        $I->selectOption('#pilotcredential-credential_type_id', 4);
        $I->selectOption('[name="PilotCredential[status]"]', PilotCredential::STATUS_STUDENT);
        $I->fillField('#pilotcredential-issued_date', '2026-01-01');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);

        $mnps = PilotCredential::find()->where(['pilot_id' => 7, 'credential_type_id' => 4])->one();
        $I->assertNotNull($mnps);
        $I->assertEquals(PilotCredential::STATUS_STUDENT, (int)$mnps->status);

        // PPL expiry must be unchanged (student issue does not clear ancestors)
        $pplAfter = PilotCredential::find()->where(['pilot_id' => 7, 'credential_type_id' => 1])->one();
        $I->assertEquals($expiryBefore, $pplAfter->expiry_date);
    }

    public function submitStudentWhenPrerequisiteIsStudent(\FunctionalTester $I)
    {
        // Pilot 4 (Vfr Validator) has PPL as STUDENT only (id=2). CPL requires PPL.
        // Since only a STUDENT PPL exists, CPL must be issued as STUDENT.
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/issue', ['pilotId' => 4]);

        $I->selectOption('#pilotcredential-credential_type_id', 3);
        $I->selectOption('[name="PilotCredential[status]"]', PilotCredential::STATUS_STUDENT);
        $I->fillField('#pilotcredential-issued_date', '2026-01-01');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);

        $cpl = PilotCredential::find()->where(['pilot_id' => 4, 'credential_type_id' => 3])->one();
        $I->assertNotNull($cpl);
        $I->assertEquals(PilotCredential::STATUS_STUDENT, (int)$cpl->status);
    }

    public function submitActiveBlockedByStudentPrerequisite(\FunctionalTester $I)
    {
        // Pilot 4 has only STUDENT PPL. Selecting CPL as ACTIVE must be rejected server-side.
        // JS would disable the Active radio in a browser, but functional tests bypass JS.
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/issue', ['pilotId' => 4]);

        $I->selectOption('#pilotcredential-credential_type_id', 3);
        $I->selectOption('[name="PilotCredential[status]"]', PilotCredential::STATUS_ACTIVE);
        $I->fillField('#pilotcredential-issued_date', '2026-01-01');
        $I->fillField('#pilotcredential-expiry_date', '2028-12-31');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('This credential can only be issued as Student because all prerequisites are held as Student.');

        $cpl = PilotCredential::find()->where(['pilot_id' => 4, 'credential_type_id' => 3])->one();
        $I->assertNull($cpl);
    }

    public function prerequisitesNotMetPostInjection(\FunctionalTester $I)
    {
        // Pilot 8 has no credentials → injecting CPL via POST should fail server-side validation.
        // CPL does not appear in the dropdown for pilot 8, so direct AJAX POST is used to bypass the form.
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/pilot-credential/issue?pilotId=8', [
            'PilotCredential' => [
                'credential_type_id' => 3,
                'status'             => PilotCredential::STATUS_ACTIVE,
                'issued_date'        => '2026-01-01',
                'expiry_date'        => '2028-12-31',
                'pilot_id'           => 8,
                'issued_by'          => 2,
            ],
        ]);

        $I->seeResponseCodeIs(200);

        $count = PilotCredential::find()->count();
        $I->assertEquals(10, $count);
    }
}
