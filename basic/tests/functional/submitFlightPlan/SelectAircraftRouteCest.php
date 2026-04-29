<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeAircraftTypeFixture;
use tests\fixtures\CredentialTypeAirportAircraftFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\PilotCredentialFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use Yii;

class SelectAircraftRouteCest
{
    public function _fixtures(){
        return [
            'authAssignment'              => AuthAssignmentFixture::class,
            'submittedFlightPlan'          => SubmittedFlightPlanFixture::class,
            'credentialType'              => CredentialTypeFixture::class,
            'credentialTypeAircraftType'  => CredentialTypeAircraftTypeFixture::class,
            'credentialTypeAirportAircraft' => CredentialTypeAirportAircraftFixture::class,
            'pilotCredential'             => PilotCredentialFixture::class,
        ];
    }

    public function openSelectAircraftRouteNotAvailableLocation(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route',[ 'route_id' => '1' ]);

        $I->see('Forbidden: Pilot location is not at LEBL');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEVC');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftRouteNonActivatedPilot(\FunctionalTester $I){
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route',[ 'route_id' => '1' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEBL');
        $I->dontSee('LEVC');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftRouteVisitor(\FunctionalTester $I){
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route',[ 'route_id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openSelectAircraftRouteValidLocationAsPilot(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route',[ 'route_id' => '1' ]);

        $I->see('LEBL');
        $I->see('LEVC');

        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-route?route_id=1&aircraft_id=2"]');
        $I->see('EC-UUU');
        $I->see('C172 Std');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-route?route_id=1&aircraft_id=3"]');


        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

    public function openSelectAircraftRouteFiltersPlanesBasedOnRange(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route',[ 'route_id' => '3' ]);

        $I->see('LEBL');
        $I->see('GCLP');

        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-route?route_id=3&aircraft_id=2"]');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
        $I->dontSeeElement('a[href*="/submitted-flight-plan/prepare-fpl-route?route_id=3&aircraft_id=3"]');

        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

    // -------------------------------------------------------------------------
    // Credential type filter (LEBL→LEVC, route 1)
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
    }

    // -------------------------------------------------------------------------
    // Airport restriction filter (LEBL→GCLP, route 3)
    // -------------------------------------------------------------------------

    public function b738BlockedAtGclpForPilotLackingMnps(\FunctionalTester $I)
    {
        // Pilot 7 has PPL + B738 Rating but no MNPS → B738 hidden for GCLP (MNPS required)
        // C172 (range 696 nm) is also out of range → nothing shown
        $I->amLoggedInAs(7);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function b738ShownAtGclpForPilotWithMnps(\FunctionalTester $I)
    {
        // Pilot 6 has full set (PPL + CPL + IR + B738 Rating + MNPS) → B738 visible for GCLP
        $I->amLoggedInAs(6);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '3']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
    }

    // -------------------------------------------------------------------------
    // Credential validity edge cases — these tests mutate fixture data, run last
    // -------------------------------------------------------------------------

    public function pilotWithExpiredCredentialCanStillSeeAircraft(\FunctionalTester $I)
    {
        // Expire pilot 1's PPL: an expired but non-revoked credential still grants access
        // so the pilot can complete a renewal flight. Access is removed only by revoking.
        \app\models\PilotCredential::updateAll(['expiry_date' => '2020-01-01'], ['id' => 1]);

        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-UUU');
        $I->see('C172 Std');
    }

    public function pilotWithStudentCredentialCanStillSeeAircraft(\FunctionalTester $I)
    {
        // Demote pilot 1's PPL to student: student credentials still grant access
        // so the pilot can complete training flights. Access is removed only by revoking.
        \app\models\PilotCredential::updateAll(['status' => \app\models\PilotCredential::STATUS_STUDENT, 'expiry_date' => null], ['id' => 1]);

        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-route', ['route_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-UUU');
        $I->see('C172 Std');
    }

}