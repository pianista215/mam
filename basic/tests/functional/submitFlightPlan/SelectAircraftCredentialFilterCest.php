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
use tests\fixtures\SubmittedFlightPlanFixture;
use tests\fixtures\TourStageFixture;

/**
 * Tests aircraft credential filtering in the FPL aircraft-selection step and
 * server-side bypass prevention in all three prepareFpl* actions (route, tour, charter).
 *
 * Fixture data summary:
 *   - credential_type_aircraft_type: B738 (type_id=2) requires PPL (cred_type_id=1)
 *   - credential_type_airport_aircraft: B738 at GCLP additionally requires CPL (cred_type_id=3)
 *   - Pilot 1 (John Doe, LEBL): PPL active, IR active — no CPL
 *   - Pilot 6 (Vfr School, LEBL): PPL + CPL + IR active
 *   - Pilot 8 (Other Ifr School, LEBL): no credentials
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
            'submittedFlightPlan'         => SubmittedFlightPlanFixture::class,
            'credentialType'              => CredentialTypeFixture::class,
            'credentialTypePrerequisite'  => CredentialTypePrerequisiteFixture::class,
            'credentialTypeAircraftType'  => CredentialTypeAircraftTypeFixture::class,
            'credentialTypeAirportAircraft' => CredentialTypeAirportAircraftFixture::class,
            'pilotCredential'             => PilotCredentialFixture::class,
        ];
    }

    // -------------------------------------------------------------------------
    // Aircraft-type credential filter (select-aircraft-route)
    // -------------------------------------------------------------------------

    public function pilotWithActivePplSeesB738AndC172(\FunctionalTester $I)
    {
        // Pilot 1 has PPL active → B738 (EC-BBB) and C172 (EC-UUU) must appear for LEBL→LEVC
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->see('EC-UUU');
        $I->see('C172 Std');
    }

    public function pilotWithoutCredentialSeesNoAircraft(\FunctionalTester $I)
    {
        // Pilot 8 has NO credentials → all aircraft hidden (both B738 and C172 require PPL)
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    // -------------------------------------------------------------------------
    // Airport restriction filter (select-aircraft-route LEBL→GCLP)
    // -------------------------------------------------------------------------

    public function b738BlockedAtRestrictedAirportForPilotLackingCpl(\FunctionalTester $I)
    {
        // Pilot 1 has PPL but no CPL → B738 is hidden for GCLP (CPL required there)
        // C172 is out of range for this route, so the only result with no credential
        // filter would be B738. With the filter, nothing from B738 is shown.
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
    }

    public function b738ShownAtRestrictedAirportForPilotWithCpl(\FunctionalTester $I)
    {
        // Pilot 6 has PPL + CPL active → B738 is visible for LEBL→GCLP
        $I->amLoggedInAs(6);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
    }

    // -------------------------------------------------------------------------
    // Bypass prevention: prepareFplRoute
    // -------------------------------------------------------------------------

    public function prepareFplRouteBlockedForPilotLackingAircraftTypeCredential(\FunctionalTester $I)
    {
        // Pilot 8 (no credentials) bypasses the UI and tries to get aircraft 2 (B738) on route 1
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-route', ['route_id' => '1', 'aircraft_id' => '2']);

        $I->seeResponseCodeIs(403);
    }

    public function prepareFplRouteBlockedForPilotLackingC172Credential(\FunctionalTester $I)
    {
        // Pilot 8 (no credentials) bypasses the UI and tries aircraft 3 (C172) on route 1 — must be 403
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-route', ['route_id' => '1', 'aircraft_id' => '3']);

        $I->seeResponseCodeIs(403);
    }

    public function prepareFplRouteAllowedForPilotWithCredential(\FunctionalTester $I)
    {
        // Pilot 1 (PPL → C172 requires PPL) can proceed with aircraft 3 (C172) on route 1
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-route', ['route_id' => '1', 'aircraft_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->see('Flight Plan Submission');
    }

    public function prepareFplRouteBlockedAtRestrictedAirport(\FunctionalTester $I)
    {
        // Pilot 1 (PPL, no CPL) bypasses and tries B738 to GCLP (route 3)
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-route', ['route_id' => '3', 'aircraft_id' => '2']);

        $I->seeResponseCodeIs(403);
    }

    // -------------------------------------------------------------------------
    // Bypass prevention: prepareFplTour
    // -------------------------------------------------------------------------

    public function prepareFplTourBlockedForPilotLackingAircraftTypeCredential(\FunctionalTester $I)
    {
        // Tour stage 1: LEBL→LEMD. Pilot 8 (no credentials) tries B738 (aircraft 2) — must be 403
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-tour', ['tour_stage_id' => '1', 'aircraft_id' => '2']);

        $I->seeResponseCodeIs(403);
    }

    public function prepareFplTourAllowedForPilotWithCredential(\FunctionalTester $I)
    {
        // Pilot 1 (PPL → C172 requires PPL) can fly aircraft 3 (C172) on tour stage 2
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-tour', ['tour_stage_id' => '2', 'aircraft_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->see('Flight Plan Submission');
    }

    // -------------------------------------------------------------------------
    // Bypass prevention: prepareFplCharter
    // -------------------------------------------------------------------------

    public function prepareFplCharterBlockedForPilotLackingAircraftTypeCredential(\FunctionalTester $I)
    {
        // Pilot 8 (no credentials) tries B738 (aircraft 2) on a charter to LEMD — must be 403
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', ['arrival' => 'lemd', 'aircraft_id' => '2']);

        $I->seeResponseCodeIs(403);
    }

    public function prepareFplCharterAllowedForPilotWithCredential(\FunctionalTester $I)
    {
        // Pilot 1 (PPL → C172 requires PPL) can fly aircraft 3 (C172) on a charter to LEMD
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', ['arrival' => 'lemd', 'aircraft_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->see('Flight Plan Submission');
    }

    // -------------------------------------------------------------------------
    // Credential validity edge cases (these tests run last and mutate fixture data)
    // -------------------------------------------------------------------------

    public function pilotWithExpiredCredentialCanStillSeeAircraft(\FunctionalTester $I)
    {
        // Pilot 1 has PPL active with no expiry (id=1). Expire it: an expired but non-revoked
        // credential (status=ACTIVE, superseded_at=NULL) still allows flying so the pilot can
        // complete a renewal flight. Only revoking a credential removes access.
        \app\models\PilotCredential::updateAll(['expiry_date' => '2020-01-01'], ['id' => 1]);

        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->see('EC-UUU');
    }

    public function pilotWithStudentCredentialCanStillSeeAircraft(\FunctionalTester $I)
    {
        // Demote pilot 1's PPL (id=1) to student — student credentials still grant aircraft access
        // so the pilot can complete training flights. Access is only removed by revoking (deleting) the credential.
        \app\models\PilotCredential::updateAll(['status' => \app\models\PilotCredential::STATUS_STUDENT, 'expiry_date' => null], ['id' => 1]);

        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->see('EC-UUU');
    }
}
