<?php

namespace tests\functional\submitFlightPlan;

use app\models\Assignment;
use app\models\PilotCredential;
use app\models\SubmittedFlightPlan;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeAircraftTypeFixture;
use tests\fixtures\CredentialTypeAirportAircraftFixture;
use tests\fixtures\CredentialTypeFixture;
use tests\fixtures\PilotCredentialFixture;
use tests\fixtures\SubmittedFlightPlanFixture;

/**
 * Assignment fixtures (see tests/fixtures/data/assignment.php), pilot 1 at LEBL:
 * 1 LEBL-LEMD aircraft 2 (bookable), 2 LEBL-GCLP aircraft 2 (bookable, needs MNPS), 3 aircraft at GCLP,
 * 4 aircraft reserved by FPL 1, 5 another pilot's, 6 out of the C172 range, 7 departing from LEMD,
 * 8 pilot 10 (high charter ratio).
 */
class SubmitFplAssignmentCest
{
    public function _fixtures(){
        return [
            'authAssignment'                => AuthAssignmentFixture::class,
            'submittedFlightPlan'           => SubmittedFlightPlanFixture::class,
            'credentialType'                => CredentialTypeFixture::class,
            'credentialTypeAircraftType'    => CredentialTypeAircraftTypeFixture::class,
            'credentialTypeAirportAircraft' => CredentialTypeAirportAircraftFixture::class,
            'pilotCredential'               => PilotCredentialFixture::class,
        ];
    }

    private function seeForbidden(\FunctionalTester $I)
    {
        $I->see('Forbidden');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
        $I->assertEquals(5, SubmittedFlightPlan::find()->count());
    }

    private function fillValidIfrPlan(\FunctionalTester $I)
    {
        $I->fillField('#submittedflightplan-cruise_speed_value', '350');
        $I->fillField('#flight_level_value', '340');
        $I->fillField('#submittedflightplan-route', 'DCT CASPE UN975 TOBEK');
        $I->fillField('#submittedflightplan-estimated_time', '0105');
        $I->fillField('#submittedflightplan-alternative1_icao', 'LEVC');
        $I->fillField('#submittedflightplan-other_information', 'DOF/20261027 REG/ECBBB OPR/XXX');
        $I->fillField('#submittedflightplan-endurance_time', '0300');
    }

    private function validPostData(array $extra = []): array
    {
        return ['SubmittedFlightPlan' => array_merge([
            'flight_rules' => 'I',
            'alternative1_icao' => 'LEVC',
            'cruise_speed_value' => '350',
            'cruise_speed_unit' => 'N',
            'flight_level_value' => '340',
            'flight_level_unit' => 'F',
            'route' => 'DCT CASPE UN975 TOBEK',
            'estimated_time' => '0105',
            'other_information' => 'DOF/20261027 REG/ECBBB OPR/XXX',
            'endurance_time' => '0300',
        ], $extra)];
    }

    private function bookAssignment1(\FunctionalTester $I): SubmittedFlightPlan
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '1']);
        $this->fillValidIfrPlan($I);
        $I->click('Submit FPL', 'button');
        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('submitted-flight-plan/view');

        $model = SubmittedFlightPlan::findOne(['pilot_id' => 1]);
        $I->assertNotNull($model);
        return $model;
    }

    // Access

    public function openPrepareFplAssignmentAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '1']);
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openPrepareFplAssignmentAsNonActivatedUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '1']);
        $this->seeForbidden($I);
    }

    public function openPrepareFplAssignmentNotFound(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '999']);
        $I->seeResponseCodeIs(404);
        $I->dontSee('Flight Plan Submission');
    }

    public function openPrepareFplAssignmentOfAnotherPilot(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '5']);
        $this->seeForbidden($I);
    }

    public function openPrepareFplAssignmentAircraftInDifferentLocation(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '3']);
        $this->seeForbidden($I);
    }

    public function openPrepareFplAssignmentAircraftAlreadyReserved(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '4']);
        $this->seeForbidden($I);
    }

    public function openPrepareFplAssignmentOutOfRange(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '6']);
        $this->seeForbidden($I);
    }

    public function openPrepareFplAssignmentDepartingFromAnotherAirport(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '7']);
        $this->seeForbidden($I);
    }

    public function openPrepareFplAssignmentWithoutAircraftTypeCredential(\FunctionalTester $I)
    {
        // Pilot 1 loses the B738 rating
        PilotCredential::deleteAll(['pilot_id' => 1, 'credential_type_id' => 5]);

        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '1']);
        $this->seeForbidden($I);
    }

    public function openPrepareFplAssignmentWithoutAirportCredential(\FunctionalTester $I)
    {
        // Pilot 1 loses MNPS, required to fly the B738 to GCLP
        PilotCredential::deleteAll(['pilot_id' => 1, 'credential_type_id' => 4]);

        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '2']);
        $this->seeForbidden($I);

        // LEMD has no airport restriction
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '1']);
        $I->see('Flight Plan Submission');
    }

    public function openPrepareFplAssignmentButAlreadyHaveOneSubmitted(\FunctionalTester $I)
    {
        // Pilot 5 already has FPL 1
        $I->amLoggedInAs(5);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('submitted-flight-plan/view');
        $I->dontSee('Flight Plan Submission');
        $I->dontSee('Submit FPL', 'button');
    }

    public function postPrepareFplAssignmentOfAnotherPilot(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/submitted-flight-plan/prepare-fpl-assignment?assignment_id=5', $this->validPostData());

        $I->seeResponseCodeIs(403);
        $I->assertNull(SubmittedFlightPlan::findOne(['pilot_id' => 1]));
        $I->assertNull(SubmittedFlightPlan::findOne(['pilot_id' => 9]));
    }

    // Prepare form

    public function openPrepareFplAssignmentShowsAssignmentHeader(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '1']);

        $I->seeResponseCodeIs(200);
        $I->see('Flight Plan Submission');
        $I->see('Assignment', '.fs-5');
        $I->see('Iberian Logistics (LEBL-LEMD)');
        $I->seeElement('img[alt="Iberian Logistics"]');

        // The aircraft comes from the assignment, there is no aircraft selection
        $I->seeInField('input[name=aircraftRegistration]', 'EC-BBB');
        $I->seeInField('input[name=aircraftType]', 'B738');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'LEMD');
        $I->seeInField('input[name=pilot]', 'John Doe');
        $I->see('Submit FPL', 'button');
    }

    public function openPrepareFplAssignmentEmptyFields(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '1']);
        $I->click('Submit FPL', 'button');

        $I->expectTo('see validations errors');
        $I->see('Cruise Speed Value cannot be blank.');
        $I->see('Route cannot be blank.');
        $I->see('Altn Aerodrome cannot be blank.');
        $I->see('Iberian Logistics (LEBL-LEMD)');

        $I->assertEquals(5, SubmittedFlightPlan::find()->count());
    }

    public function openPrepareFplAssignmentInvalidAlternative(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '1']);
        $this->fillValidIfrPlan($I);
        $I->fillField('#submittedflightplan-alternative1_icao', 'XXXX');
        $I->click('Submit FPL', 'button');

        $I->see('Altn Aerodrome is invalid.');
        $I->assertEquals(5, SubmittedFlightPlan::find()->count());
    }

    // Booking

    public function submitValidAssignmentFpl(\FunctionalTester $I)
    {
        $model = $this->bookAssignment1($I);

        $I->see('Current Flight Plan');
        $I->see('Iberian Logistics (LEBL-LEMD)');
        $I->seeElement('img[alt="Iberian Logistics"]');
        $I->seeInField('input[name=aircraftRegistration]', 'EC-BBB');
        $I->seeInField('input[name=departure]', 'LEBL');
        $I->seeInField('input[name=destination]', 'LEMD');

        $I->assertEquals(1, $model->assignment_id);
        $I->assertEquals(2, $model->aircraft_id);
        $I->assertNull($model->route_id);
        $I->assertNull($model->tour_stage_id);
        $I->assertNull($model->charter_route_id);
        $I->assertEquals('I', $model->flight_rules);
        $I->assertEquals('DCT CASPE UN975 TOBEK', $model->route);
        $I->assertEquals('LEVC', $model->alternative1_icao);

        // Payload is generated like for any other flight
        $I->assertNotNull($model->crew);
        $I->assertNotNull($model->pax_adults);
        $I->assertNotNull($model->cargo_paid_kg);

        $I->assertEquals(6, SubmittedFlightPlan::find()->count());
        $I->assertNotNull(Assignment::findOne(1), 'The assignment is kept until the flight is reported');
    }

    public function submitAssignmentFplIgnoresEntityFieldsFromPost(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/submitted-flight-plan/prepare-fpl-assignment?assignment_id=1', $this->validPostData([
            'assignment_id' => '2',
            'route_id' => '1',
            'tour_stage_id' => '2',
            'charter_route_id' => '1',
            'aircraft_id' => '3',
            'pilot_id' => '9',
            'pax_adults' => '9999',
        ]));
        $I->seeResponseCodeIsRedirection();

        $model = SubmittedFlightPlan::findOne(['pilot_id' => 1]);
        $I->assertNotNull($model);
        $I->assertEquals(1, $model->assignment_id);
        $I->assertEquals(2, $model->aircraft_id);
        $I->assertNull($model->route_id);
        $I->assertNull($model->tour_stage_id);
        $I->assertNull($model->charter_route_id);
        $I->assertNotEquals(9999, $model->pax_adults);
        $I->assertNull(SubmittedFlightPlan::findOne(['pilot_id' => 9]));
    }

    public function submitAnotherAssignmentWithSameAircraftAfterBooking(\FunctionalTester $I)
    {
        $this->bookAssignment1($I);

        // Pilot 1 already has a FPL: back to it
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '2']);
        $I->seeInCurrentUrl('submitted-flight-plan/view');
        $I->see('Iberian Logistics (LEBL-LEMD)');
        $I->assertEquals(6, SubmittedFlightPlan::find()->count());
    }

    public function highCharterRatioDoesNotBlockAssignments(\FunctionalTester $I)
    {
        // Pilot 10 only has charter flights; give them the B738 rating to fly aircraft 10
        $credential = new PilotCredential([
            'pilot_id' => 10,
            'credential_type_id' => 5,
            'status' => PilotCredential::STATUS_ACTIVE,
            'issued_date' => '2024-01-01',
            'issued_by' => 2,
        ]);
        $I->assertTrue($credential->save(false));

        $I->amLoggedInAs(10);
        $I->amOnRoute('submitted-flight-plan/prepare-fpl-assignment', ['assignment_id' => '8']);

        $I->dontSee('Your charter flights ratio is too high.');
        $I->see('Flight Plan Submission');
        $I->see('Iberian Logistics (LEAL-LEMD)');
        $I->seeInField('input[name=aircraftRegistration]', 'EC-JJJ');
    }

    // View, update and delete of an assignment FPL

    public function updateAssignmentFplKeepsAssignment(\FunctionalTester $I)
    {
        $model = $this->bookAssignment1($I);

        $I->amOnRoute('submitted-flight-plan/update', ['id' => $model->id]);
        $I->seeResponseCodeIs(200);
        $I->see('Iberian Logistics (LEBL-LEMD)');
        $I->seeElement('img[alt="Iberian Logistics"]');

        $I->fillField('#submittedflightplan-route', 'DCT NEW ROUTE');
        $I->click('Submit FPL', 'button');
        $I->seeInCurrentUrl('submitted-flight-plan/view');

        $model->refresh();
        $I->assertEquals('DCT NEW ROUTE', $model->route);
        $I->assertEquals(1, $model->assignment_id);
        $I->assertEquals(2, $model->aircraft_id);
    }

    public function validatorSeesAssignmentFpl(\FunctionalTester $I)
    {
        $model = $this->bookAssignment1($I);

        // Pilot 5 is IFR validator
        $I->amLoggedInAs(5);
        $I->amOnRoute('submitted-flight-plan/view', ['id' => $model->id]);
        $I->seeResponseCodeIs(200);
        $I->see('Iberian Logistics (LEBL-LEMD)');
    }

    public function deleteAssignmentFplMakesAssignmentAvailableAgain(\FunctionalTester $I)
    {
        $model = $this->bookAssignment1($I);

        $I->amOnRoute('submitted-flight-plan/select-flight');
        $I->seeInCurrentUrl('submitted-flight-plan/view');

        $I->sendAjaxPostRequest('/submitted-flight-plan/delete?id=' . $model->id);
        $I->seeResponseCodeIsRedirection();

        $I->assertNull(SubmittedFlightPlan::findOne($model->id));
        $I->assertNotNull(Assignment::findOne(1));

        $I->amOnRoute('submitted-flight-plan/select-flight');
        $I->see('Assignments');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-assignment?assignment_id=1"]');
    }
}
