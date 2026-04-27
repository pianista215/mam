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

    public function pilotWithActivePplSeesB738(\FunctionalTester $I)
    {
        // Pilot 1 has PPL active → B738 (EC-BBB) must appear for LEBL→LEVC
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->see('EC-UUU');
        $I->see('C172 Std');
    }

    public function pilotWithoutCredentialDoesNotSeeB738(\FunctionalTester $I)
    {
        // Pilot 8 has NO credentials → B738 (requires PPL) must be hidden; C172 is unrestricted
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->see('EC-UUU');
        $I->see('C172 Std');
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

    public function prepareFplRouteAllowedForUnrestrictedAircraft(\FunctionalTester $I)
    {
        // Pilot 8 (no credentials) can still fly C172 (no type restriction) on route 1
        $I->amLoggedInAs(8);
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

    public function prepareFplTourAllowedForUnrestrictedAircraft(\FunctionalTester $I)
    {
        // Pilot 8 can fly C172 (no restriction) on tour stage 2 (LEBL→LEMD)
        $I->amLoggedInAs(8);
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

    public function prepareFplCharterAllowedForUnrestrictedAircraft(\FunctionalTester $I)
    {
        // Pilot 8 can fly C172 (no restriction) on a charter to LEMD
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-charter', ['arrival' => 'lemd', 'aircraft_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->see('Flight Plan Submission');
    }

    // -------------------------------------------------------------------------
    // Credential validity edge cases (these tests run last and mutate fixture data)
    // -------------------------------------------------------------------------

    public function pilotWithExpiredCredentialCannotSeeRestrictedAircraft(\FunctionalTester $I)
    {
        // Pilot 1 has PPL active with no expiry (id=1). Expire it to verify expired credentials
        // are not counted by applyCredentialFilter (only expiry_date IS NULL OR >= today qualify).
        \app\models\PilotCredential::updateAll(['expiry_date' => '2020-01-01'], ['id' => 1]);

        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->see('EC-UUU');
    }

    public function pilotWithOnlyStudentCredentialCannotSeeRestrictedAircraft(\FunctionalTester $I)
    {
        // Demote pilot 1's PPL (id=1) to student — student credentials must not unlock restricted aircraft.
        \app\models\PilotCredential::updateAll(['status' => \app\models\PilotCredential::STATUS_STUDENT, 'expiry_date' => null], ['id' => 1]);

        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->see('EC-UUU');
    }
}
