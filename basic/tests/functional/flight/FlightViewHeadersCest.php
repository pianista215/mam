<?php

namespace tests\functional\flight;

use app\models\Flight;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ClientFixture;
use tests\fixtures\FlightFixture;
use tests\fixtures\FlightReportFixture;
use FunctionalTester;
use Yii;

class FlightViewHeadersCest
{

    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'flightReport' => FlightReportFixture::class,
            'client' => ClientFixture::class,
        ];
    }

    public function openFlightViewTour(FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 1]);
        $I->seeResponseCodeIs(200);
        $I->see('Stage Tour actual reported #1 (LEBL-LEMD)');
    }

    public function openFlightViewRoute(FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 2]);
        $I->seeResponseCodeIs(200);
        $I->see('Route F002');
    }

    public function openFlightViewCharter(FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 6]);
        $I->seeResponseCodeIs(200);
        $I->see('Charter Flight (LEMD-LEBL)');
    }

    private function convertToAssignmentFlight(int $flightId, int $clientId): Flight
    {
        // Reuse a flight with report so the whole view renders
        $flight = Flight::findOne($flightId);
        $flight->flight_type = Flight::TYPE_ASSIGNMENT;
        $flight->code = 'ASSIGNMENT';
        $flight->client_id = $clientId;
        if (!$flight->save(false)) {
            throw new \RuntimeException('Could not convert the flight');
        }
        return $flight;
    }

    public function openFlightViewAssignment(FunctionalTester $I)
    {
        $flight = $this->convertToAssignmentFlight(2, 2);

        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => $flight->id]);
        $I->seeResponseCodeIs(200);
        $I->see('Assignment', '.fs-5');
        $I->see('Canary Fresh Fish (' . $flight->departure . '-' . $flight->arrival . ')');
        $I->seeElement('img[alt="Canary Fresh Fish"]');
        $I->dontSee('Route ASSIGNMENT');
        $I->dontSee('Route F002');
        $I->dontSee('Charter flight');
    }

    public function openFlightViewRouteHasNoClientLogo(FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('flight/view', ['id' => 2]);
        $I->seeResponseCodeIs(200);
        $I->dontSeeElement('img[alt="Iberian Logistics"]');
        $I->dontSeeElement('img[alt="Canary Fresh Fish"]');
    }

}
