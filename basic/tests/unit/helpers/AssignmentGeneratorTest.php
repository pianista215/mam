<?php

namespace tests\unit\helpers;

use app\helpers\AssignmentGenerator;
use app\helpers\GeoUtils;
use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftType;
use app\models\Airport;
use app\models\Client;
use app\models\Country;
use app\models\Route;
use tests\unit\BaseUnitTest;

class AssignmentGeneratorTest extends BaseUnitTest
{
    // Aircraft range is 1000 NM, so assignments are limited to 800 NM
    private const RANGE_NM = 1000;

    // Nautical miles per degree of latitude, matching GeoUtils::haversine
    private const NM_PER_DEG = 6371.0 * M_PI / 180 * 0.539957;

    private Aircraft $aircraft;

    protected function _before()
    {
        parent::_before();
        $this->clearDatabase();

        (new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']))->save(false);

        // All airports lie on the same meridian, so distances are exact and easy to reason about
        $this->createAirport('XORG', 0);
        $this->createAirport('XD01', 100);
        $this->createAirport('XD02', 200);
        $this->createAirport('XD03', 300);
        $this->createAirport('XD04', 400);
        $this->createAirport('XD05', 500);
        $this->createAirport('XD06', 600);
        $this->createAirport('XNEA', 750); // 75% of range
        $this->createAirport('XFAR', 850); // 85% of range: reachable, but beyond the 80% limit

        $type = new AircraftType(['icao_type_code' => 'B738', 'name' => 'Boeing 737-800', 'max_nm_range' => self::RANGE_NM]);
        $type->save(false);

        $config = new AircraftConfiguration([
            'aircraft_type_id' => $type->id,
            'name' => 'Standard',
            'pax_capacity' => 180,
            'cargo_capacity' => 2000,
            'crew' => 5,
            'mtow' => 79016,
            'oew' => 41413,
        ]);
        $config->save(false);

        $this->aircraft = new Aircraft([
            'aircraft_configuration_id' => $config->id,
            'registration' => 'EC-AAA',
            'name' => 'Test Aircraft',
            'location' => 'XORG',
            'hours_flown' => 0,
        ]);
        $this->aircraft->save(false);
    }

    private function createAirport(string $icao, float $distanceFromOriginNm): void
    {
        (new Airport([
            'icao_code' => $icao,
            'name' => $icao,
            'latitude' => $distanceFromOriginNm / self::NM_PER_DEG,
            'longitude' => 0.0,
            'city' => $icao,
            'country_id' => 1,
        ]))->save(false);
    }

    private function createClient(string $name, string $headquarters, array $hubs = []): Client
    {
        $client = new Client(['name' => $name, 'headquarters_icao' => $headquarters, 'hubIcaos' => $hubs]);
        $this->assertTrue($client->save(), json_encode($client->errors));
        return $client;
    }

    private function createRoute(string $code, string $departure, string $arrival): void
    {
        $route = new Route(['code' => $code, 'departure' => $departure, 'arrival' => $arrival]);
        $this->assertTrue($route->save(), json_encode($route->errors));
    }

    private function moveAircraft(string $icao): void
    {
        $this->aircraft->location = $icao;
        $this->aircraft->save(false);
        $this->aircraft = Aircraft::findOne($this->aircraft->id);
    }

    /** Random source that never swaps on shuffle and always accepts on the lottery */
    private function alwaysAccept(): callable
    {
        return fn(int $min, int $max) => $min;
    }

    /** Random source that never swaps on shuffle and always rejects on the lottery */
    private function alwaysReject(): callable
    {
        return fn(int $min, int $max) => $max;
    }

    /** Random source that never swaps on shuffle and returns the given values on the lottery (1..100) */
    private function scriptedLottery(array $values): callable
    {
        return function (int $min, int $max) use (&$values) {
            if ($min === 1 && $max === 100) {
                return array_shift($values);
            }
            return $min;
        };
    }

    private function destinations(array $assignments): array
    {
        $icaos = array_column($assignments, 'destination');
        sort($icaos);
        return $icaos;
    }

    private function createClientWithSixHubs(): Client
    {
        return $this->createClient('Big Client', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04', 'XD05', 'XD06']);
    }

    // --- No regular routes: every picked assignment is returned ---

    public function testNoRoutesReturnsFiveAssignments()
    {
        $client = $this->createClientWithSixHubs();

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysReject());

        $this->assertCount(AssignmentGenerator::COUNT, $result);
        $destinations = array_column($result, 'destination');
        $this->assertSame($destinations, array_unique($destinations));
        $this->assertNotContains('XORG', $destinations);
        foreach ($result as $assignment) {
            $this->assertSame($client->id, $assignment['client_id']);
            $this->assertContains($assignment['destination'], ['XD01', 'XD02', 'XD03', 'XD04', 'XD05', 'XD06']);
        }
    }

    public function testNoRoutesWithFewerCandidatesReturnsAll()
    {
        $this->createClient('Small Client', 'XORG', ['XD01', 'XD02']);

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysReject());

        $this->assertSame(['XD01', 'XD02'], $this->destinations($result));
    }

    public function testNoRelatedClientsReturnsEmpty()
    {
        $this->createClient('Elsewhere', 'XD01', ['XD02', 'XD03']);

        $this->assertSame([], AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept()));
    }

    public function testNoClientsAtAllReturnsEmpty()
    {
        $this->assertSame([], AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept()));
    }

    // --- Client relation with the origin ---

    public function testOriginAsHeadquarters()
    {
        $client = $this->createClient('HQ Client', 'XORG', ['XD01']);

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept());

        $this->assertSame([['destination' => 'XD01', 'client_id' => $client->id, 'distance_nm' => 100]], $result);
    }

    public function testOriginAsHubOffersHeadquartersAndOtherHubs()
    {
        $client = $this->createClient('Hub Client', 'XD01', ['XORG', 'XD02']);

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept());

        $this->assertSame(['XD01', 'XD02'], $this->destinations($result));
        $this->assertSame([$client->id, $client->id], array_column($result, 'client_id'));
    }

    public function testUnrelatedClientNeverAppears()
    {
        $related = $this->createClient('Related', 'XORG', ['XD01']);
        $this->createClient('Unrelated', 'XD02', ['XD03', 'XD04']);

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept());

        $this->assertSame([['destination' => 'XD01', 'client_id' => $related->id, 'distance_nm' => 100]], $result);
    }

    // --- Range ---

    public function testDestinationsBeyondEightyPercentOfRangeAreExcluded()
    {
        $this->createClient('Range Client', 'XORG', ['XNEA', 'XFAR']);

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept());

        $this->assertSame(['XNEA'], $this->destinations($result));
    }

    public function testHeadquartersBeyondRangeIsExcluded()
    {
        $this->createClient('Far HQ', 'XFAR', ['XORG', 'XD01']);

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept());

        $this->assertSame(['XD01'], $this->destinations($result));
    }

    // --- Shared destinations ---

    public function testSharedHubProducesOneAssignmentPerClient()
    {
        $a = $this->createClient('Client A', 'XORG', ['XD01']);
        $b = $this->createClient('Client B', 'XORG', ['XD01']);

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept());

        $this->assertSame(['XD01', 'XD01'], $this->destinations($result));
        $clientIds = array_column($result, 'client_id');
        sort($clientIds);
        $this->assertSame([$a->id, $b->id], $clientIds);
    }

    public function testNoDuplicatePairsAcrossManyRuns()
    {
        $this->createClient('Client A', 'XORG', ['XD01', 'XD02', 'XD03']);
        $this->createClient('Client B', 'XD01', ['XORG', 'XD02', 'XD04']);

        for ($i = 0; $i < 50; $i++) {
            $result = AssignmentGenerator::generate($this->aircraft);

            $pairs = array_map(fn($a) => $a['destination'] . '-' . $a['client_id'], $result);
            $this->assertCount(AssignmentGenerator::COUNT, $result);
            $this->assertSame($pairs, array_unique($pairs));
            $this->assertNotContains('XORG', array_column($result, 'destination'));
        }
    }

    // --- Regular routes available: 70% lottery per assignment ---

    public function testRoutesAvailableAndLotteryAcceptsAll()
    {
        $this->createClientWithSixHubs();
        $this->createRoute('R001', 'XORG', 'XD02');

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept());

        $this->assertCount(AssignmentGenerator::COUNT, $result);
    }

    public function testRoutesAvailableAndLotteryRejectsAll()
    {
        $this->createClientWithSixHubs();
        $this->createRoute('R001', 'XORG', 'XD02');

        $this->assertSame([], AssignmentGenerator::generate($this->aircraft, $this->alwaysReject()));
    }

    public function testRoutesAvailableLotteryKeepsOnlyWinners()
    {
        $this->createClientWithSixHubs();
        $this->createRoute('R001', 'XORG', 'XD02');
        $all = AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept());

        // 70 is the last accepted value, 71 the first rejected one
        $result = AssignmentGenerator::generate($this->aircraft, $this->scriptedLottery([1, 71, 70, 100, 50]));

        $this->assertSame([$all[0], $all[2], $all[4]], $result);
    }

    public function testRoutesBeyondEightyPercentOfRangeDoNotCount()
    {
        $this->createClientWithSixHubs();
        $this->createRoute('R001', 'XORG', 'XFAR');

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysReject());

        $this->assertCount(AssignmentGenerator::COUNT, $result);
    }

    public function testRoutesFromOtherAirportsDoNotCount()
    {
        $this->createClientWithSixHubs();
        $this->createRoute('R001', 'XD01', 'XD02');
        $this->createRoute('R002', 'XD02', 'XORG');

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysReject());

        $this->assertCount(AssignmentGenerator::COUNT, $result);
    }

    public function testHasFlyableRoutes()
    {
        $this->createRoute('R001', 'XORG', 'XNEA');

        $this->assertTrue(AssignmentGenerator::hasFlyableRoutes('XORG', 800));
        $this->assertFalse(AssignmentGenerator::hasFlyableRoutes('XORG', 700));
        $this->assertFalse(AssignmentGenerator::hasFlyableRoutes('XNEA', 800));
    }

    // --- Dead-end safety ---

    public function testEveryDestinationOffersTheWayBack()
    {
        $this->createClient('Client A', 'XORG', ['XD01', 'XD02']);
        $this->createClient('Client B', 'XD01', ['XFAR']);
        $this->createRoute('R001', 'XORG', 'XD03');

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept());
        $this->assertNotEmpty($result);

        foreach (array_unique(array_column($result, 'destination')) as $destination) {
            $this->moveAircraft($destination);

            // No regular routes at the destination: the lottery must not apply
            $back = AssignmentGenerator::generate($this->aircraft, $this->alwaysReject());

            $this->assertNotEmpty($back, "Pilot stuck at $destination");
            $this->assertContains('XORG', array_column($back, 'destination'));
            $this->moveAircraft('XORG');
        }
    }

    public function testDestinationWithOnlyLongRoutesStillGetsAssignments()
    {
        $this->createClient('Client A', 'XORG', ['XD01']);
        $this->createRoute('R001', 'XD01', 'XFAR');
        $this->createRoute('R002', 'XFAR', 'XD01');
        $this->moveAircraft('XD01');

        // The only route from XD01 is 750 NM long; with a 900 NM range the limit is 720 NM, so it isn't flyable
        $this->aircraft->aircraftConfiguration->aircraftType->max_nm_range = 900;
        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysReject());

        $this->assertSame(['XORG'], $this->destinations($result));
    }

    // --- Invalid input ---

    public function testAircraftWithoutRangeReturnsEmpty()
    {
        $this->createClientWithSixHubs();
        $this->aircraft->aircraftConfiguration->aircraftType->max_nm_range = 0;

        $this->assertSame([], AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept()));
    }

    public function testAircraftAtUnknownAirportReturnsEmpty()
    {
        $this->createClientWithSixHubs();
        $aircraft = new Aircraft([
            'aircraft_configuration_id' => $this->aircraft->aircraft_configuration_id,
            'location' => 'ZZZZ',
        ]);

        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));
    }

    // --- Result shape ---

    public function testResultShapeAndDistance()
    {
        $client = $this->createClient('Shape Client', 'XORG', ['XNEA']);
        $origin = Airport::findOne(['icao_code' => 'XORG']);
        $dest = Airport::findOne(['icao_code' => 'XNEA']);

        $result = AssignmentGenerator::generate($this->aircraft, $this->alwaysAccept());

        $this->assertCount(1, $result);
        $this->assertSame(['destination', 'client_id', 'distance_nm'], array_keys($result[0]));
        $this->assertSame('XNEA', $result[0]['destination']);
        $this->assertSame($client->id, $result[0]['client_id']);
        $expected = (int) round(GeoUtils::haversine($origin->latitude, $origin->longitude, $dest->latitude, $dest->longitude, 'nm'));
        $this->assertSame($expected, $result[0]['distance_nm']);
    }
}
