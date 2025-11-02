<?php

namespace tests\functional\TourConfiguration;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\FlightFixture;
use Yii;

class TourIndexViewCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flight' => FlightFixture::class,
        ];
    }

    private function checkTourIndexCommon(\FunctionalTester $I){
        $I->amOnRoute('tour/index');

        $I->see('Tours');
        $I->see('Showing 1-5 of 5 items.');
        $I->see('Tour previous');
        $I->see('Tour actual empty');
        $I->see('Tour actual reported');
        $I->see('Tour not started');
        $I->see('Tour present 2');
    }

    public function openTourIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkTourIndexCommon($I);

        $I->see('Create Tour', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openTourIndexAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);

        $this->checkTourIndexCommon($I);

        $I->see('Create Tour', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Update']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function openTourIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkTourIndexCommon($I);

        $I->dontSee('Create Tour', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    public function openTourIndexAsVisitor(\FunctionalTester $I)
    {
        $this->checkTourIndexCommon($I);

        $I->dontSee('Create Tour', 'a');
        $I->seeElement('a', ['title' => 'View']);
        $I->dontSeeElement('a', ['title' => 'Update']);
        $I->dontSeeElement('a', ['title' => 'Delete']);
    }

    private function checkTourPastViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('tour/view', [ 'id' => '1' ]);

        $I->see('Tour previous');
        $I->see('Tour already ended without flights associated');
        $I->see('Tour Stages');
        $I->see('LEBL');
        $I->see('LEMD');
        $I->see('Desc');
    }

    public function openTourPastViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkTourPastViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=1"]');
        $I->seeElement('a[href*="/tour-stage/update?id=1"]');
        $I->seeElement('a[href*="/tour-stage/delete?id=1"]');
    }

    public function openTourPastViewAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);

        $this->checkTourPastViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=1"]');
        $I->seeElement('a[href*="/tour-stage/update?id=1"]');
        $I->seeElement('a[href*="/tour-stage/delete?id=1"]');
    }

    public function openTourPastViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkTourPastViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=1"]');
        $I->dontSeeElement('a[href*="/tour-stage/update?id=1"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=1"]');
    }

    public function openTourPastViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour/view', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    private function checkTourPresentEmptyViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('tour/view', [ 'id' => '2' ]);

        $I->see('Tour actual empty');
        $I->see('Tour actual without flights associated');
        $I->see('This tour has no stages yet.');
    }

    public function openTourPresentEmptyViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkTourPresentEmptyViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openTourPresentEmptyViewAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);

        $this->checkTourPresentEmptyViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');
    }

    public function openTourPresentEmptyViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkTourPresentEmptyViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');
    }

    public function openTourPresentEmptyViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour/view', [ 'id' => '2' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    private function checkTourPresentAlreadyFlownViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('tour/view', [ 'id' => '3' ]);

        $I->see('Tour actual reported');
        $I->see('Tour actual with flights associated');
        $I->see('Tour Stages');
        $I->see('LEBL');
        $I->see('LEMD');
        $I->see('GCLP');
        $I->see('260');
        $I->see('1173');
    }

    public function openTourPresentAlreadyFlownViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkTourPresentAlreadyFlownViewCommon($I);

        $I->see('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=2"]');
        $I->seeElement('a[href*="/tour-stage/update?id=2"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=2"]');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=3"]');
        $I->seeElement('a[href*="/tour-stage/update?id=3"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=3"]');
        $I->dontSeeElement('i.fa-regular.fa-circle-check[title="Completed"]');
    }

    public function openTourPresentAlreadyFlownViewAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);

        $this->checkTourPresentAlreadyFlownViewCommon($I);

        $I->see('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=2"]');
        $I->seeElement('a[href*="/tour-stage/update?id=2"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=2"]');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=3"]');
        $I->seeElement('a[href*="/tour-stage/update?id=3"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=3"]');
        $I->dontSeeElement('i.fa-regular.fa-circle-check[title="Completed"]');
    }

    public function openTourPresentAlreadyFlownViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkTourPresentAlreadyFlownViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=2"]');
        $I->dontSeeElement('a[href*="/tour-stage/update?id=2"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=2"]');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=3"]');
        $I->dontSeeElement('a[href*="/tour-stage/update?id=3"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=3"]');
        $I->dontSeeElement('i.fa-regular.fa-circle-check[title="Completed"]');
    }

    public function openTourPresentAlreadyFlownViewAsUserAlreadyFlown(\FunctionalTester $I)
    {
        $I->amLoggedInAs(5);

        $this->checkTourPresentAlreadyFlownViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=2"]');
        $I->dontSeeElement('a[href*="/tour-stage/update?id=2"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=2"]');
        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=3"]');
        $I->dontSeeElement('a[href*="/tour-stage/update?id=3"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=3"]');
        $I->seeElement('i.fa-regular.fa-circle-check[title="Completed"]');
    }

    public function openTourPresentAlreadyFlownViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour/view', [ 'id' => '3' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    private function checkTourPresentNotFlownViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('tour/view', [ 'id' => '5' ]);

        $I->see('Tour present 2');
        $I->see('Tour without flights reported');
        $I->see('Tour Stages');
        $I->see('LEMD');
        $I->see('LEVC');
        $I->see('400');
    }

    public function openTourPresentNotFlownViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkTourPresentNotFlownViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=5"]');
        $I->seeElement('a[href*="/tour-stage/update?id=5"]');
        $I->seeElement('a[href*="/tour-stage/delete?id=5"]');
        $I->dontSeeElement('i.fa-regular.fa-circle-check[title="Completed"]');
    }

    public function openTourPresentNotFlownViewAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);

        $this->checkTourPresentNotFlownViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=5"]');
        $I->seeElement('a[href*="/tour-stage/update?id=5"]');
        $I->seeElement('a[href*="/tour-stage/delete?id=5"]');
        $I->dontSeeElement('i.fa-regular.fa-circle-check[title="Completed"]');
    }

    public function openTourPresentNotFlownViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkTourPresentNotFlownViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->seeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=5"]');
        $I->dontSeeElement('a[href*="/tour-stage/update?id=5"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=5"]');
        $I->dontSeeElement('i.fa-regular.fa-circle-check[title="Completed"]');
    }


    public function openTourPresentNotFlownViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour/view', [ 'id' => '5' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    private function checkTourFutureViewCommon(\FunctionalTester $I) {
        $I->amOnRoute('tour/view', [ 'id' => '4' ]);

        $I->see('Tour not started');
        $I->see('Tour that will start in the future');
        $I->see('Tour Stages');
        $I->see('LEMD');
        $I->see('LEVC');
        $I->see('400');
    }

    public function openTourFutureViewAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);

        $this->checkTourFutureViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=4"]');
        $I->seeElement('a[href*="/tour-stage/update?id=4"]');
        $I->seeElement('a[href*="/tour-stage/delete?id=4"]');
    }

    public function openTourFutureViewAsTourMgr(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10);

        $this->checkTourFutureViewCommon($I);

        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=4"]');
        $I->seeElement('a[href*="/tour-stage/update?id=4"]');
        $I->seeElement('a[href*="/tour-stage/delete?id=4"]');
    }

    public function openTourFutureViewAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $this->checkTourFutureViewCommon($I);

        $I->dontSee('Update', 'a');
        $I->dontSee('Delete', 'a');

        $I->dontSeeElement('a[href*="/submitted-flight-plan/select-aircraft-tour?tour_stage_id=4"]');
        $I->dontSeeElement('a[href*="/tour-stage/update?id=4"]');
        $I->dontSeeElement('a[href*="/tour-stage/delete?id=4"]');
    }

    public function openTourFutureViewAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('tour/view', [ 'id' => '4' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

}