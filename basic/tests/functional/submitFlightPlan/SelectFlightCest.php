<?php

namespace tests\functional\submitFlightPlan;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\AircraftFixture;
use tests\fixtures\SubmittedFlightPlanFixture;
use tests\fixtures\RouteFixture;
use tests\fixtures\TourStageFixture;
use Yii;

class SelectFlightCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'aircraft' => AircraftFixture::class,
            'route' => RouteFixture::class,
            'tour_stage' => TourStageFixture::class,
            // Loads the assignments too (see tests/fixtures/data/assignment.php)
            'submittedFlightPlan' => SubmittedFlightPlanFixture::class,
        ];
    }

    public function openSelectFlightNoRoutesFromLocation(\FunctionalTester $I){
        $I->amLoggedInAs(10);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->see('Select flight');
        $I->see('No results found.');

        $I->see('Charter flight');
        $I->see('Continue', 'button');
    }

    public function openSelectFlightOnlyStagesFromLocation(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->see('Select flight');
        $I->see('Tour Stages');
        $I->see('Tour present 2 #1');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=5"]');

        $I->see('Routes');
        $I->see('No results found.');
        $I->see('Charter flight');
        $I->see('Continue', 'button');
    }

    public function openSelectFlightBothStagesAndRoutes(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->see('Select flight');

        $I->see('Tour Stages');
        $I->see('Tour actual reported #1');

        // This stage is in the past
        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=1"]');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=2"]');

        $I->see('Routes');
        $I->see('Showing 1-2 of 2 items.');

        $I->see('LEBL');
        $I->see('LEVC');
        $I->see('GCLP');
        $I->see('165');
        $I->see('1173');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-route?route_id=1"]');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-route?route_id=3"]');

        $I->see('Charter flight');
        $I->see('Continue', 'button');
    }

    public function canSelectCharterFlightWithAirport(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->see('Charter flight');
        $I->see('Continue', 'button');

        $I->fillField('input[name="arrival"]', 'gclp');
        $I->click('Continue');

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('submitted-flight-plan/select-aircraft-charter');
        $I->seeInCurrentUrl('arrival=gclp');

        $I->see('Charter flight (LEMD-GCLP)');
    }

    public function cantSelectFlightIfNonActivatedUser(\FunctionalTester $I){
        $I->amLoggedInAs(3);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('LEBL');
        $I->dontSee('LEVC');
        $I->dontSee('GCLP');
        $I->dontSee('Charter flight');
        $I->dontSee('Continue', 'button');
    }

    public function cantSelectFlightIfVisitor(\FunctionalTester $I){
        $I->amOnRoute('submitted-flight-plan/select-flight');
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openSelectFlightShowsAvailableAssignments(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->see('Assignments', 'h3');
        $I->see('Iberian Logistics', '.assignments-table');
        $I->see('Canary Fresh Fish', '.assignments-table');
        $I->see('EC-BBB', '.assignments-table');
        $I->see('B738', '.assignments-table');
        $I->see('261', '.assignments-table');
        $I->seeElement('.assignments-table img[alt="Iberian Logistics"]');

        // Bookable (range and credentials are checked when booking)
        foreach ([1, 2, 6] as $id) {
            $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-assignment?assignment_id=' . $id . '"]');
        }
        // Aircraft elsewhere, aircraft reserved, another pilot's, departing from another airport, pilot 10's
        foreach ([3, 4, 5, 7, 8] as $id) {
            $I->dontSeeElement('a[href*="/submitted-flight-plan/prepare-fpl-assignment?assignment_id=' . $id . '"]');
        }
        $I->dontSee('EC-HHH', '.assignments-table');
        $I->dontSee('EC-DDD', '.assignments-table');
        $I->dontSee('EC-AAA', '.assignments-table');

        // No aircraft selection for assignments
        $I->dontSeeElement('.assignments-table a[href*="select-aircraft"]');
    }

    public function openSelectFlightAssignmentsSortedByDistance(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $html = $I->grabPageSource();
        $first = strpos($html, 'prepare-fpl-assignment?assignment_id=1"');
        $second = strpos($html, 'prepare-fpl-assignment?assignment_id=2"');
        $third = strpos($html, 'prepare-fpl-assignment?assignment_id=6"');
        $I->assertNotFalse($first);
        $I->assertTrue($first < $second && $second < $third, 'Assignments sorted by distance, then id');
    }

    public function openSelectFlightSectionsOrder(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $html = $I->grabPageSource();
        $positions = array_map(fn($title) => strpos($html, '>' . $title . '</h3>'), ['Tour Stages', 'Assignments', 'Charter flight', 'Routes']);
        foreach ($positions as $position) {
            $I->assertNotFalse($position);
        }
        $sorted = $positions;
        sort($sorted);
        $I->assertSame($sorted, $positions, 'Sections: tour stages, assignments, charter, routes');
    }

    public function openSelectFlightWithoutAssignments(\FunctionalTester $I){
        // Pilot 2 is at LEMD, where only pilot 1 has an assignment
        $I->amLoggedInAs(2);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->dontSee('Assignments', 'h3');
        $I->dontSeeElement('.assignments-table');
        $I->dontSeeElement('a[href*="prepare-fpl-assignment"]');
    }

    public function openSelectFlightAssignmentsOfPilotInAnotherAirport(\FunctionalTester $I){
        $I->amLoggedInAs(10);
        $I->amOnRoute('submitted-flight-plan/select-flight');

        $I->see('Assignments', 'h3');
        $I->seeElement('a[href*="/submitted-flight-plan/prepare-fpl-assignment?assignment_id=8"]');
        $I->dontSeeElement('a[href*="/submitted-flight-plan/prepare-fpl-assignment?assignment_id=1"]');
    }

}
