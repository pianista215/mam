<?php

namespace tests\functional\credentialType;

use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\PilotCredentialFixture;

class CredentialTypeIndexViewCest
{
    public function _fixtures()
    {
        return [
            'authAssignment'  => AuthAssignmentFixture::class,
            'credentialType'  => CredentialTypeFixture::class,
            'pilotCredential' => PilotCredentialFixture::class,
            'aircraftType'    => AircraftTypeFixture::class,
        ];
    }

    private function checkIndexCommon(\FunctionalTester $I)
    {
        $I->amOnRoute('credential-type/index');

        $I->see('Credential Types');
        $I->see('PPL');
        $I->see('Private Pilot License');
        $I->see('IR');
        $I->see('Instrument Rating');
        $I->see('CPL');
        $I->see('Commercial Pilot License');
        $I->see('MNPS');
        $I->see('MNPS Certification');
        $I->see('License');
        $I->see('Rating');
        $I->see('Certification');
    }

    public function openIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkIndexCommon($I);

        $I->see('Create Credential Type', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkIndexCommon($I);

        $I->dontSee('Create Credential Type', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openIndexAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('credential-type/index');
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/view', ['id' => '3']);

        $I->see('Commercial Pilot License');
        $I->see('CPL');
        $I->see('License');
        $I->see('Allows commercial operations as PIC.');

        $I->see('Update', 'a');

        $I->see('Prerequisites');
        $I->see('Unlocked Credentials');
        $I->see('Unlocked Aircraft Types');
        $I->see('Pilots');
    }

    public function openViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('credential-type/view', ['id' => '3']);

        $I->see('Commercial Pilot License');
        $I->see('CPL');

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('credential-type/view', ['id' => '1']);
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openViewWithPilots(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/view', ['id' => '1']);

        $I->see('Private Pilot License');

        // Both current records appear
        $I->see('John Doe');
        $I->see('Vfr Validator');
        $I->see('Active');
        $I->see('Student');
        $I->see('2024-01-15');
        $I->see('2025-03-01');

        // Superseded record must not appear
        $I->dontSee('Superseded by renewal');
    }

    public function openViewWithExpiredBadge(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/view', ['id' => '2']);

        $I->see('Instrument Rating');

        // John Doe: Active with future expiry
        $I->see('John Doe');
        $I->see('Active');
        $I->see('2027-01-10');

        // Vfr Validator: Active but expiry in the past → Expired badge
        $I->see('Vfr Validator');
        $I->see('Expired');
        $I->see('2023-05-01');
    }

    public function openViewNonExistent(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/view', ['id' => '999']);
        $I->seeResponseCodeIs(404);
    }
}
