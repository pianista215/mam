<?php

namespace tests\functional\aircraftType;

use tests\fixtures\AircraftConfigurationFixture;
use tests\fixtures\AircraftTypeResourceFixture;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ConfigFixture;
use tests\fixtures\CredentialTypeAircraftTypeFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\PilotCredentialFixture;

/**
 * Tests for aircraft type resource upload/download/delete.
 *
 * Pilot reference (from fixtures):
 *  pilot 2  → ADMIN (can always manage resources)
 *  pilot 6  → PILOT with B738 Type Rating (can view resources for aircraft_type_id=2)
 *  pilot 8  → PILOT with no credentials linked to any aircraft type
 *  pilot 9  → PILOT + FLEET_MANAGER + AIRCRAFT_TYPE_RESOURCE_MANAGER (can manage resources)
 *
 * Aircraft type 2 = B738 (requires credential_type_id=5 = B738 Type Rating)
 */
class AircraftTypeResourceCest
{
    private string $resourceFilePath = '/tmp/mam_test_files/aircraft_type/2/b738_qrh_test.pdf';

    public function _fixtures(): array
    {
        return [
            'authAssignment'             => AuthAssignmentFixture::class,
            'aircraftConfiguration'      => AircraftConfigurationFixture::class,
            'credentialType'             => CredentialTypeFixture::class,
            'credentialTypeAircraftType' => CredentialTypeAircraftTypeFixture::class,
            'pilotCredential'            => PilotCredentialFixture::class,
            'aircraftTypeResource'       => AircraftTypeResourceFixture::class,
            'config'                     => ConfigFixture::class,
        ];
    }

    public function _before(\FunctionalTester $I): void
    {
        $dir = dirname($this->resourceFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->resourceFilePath, 'dummy test content');
    }

    public function _after(\FunctionalTester $I): void
    {
        if (file_exists($this->resourceFilePath)) {
            unlink($this->resourceFilePath);
        }
    }

    // --- View section visibility ---

    public function resourceManagerSeesResourceSectionWithUploadForm(\FunctionalTester $I): void
    {
        $I->amLoggedInAs(9);
        $I->amOnRoute('aircraft-type/view', ['id' => 2]);
        $I->seeResponseCodeIs(200);
        $I->see('Resources');
        $I->see('B738_QRH.pdf');
        $I->seeElement('input[name="file"]');
    }

    public function credentialedPilotSeesResourceSectionWithoutUploadForm(\FunctionalTester $I): void
    {
        // Pilot 6 has B738 Type Rating → can view resources for B738 (aircraft_type_id=2)
        $I->amLoggedInAs(6);
        $I->amOnRoute('aircraft-type/view', ['id' => 2]);
        $I->seeResponseCodeIs(200);
        $I->see('Resources');
        $I->see('B738_QRH.pdf');
        $I->dontSeeElement('input[name="file"]');
    }

    public function uncredentialedPilotDoesNotSeeResourceSection(\FunctionalTester $I): void
    {
        // Pilot 8 has no credentials linked to any aircraft type
        $I->amLoggedInAs(8);
        $I->amOnRoute('aircraft-type/view', ['id' => 2]);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Resources');
    }

    public function guestRedirectedToLoginOnViewPage(\FunctionalTester $I): void
    {
        $I->amOnRoute('aircraft-type/view', ['id' => 2]);
        $I->seeCurrentUrlMatches('~login~');
    }

    // --- Download authorization ---

    public function guestCannotDownloadResource(\FunctionalTester $I): void
    {
        $I->amOnRoute('aircraft-type-resource/download', ['id' => 1]);
        $I->seeCurrentUrlMatches('~login~');
    }

    public function credentialedPilotCanDownloadResource(\FunctionalTester $I): void
    {
        $I->amLoggedInAs(6);
        $I->amOnRoute('aircraft-type-resource/download', ['id' => 1]);
        $I->seeResponseCodeIs(200);
    }

    public function uncredentialedPilotCannotDownloadResource(\FunctionalTester $I): void
    {
        $I->amLoggedInAs(8);
        $I->amOnRoute('aircraft-type-resource/download', ['id' => 1]);
        $I->seeResponseCodeIs(403);
    }

    public function resourceManagerCanDownloadWithoutCredential(\FunctionalTester $I): void
    {
        $I->amLoggedInAs(9);
        $I->amOnRoute('aircraft-type-resource/download', ['id' => 1]);
        $I->seeResponseCodeIs(200);
    }

    // --- Delete authorization ---

    public function nonManagerCannotDeleteResource(\FunctionalTester $I): void
    {
        $I->amLoggedInAs(6);
        $I->sendAjaxPostRequest('/aircraft-type-resource/delete?id=1');
        $I->seeResponseCodeIs(403);
    }

    public function resourceManagerCanDeleteResource(\FunctionalTester $I): void
    {
        $I->amLoggedInAs(9);
        $I->sendAjaxPostRequest('/aircraft-type-resource/delete?id=1');
        // Should redirect (302) to aircraft-type/view after deletion
        $I->seeResponseCodeIs(302);
    }

    // --- Upload authorization ---

    public function nonManagerCannotUploadResource(\FunctionalTester $I): void
    {
        $I->amLoggedInAs(6);
        $I->sendAjaxPostRequest('/aircraft-type-resource/upload?aircraftTypeId=2');
        $I->seeResponseCodeIs(403);
    }
}
