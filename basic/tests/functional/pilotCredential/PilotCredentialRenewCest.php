<?php

namespace tests\functional\pilotCredential;

use app\models\PilotCredential;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\CredentialTypePrerequisiteFixture;
use tests\fixtures\PilotCredentialFixture;

class PilotCredentialRenewCest
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

    public function openRenewAsAdmin(\FunctionalTester $I)
    {
        // id=3: John Doe IR, active with expiry
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/renew', ['id' => 3]);

        $I->seeResponseCodeIs(200);
        // issued_date must be locked (form-control-plaintext, not editable input)
        $I->seeElement('.form-control-plaintext');
        $I->dontSeeElement('#pilotcredential-issued_date');
    }

    public function openRenewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('pilot-credential/renew', ['id' => 3]);
        $I->seeResponseCodeIs(403);
    }

    public function openRenewAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot-credential/renew', ['id' => 3]);
        $I->seeCurrentUrlMatches('~login~');
    }

    public function renewBlockedByHigherLicense(\FunctionalTester $I)
    {
        // id=7: pilot 6 PPL — canRenew() = false because pilot 6 holds CPL (descendant license)
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/pilot-credential/renew?id=7', [
            'PilotCredential' => [
                'status'      => PilotCredential::STATUS_ACTIVE,
                'issued_date' => '2022-01-01',
                'expiry_date' => '2028-12-31',
                'pilot_id'    => 6,
                'issued_by'   => 2,
            ],
        ]);
        $I->seeResponseCodeIs(403);

        $ppl = PilotCredential::findOne(7);
        $I->assertNull($ppl->expiry_date);
    }

    public function renewStudentCredential(\FunctionalTester $I)
    {
        // id=2: Vfr PPL student → actionRenew guards against non-active, must return 403
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/pilot-credential/renew?id=2', [
            'PilotCredential' => [
                'status'      => PilotCredential::STATUS_ACTIVE,
                'issued_date' => '2025-03-01',
                'expiry_date' => '2028-12-31',
                'pilot_id'    => 4,
                'issued_by'   => 2,
            ],
        ]);
        $I->seeResponseCodeIs(403);
    }

    public function renewWithPastExpiry(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/renew', ['id' => 3]);

        $I->fillField('#pilotcredential-expiry_date', '2020-01-01');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Expiry date must be after today.');

        $pc = PilotCredential::findOne(3);
        $I->assertEquals('2027-01-10', $pc->expiry_date);
    }

    public function renewValidCredential(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/renew', ['id' => 3]);

        $I->fillField('#pilotcredential-expiry_date', '2028-06-01');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);

        $pc = PilotCredential::findOne(3);
        $I->assertEquals('2028-06-01', $pc->expiry_date);
        $I->assertEquals('2025-01-10', $pc->issued_date);
    }

    public function issuedDateLockedOnRenew(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/pilot-credential/renew?id=3', [
            'PilotCredential' => [
                'status'      => PilotCredential::STATUS_ACTIVE,
                'issued_date' => '1999-01-01',
                'expiry_date' => '2028-12-31',
                'pilot_id'    => 1,
                'issued_by'   => 2,
            ],
        ]);

        $pc = PilotCredential::findOne(3);
        $I->assertEquals('2025-01-10', $pc->issued_date);
    }

    public function renewLicenseCascadesRatings(\FunctionalTester $I)
    {
        // id=8: pilot 6 CPL (active, expiry='2026-06-01')
        // id=9: pilot 6 IR  (active, expiry='2025-12-31')
        // Prerequisites: IR requires PPL, CPL requires PPL
        // Renewing CPL → ancestors=[PPL], descendants of PPL=[IR,CPL] → ratings=[IR]
        // IR (id=9) should be cascade-renewed to new expiry
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/renew', ['id' => 8]);

        $I->fillField('#pilotcredential-expiry_date', '2028-01-01');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);

        $cpl = PilotCredential::findOne(8);
        $I->assertEquals('2028-01-01', $cpl->expiry_date);

        $ir = PilotCredential::findOne(9);
        $I->assertEquals('2028-01-01', $ir->expiry_date);
    }

    public function studentRatingsNotCascadeRenewed(\FunctionalTester $I)
    {
        // Insert a student IR for pilot 6 in the DB before the test
        $studentIr = new PilotCredential();
        $studentIr->pilot_id           = 6;
        $studentIr->credential_type_id = 2;
        $studentIr->status             = PilotCredential::STATUS_STUDENT;
        $studentIr->issued_date        = '2025-01-01';
        $studentIr->expiry_date        = '2025-06-01';
        $studentIr->issued_by          = 2;
        $studentIr->save(false);
        $studentIrId = $studentIr->id;

        // Delete existing active IR first (unique constraint per pilot+type)
        PilotCredential::findOne(9)->delete();

        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot-credential/renew', ['id' => 8]);

        $I->fillField('#pilotcredential-expiry_date', '2028-01-01');
        $I->click('Save', 'button');

        // Student IR must NOT be updated
        $studentAfter = PilotCredential::findOne($studentIrId);
        $I->assertEquals('2025-06-01', $studentAfter->expiry_date);
    }
}
