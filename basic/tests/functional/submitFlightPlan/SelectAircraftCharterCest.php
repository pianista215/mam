<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeAircraftTypeFixture;
use tests\fixtures\CredentialTypeAirportAircraftFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\PilotCredentialFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use Yii;

class SelectAircraftCharterCest
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

    public function openSelectAircraftCharterNonActivatedPilot(\FunctionalTester $I){
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[ 'arrival' => 'gclp' ]);

        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('LEBL-LEMD');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function openSelectAircraftCharterVisitor(\FunctionalTester $I){
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[ 'arrival' => 'gclp' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openSelectAircraftCharterWithoutEnoughRegularRatio(\FunctionalTester $I){
        $I->amLoggedInAs(10);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[ 'arrival' => 'gclp' ]);
        $I->seeResponseCodeIs(200);

        $I->see('Your charter flights ratio is too high. Please complete more regular or tour flights before booking another charter.');
        $I->seeInCurrentUrl('submitted-flight-plan/select-flight');
        $I->dontSee('LEAL-GCLP');
    }

    public function openSelectAircraftCharterWithoutAirport(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[]);

        $I->see('Bad Request: Missing required parameters: arrival');
        $I->seeResponseCodeIs(400);
        $I->dontSee('EC-BBB');
    }

    public function openSelectAircraftCharterWithInvalidAirport(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',['arrival' => 'zzzz']);

        $I->see('Bad Request: Arrival airport (ICAO) is invalid.');
        $I->seeResponseCodeIs(400);
        $I->dontSee('EC-BBB');
    }

    public function openSelectAircraftCharterValidAirport(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[ 'arrival' => 'LEMD' ]);

        $I->see('LEBL-LEMD');
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-charter?arrival=LEMD&aircraft_id=2"]');
        $I->see('EC-UUU');
        $I->see('C172 Std');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-charter?arrival=LEMD&aircraft_id=3"]');

        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

    public function openSelectAircraftCharterFiltersPlanesBasedOnRange(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter',[ 'arrival' => 'gclp' ]);

        $I->see('LEBL-GCLP');

        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-charter?arrival=GCLP&aircraft_id=2"]');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
        $I->dontSeeElement('a[href*="/submitted-flight-plan/prepare-fpl-charter?arrival=gclp&aircraft_id=3"]');

        // Already reserved plane
        $I->dontSee('EC-DDD');
        $I->dontSee('Boeing Name 2 Cargo');
    }

    // -------------------------------------------------------------------------
    // Credential type filter (LEBL→LEMD)
    // -------------------------------------------------------------------------

    public function pilotWithPplSeesOnlyC172(\FunctionalTester $I)
    {
        // Pilot 4 has only student PPL → only C172 (EC-UUU) visible; B738 requires B738 Rating
        \app\models\SubmittedFlightPlan::deleteAll(['pilot_id' => 4]);
        \app\models\CharterRoute::deleteAll(['pilot_id' => 4]);
        $I->amLoggedInAs(4);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter', ['arrival' => 'LEMD']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-UUU');
        $I->see('C172 Std');
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
    }

    public function pilotWithoutCredentialSeesNoAircraft(\FunctionalTester $I)
    {
        // Pilot 8 has NO credentials → all aircraft hidden
        \app\models\SubmittedFlightPlan::deleteAll(['pilot_id' => 8]);
        $I->amLoggedInAs(8);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter', ['arrival' => 'LEMD']);

        $I->seeResponseCodeIs(200);
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    // -------------------------------------------------------------------------
    // Airport restriction filter (LEBL→GCLP)
    // -------------------------------------------------------------------------

    public function b738BlockedAtGclpForPilotLackingMnps(\FunctionalTester $I)
    {
        // Pilot 7 has PPL + B738 Rating but no MNPS → B738 hidden for GCLP (MNPS required)
        // C172 (range 696 nm) is also out of range → nothing shown
        \app\models\SubmittedFlightPlan::deleteAll(['pilot_id' => 7]);
        $I->amLoggedInAs(7);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter', ['arrival' => 'GCLP']);

        $I->seeResponseCodeIs(200);
        $I->dontSee('EC-BBB');
        $I->dontSee('Boeing Name Cargo');
        $I->dontSee('EC-UUU');
        $I->dontSee('C172 Std');
    }

    public function b738ShownAtGclpForPilotWithMnps(\FunctionalTester $I)
    {
        // Pilot 6 has full set (PPL + CPL + IR + B738 Rating + MNPS) → B738 visible for GCLP
        \app\models\SubmittedFlightPlan::deleteAll(['pilot_id' => 6]);
        $I->amLoggedInAs(6);
        $I->amOnRoute('submitted-flight-plan/select-aircraft-charter', ['arrival' => 'GCLP']);

        $I->seeResponseCodeIs(200);
        $I->see('EC-BBB');
        $I->see('Boeing Name Cargo');
    }

}