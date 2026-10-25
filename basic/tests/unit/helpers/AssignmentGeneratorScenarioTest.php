<?php

namespace tests\unit\helpers;

use app\helpers\AssignmentGenerator;
use app\models\Aircraft;
use app\models\Client;
use tests\unit\BaseUnitTest;

/**
 * Realistic scenarios on a Spanish network with real coordinates and approximate runway lengths,
 * showing that every kind of client produces sensible assignments and that small aircraft are
 * never sent to big airports.
 */
class AssignmentGeneratorScenarioTest extends BaseUnitTest
{
    use AssignmentGeneratorTestTrait;

    private const C182_RANGE = 900;   // small aircraft, limit 720 NM
    private const A320_RANGE = 3300;  // large aircraft, limit 2640 NM

    // Airports with a runway longer than 3000 m
    private const BIG_AIRPORTS = ['LEMD', 'LEBL', 'LEZL', 'LEMG', 'LEVC', 'LPPT'];

    // Airports with runways, none longer than 3000 m
    private const SMALL_AIRPORTS = ['LEAS', 'LEBB', 'LEBG', 'LECU', 'LEGR', 'LEHC', 'LELN', 'LELO', 'LESA', 'LESO'];

    private Client $carrier;
    private Client $aeroclub;
    private Client $regional;
    private Client $cargo;

    protected function _before()
    {
        parent::_before();
        $this->clearDatabase();
        $this->createCountry();

        $airports = [
            'LEMD' => [40.4719, -3.5626, [4350, 4100, 3500]],
            'LEBL' => [41.2971, 2.0785, [3352, 2660]],
            'LEZL' => [37.4180, -5.8931, [3360]],
            'LEMG' => [36.6749, -4.4991, [3200, 2750]],
            'LEVC' => [39.4893, -0.4816, [3215]],
            'LPPT' => [38.7813, -9.1359, [3805]],
            'LEBB' => [43.3011, -2.9106, [2600, 2000]],
            'LEAS' => [43.5636, -6.0346, [2200]],
            'LESA' => [40.9521, -5.5020, [2513]],
            'LEBG' => [42.3576, -3.6206, [2100]],
            'LELO' => [42.4610, -2.3223, [2000]],
            'LESO' => [43.3565, -1.7906, [1754]],
            'LECU' => [40.3707, -3.7851, [1500]],
            'LEHC' => [42.0761, -0.3163, [2100]],
            'LEGR' => [37.1887, -3.7774, [2900]],
            'LELN' => [42.5890, -5.6556, [3000]],
            'LEHX' => [40.4500, -3.7000, []], // heliport without runways
        ];
        foreach ($airports as $icao => [$lat, $lon, $runways]) {
            $this->createAirportAt($icao, $lat, $lon, $runways);
        }

        $this->carrier = $this->createClient('Meseta Airlines', 'LEMD', ['LEBL', 'LEZL', 'LEMG']);
        $this->aeroclub = $this->createClient('Aeroclub Cuatro Vientos', 'LECU');
        $this->regional = $this->createClient('Regional Norte', 'LEBB', ['LEAS']);
        $this->cargo = $this->createClient('Pyrenees Cargo', 'LEHC', ['LELO', 'LESO']);
    }

    private function c182(string $location): Aircraft
    {
        return $this->createAircraft(self::C182_RANGE, $location);
    }

    private function a320(string $location): Aircraft
    {
        return $this->createAircraft(self::A320_RANGE, $location);
    }

    /** @return array<int, array> Seeded random runs, each one validated */
    private function runs(Aircraft $aircraft, int $count = 50, int $seed = 2026): array
    {
        $results = $this->generateManyTimes($aircraft, $count, $seed);
        foreach ($results as $result) {
            $this->assertValidAssignments($result, $aircraft);
        }
        return $results;
    }

    private function assertNoBigAirports(array $icaos): void
    {
        $this->assertSame([], array_values(array_intersect($icaos, self::BIG_AIRPORTS)), 'Small aircraft sent to a big airport');
    }

    // --- Big carrier network ---

    public function testA320AtCarrierHeadquartersFliesTheCarrierNetwork()
    {
        $a320 = $this->a320('LEMD');

        foreach ($this->runs($a320) as $result) {
            $this->assertSame(
                ["LEBL#{$this->carrier->id}", "LEMG#{$this->carrier->id}", "LEZL#{$this->carrier->id}"],
                $this->pairsOf($result)
            );
        }
    }

    public function testA320AtCarrierHubCanReturnToHeadquarters()
    {
        $a320 = $this->a320('LEBL');

        foreach ($this->runs($a320) as $result) {
            $this->assertSame(['LEMD', 'LEMG', 'LEZL'], $this->destinationsOf($result));
        }
    }

    public function testA320WithRegularRouteGetsAboutSeventyPercentOfTheOffer()
    {
        $this->createRoute('MES001', 'LEMD', 'LEBL');
        $a320 = $this->a320('LEMD');

        $sizes = array_map('count', $this->runs($a320, 100));

        $this->assertLessThanOrEqual(3, max($sizes));
        $this->assertLessThan(3, min($sizes), 'The lottery should drop some assignments');
        $average = array_sum($sizes) / count($sizes);
        $this->assertGreaterThan(1.7, $average); // expected 2.1
        $this->assertLessThan(2.5, $average);
    }

    // --- Headquarters-only client ---

    public function testC182AtAeroclubGetsThreeInventedSmallAirportAssignments()
    {
        $c182 = $this->c182('LECU');

        foreach ($this->runs($c182) as $result) {
            $this->assertCount(3, $result);
            $this->assertSame([$this->aeroclub->id => 3], $this->countByClient($result));
            $this->assertEmpty(array_diff($this->destinationsOf($result), self::SMALL_AIRPORTS));
            $this->assertNotContains('LEHX', $this->destinationsOf($result));
        }
    }

    public function testC182AtAeroclubNeverGoesToBarajasEvenThoughItIsNextDoor()
    {
        $c182 = $this->c182('LECU');

        $union = $this->unionOfDestinations($this->runs($c182, 100));

        $this->assertNotContains('LEMD', $union);
        $this->assertNoBigAirports($union);
        // Plenty of variety among the small airports
        $this->assertGreaterThanOrEqual(7, count($union));
    }

    public function testC182CanBeSentToAnAirportWithExactlyThreeThousandMetres()
    {
        $c182 = $this->c182('LECU');

        $this->assertContains('LELN', $this->unionOfDestinations($this->runs($c182, 100)));
    }

    // --- Client with a single hub ---

    public function testC182AtRegionalHeadquartersGetsItsHubPlusTwoInventedOnes()
    {
        $c182 = $this->c182('LEBB');

        $fillers = [];
        foreach ($this->runs($c182) as $result) {
            $destinations = $this->destinationsOf($result);
            $this->assertCount(3, $result);
            $this->assertSame([$this->regional->id => 3], $this->countByClient($result));
            $this->assertContains('LEAS', $destinations);
            $this->assertNoBigAirports($destinations);
            $fillers = array_merge($fillers, array_diff($destinations, ['LEAS']));
        }

        $this->assertGreaterThanOrEqual(5, count(array_unique($fillers)), 'Invented assignments should vary');
    }

    public function testC182AtRegionalHubAlwaysGetsTheWayBackToHeadquarters()
    {
        $c182 = $this->c182('LEAS');

        foreach ($this->runs($c182) as $result) {
            $this->assertContains('LEBB', $this->destinationsOf($result));
            $this->assertNoBigAirports($this->destinationsOf($result));
        }
    }

    // --- Client with headquarters and two hubs ---

    public function testC182AtCargoHeadquartersGetsBothHubsPlusOneInvented()
    {
        $c182 = $this->c182('LEHC');

        foreach ($this->runs($c182) as $result) {
            $destinations = $this->destinationsOf($result);
            $this->assertCount(3, $result);
            $this->assertContains('LELO', $destinations);
            $this->assertContains('LESO', $destinations);
            $this->assertNoBigAirports($destinations);
        }
    }

    public function testShortRangeTrainerAtCargoHeadquartersStaysLocal()
    {
        $trainer = $this->createAircraft(300, 'LEHC'); // limit 240 NM

        foreach ($this->runs($trainer) as $result) {
            $destinations = $this->destinationsOf($result);
            $this->assertContains('LELO', $destinations);
            $this->assertContains('LESO', $destinations);
            // Far away small airports are out of reach
            $this->assertNotContains('LEGR', $destinations);
            $this->assertNotContains('LEAS', $destinations);
        }
    }

    // --- Airport without clients (reverse mode) ---

    public function testC182AtAirportWithoutClientsOnlyGetsSmallClientAirports()
    {
        $c182 = $this->c182('LESA');
        $owners = [
            'LECU' => $this->aeroclub->id,
            'LEBB' => $this->regional->id,
            'LEAS' => $this->regional->id,
            'LEHC' => $this->cargo->id,
            'LELO' => $this->cargo->id,
            'LESO' => $this->cargo->id,
        ];

        foreach ($this->runs($c182) as $result) {
            $this->assertCount(AssignmentGenerator::COUNT, $result);
            foreach ($result as $assignment) {
                $this->assertArrayHasKey($assignment['destination'], $owners);
                $this->assertSame($owners[$assignment['destination']], $assignment['client_id']);
            }
            $this->assertArrayNotHasKey($this->carrier->id, $this->countByClient($result));
        }
    }

    public function testA320AtAirportWithoutClientsCanGetCarrierAirportsButAtMostThree()
    {
        $a320 = $this->a320('LESA');

        $results = $this->runs($a320);
        foreach ($results as $result) {
            $this->assertLessThanOrEqual(3, $this->countByClient($result)[$this->carrier->id] ?? 0);
        }

        $union = $this->unionOfDestinations($results);
        $this->assertNotEmpty(array_intersect($union, ['LEMD', 'LEBL', 'LEZL', 'LEMG']));
        // Plain airports are never offered in reverse mode
        $this->assertEmpty(array_intersect($union, ['LESA', 'LEBG', 'LEGR', 'LELN', 'LEVC', 'LPPT', 'LEHX']));
    }

    public function testSmallAircraftAtPlainAirportWithOnlyBigClientAirportsAroundGetsNothing()
    {
        // Limit 120 NM: around Granada only Malaga and Seville are client airports, and both are big
        $trainer = $this->createAircraft(150, 'LEGR');

        $this->assertSame([], AssignmentGenerator::generate($trainer));
    }

    // --- Long journeys ---

    public function testC182JourneyAcrossSpainNeverGetsStuckNorVisitsBigAirports()
    {
        $c182 = $this->c182('LECU');

        mt_srand(99);
        $visited = [];
        for ($hop = 0; $hop < 100; $hop++) {
            $result = AssignmentGenerator::generate($c182);
            $this->assertValidAssignments($result, $c182);
            $this->assertNotEmpty($result, "C182 stuck at {$c182->location}");

            $next = $result[mt_rand(0, count($result) - 1)]['destination'];
            $this->assertNotContains($next, self::BIG_AIRPORTS);
            $visited[$next] = true;
            $c182 = $this->moveAircraft($c182, $next);
        }
        mt_srand();

        $this->assertGreaterThanOrEqual(6, count($visited));
    }

    public function testA320JourneyNeverGetsStuck()
    {
        $a320 = $this->a320('LEMD');

        mt_srand(5);
        for ($hop = 0; $hop < 100; $hop++) {
            $result = AssignmentGenerator::generate($a320);
            $this->assertValidAssignments($result, $a320);
            $this->assertNotEmpty($result, "A320 stuck at {$a320->location}");
            $a320 = $this->moveAircraft($a320, $result[mt_rand(0, count($result) - 1)]['destination']);
        }
        mt_srand();
    }
}
