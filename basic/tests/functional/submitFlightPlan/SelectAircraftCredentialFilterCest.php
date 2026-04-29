<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AircraftFixture;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CharterRouteFixture;
use tests\fixtures\CredentialTypeAircraftTypeFixture;
use tests\fixtures\CredentialTypeAirportAircraftFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\CredentialTypePrerequisiteFixture;
use tests\fixtures\PilotCredentialFixture;
use tests\fixtures\RouteFixture;
use tests\fixtures\TourStageFixture;

/**
 * Tests aircraft credential filtering in the FPL aircraft-selection step and
 * server-side bypass prevention in all three prepareFpl* actions (route, tour, charter).
 *
 * Credential → aircraft type mapping (credential_type_aircraft_type):
 *   - PPL  (id=1) → C172 (type_id=4)
 *   - CPL  (id=3) → BE58 (type_id=5)
 *   - B738 Rating (id=5) → B738 (type_id=2)
 *
 * Airport restriction (credential_type_airport_aircraft):
 *   - MNPS (id=4) required for B738 at GCLP
 *
 * Pilot credentials in fixtures:
 *   - Pilot 1 (John Doe):      PPL + IR + B738 Rating + MNPS (full set for legacy FPL tests)
 *   - Pilot 4 (Vfr Validator): student PPL only — sees only C172
 *   - Pilot 6 (Vfr School):    PPL + CPL + IR + B738 Rating + MNPS — full set
 *   - Pilot 7 (Ifr School):    PPL active + B738 Rating active — no MNPS
 *   - Pilot 8 (Other Ifr):     no credentials
 */
class SelectAircraftCredentialFilterCest
{
    public function _fixtures()
    {
        return [
            'authAssignment'              => AuthAssignmentFixture::class,
            'aircraft'                    => AircraftFixture::class,
            'charterRoute'                => CharterRouteFixture::class,
            'route'                       => RouteFixture::class,
            'tourStage'                   => TourStageFixture::class,
            'credentialType'              => CredentialTypeFixture::class,
            'credentialTypePrerequisite'  => CredentialTypePrerequisiteFixture::class,
            'credentialTypeAircraftType'  => CredentialTypeAircraftTypeFixture::class,
            'credentialTypeAirportAircraft' => CredentialTypeAirportAircraftFixture::class,
            'pilotCredential'             => PilotCredentialFixture::class,
        ];
    }

    // -------------------------------------------------------------------------
    // Aircraft-type credential filter (select-aircraft-route LEBL→LEVC, 165 nm)
    // -------------------------------------------------------------------------

    public function pilotWithPplSeesOnlyC172(\FunctionalTester $I)
    {
        // Pilot 4 has only student PPL → only C172 (EC-UUU) visible; B738 requires B738 Rating
        $I->amLoggedInAs(4);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-UUU');
        $I->see('C172 Std');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
    }

    public function pilotWithoutCredentialSeesNoAircraft(\FunctionalTester $I)
    {
        // Pilot 8 has NO credentials → all aircraft hidden
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
        $I->dontSee('EC-E58');
        $I->dontSee('Baron 58 Std');
    }

    // -------------------------------------------------------------------------
    // Airport restriction filter (select-aircraft-route LEBL→GCLP, 1173 nm)
    // -------------------------------------------------------------------------

    public function b738BlockedAtGclpForPilotLackingMnps(\FunctionalTester $I)
    {
        // Pilot 7 has PPL + B738 Rating but no MNPS → B738 is hidden for GCLP (MNPS required there)
        // C172 (range 696 nm) is out of range for this route, so nothing is shown.
        $I->amLoggedInAs(7);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('EC-E58');
    }

    public function b738ShownAtGclpForPilotWithMnps(\FunctionalTester $I)
    {
        // Pilot 6 has PPL + CPL + IR + B738 Rating + MNPS → B738 is visible for LEBL→GCLP
        $I->amLoggedInAs(6);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
    }

    // -------------------------------------------------------------------------
    // Bypass prevention: prepareFplRoute
    // -------------------------------------------------------------------------

    public function prepareFplRouteBlockedForPilotLackingB738Rating(\FunctionalTester $I)
    {
        // Pilot 8 (no credentials) bypasses the UI and tries to get B738 (aircraft 2) on route 1
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-route', ['route_id' => '1', 'aircraft_id' => '2']);

        $I->seeResponseCodeIs(403);
    }

    public function prepareFplRouteBlockedForPilotLackingPpl(\FunctionalTester $I)
    {
        // Pilot 8 (no credentials) bypasses the UI and tries C172 (aircraft 3) on route 1 — must be 403
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-route', ['route_id' => '1', 'aircraft_id' => '3']);

        $I->seeResponseCodeIs(403);
    }

    public function prepareFplRouteAllowedForPilotWithPpl(\FunctionalTester $I)
    {
        // Pilot 1 (PPL → C172 requires PPL) can proceed with C172 (aircraft 3) on route 1
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-route', ['route_id' => '1', 'aircraft_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->see('Flight Plan Submission');
    }

    public function prepareFplRouteBlockedAtGclpWithoutMnps(\FunctionalTester $I)
    {
        // Pilot 7 (B738 Rating, no MNPS) bypasses and tries B738 to GCLP (route 3) — blocked by MNPS
        $I->amLoggedInAs(7);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-route', ['route_id' => '3', 'aircraft_id' => '2']);

        $I->seeResponseCodeIs(403);
    }

    // -------------------------------------------------------------------------
    // Bypass prevention: prepareFplTour
    // -------------------------------------------------------------------------

    public function prepareFplTourBlockedForPilotWithoutCredential(\FunctionalTester $I)
    {
        // Tour stage 1: LEBL→LEMD. Pilot 8 (no credentials) tries B738 (aircraft 2) — must be 403
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-tour', ['tour_stage_id' => '1', 'aircraft_id' => '2']);

        $I->seeResponseCodeIs(403);
    }

    public function prepareFplTourAllowedForPilotWithPpl(\FunctionalTester $I)
    {
        // Pilot 1 (PPL → C172 requires PPL) can fly C172 (aircraft 3) on tour stage 2
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-tour', ['tour_stage_id' => '2', 'aircraft_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->see('Flight Plan Submission');
    }

    // -------------------------------------------------------------------------
    // Bypass prevention: prepareFplCharter
    // -------------------------------------------------------------------------

    public function prepareFplCharterBlockedForPilotWithoutCredential(\FunctionalTester $I)
    {
        // Pilot 8 (no credentials) tries B738 (aircraft 2) on a charter to LEMD — must be 403
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', ['arrival' => 'lemd', 'aircraft_id' => '2']);

        $I->seeResponseCodeIs(403);
    }

    public function prepareFplCharterAllowedForPilotWithPpl(\FunctionalTester $I)
    {
        // Pilot 1 (PPL → C172 requires PPL) can fly C172 (aircraft 3) on a charter to LEMD
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', ['arrival' => 'lemd', 'aircraft_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->see('Flight Plan Submission');
    }

    // -------------------------------------------------------------------------
    // Credential validity edge cases (these tests mutate fixture data and run last)
    // -------------------------------------------------------------------------

    public function pilotWithExpiredCredentialCanStillSeeAircraft(\FunctionalTester $I)
    {
        // Pilot 1 has PPL active with no expiry (id=1). Expire it: an expired but non-revoked
        // credential still allows flying so the pilot can complete a renewal flight.
        // Only revoking (deleting) a credential removes access.
        \app\models\PilotCredential::updateAll(['expiry_date' => '2020-01-01'], ['id' => 1]);

        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-UUU');
        $I->see('C172 Std');
    }

    public function pilotWithStudentCredentialCanStillSeeAircraft(\FunctionalTester $I)
    {
        // Demote pilot 1's PPL (id=1) to student — student credentials still grant aircraft access
        // so the pilot can complete training flights. Access is removed only by revoking.
        \app\models\PilotCredential::updateAll(['status' => \app\models\PilotCredential::STATUS_STUDENT, 'expiry_date' => null], ['id' => 1]);

        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-UUU');
        $I->see('C172 Std');
    }
}
