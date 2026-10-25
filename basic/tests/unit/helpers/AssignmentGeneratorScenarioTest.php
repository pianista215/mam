<?php

namespace tests\unit\helpers;

use app\helpers\AssignmentGenerator;
use app\models\Aircraft;
use app\models\Client;
use app\models\Flight;
use tests\unit\BaseUnitTest;

/**
 * Realistic scenarios on a Spanish network with real coordinates and approximate runway lengths,
 * showing that every kind of client produces sensible assignments for every kind of aircraft.
 *
 * Aircraft (range, average flown distance → window, minimum runway learnt from takeoffs):
 * - C182: 915 NM, 175 NM → [87.5, 210], 500 m; small: no runway above 3000 m
 * - C172: 730 NM, 100 NM → [50, 120], 500 m; small
 * - King Air 350: 1750 NM, 334 NM → [167, 400.8], 1484 m (the real second shortest King Air takeoff)
 * - A320: 3300 NM, 400 NM → [200, 480], 2700 m
 */
class AssignmentGeneratorScenarioTest extends BaseUnitTest
{
    use AssignmentGeneratorTestTrait;

    // Airports with a runway longer than 3000 m
    private const BIG_AIRPORTS = ['LEMD', 'LEBL', 'LEZL', 'LEMG', 'LEVC', 'LPPT'];

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
            'LPPT' => [38.7813, -9.1359, [3805], self::$PORTUGAL],
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
        foreach ($airports as $icao => $data) {
            $this->createAirportAt($icao, $data[0], $data[1], $data[2], $data[3] ?? self::$SPAIN);
        }

        $this->carrier = $this->createClient('Meseta Airlines', 'LEMD', ['LEBL', 'LEZL', 'LEMG']);
        $this->aeroclub = $this->createClient('Aeroclub Cuatro Vientos', 'LECU');
        $this->regional = $this->createClient('Regional Norte', 'LEBB', ['LEAS']);
        $this->cargo = $this->createClient('Pyrenees Cargo', 'LEHC', ['LELO', 'LESO']);
    }

    private function c182(string $location): Aircraft
    {
        return $this->createAircraft(915, $location, 175, 500);
    }

    private function c172(string $location): Aircraft
    {
        return $this->createAircraft(730, $location, 100, 500);
    }

    private function kingAir(string $location): Aircraft
    {
        return $this->createAircraft(1750, $location, 334, 1484);
    }

    private function a320(string $location): Aircraft
    {
        return $this->createAircraft(3300, $location, 400, 2700);
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

    // --- Big carrier network (A320, King Air) ---

    public function testA320AtCarrierHeadquartersFliesTheCarrierNetwork()
    {
        foreach ($this->runs($this->a320('LEMD')) as $result) {
            $this->assertSame(
                ["LEBL#{$this->carrier->id}", "LEMG#{$this->carrier->id}", "LEZL#{$this->carrier->id}"],
                $this->pairsOf($result)
            );
        }
    }

    public function testA320AtCarrierHubCanReturnToHeadquarters()
    {
        foreach ($this->runs($this->a320('LEBL')) as $result) {
            $this->assertSame(['LEMD', 'LEMG', 'LEZL'], $this->destinationsOf($result));
        }
    }

    public function testA320WithRegularRouteGetsAboutSeventyPercentOfTheOffer()
    {
        $this->createRoute('MES001', 'LEMD', 'LEBL');

        $sizes = array_map('count', $this->runs($this->a320('LEMD'), 100));

        $this->assertLessThanOrEqual(3, max($sizes));
        $this->assertLessThan(3, min($sizes), 'The lottery should drop some assignments');
        $average = array_sum($sizes) / count($sizes);
        $this->assertGreaterThan(1.7, $average); // expected 2.1
        $this->assertLessThan(2.5, $average);
    }

    public function testLongHaulA320GetsNothingWithinSpain()
    {
        // Usually flown 1000 NM: window [500, 1200], no Spanish client airport is that far from Madrid
        $longHaul = $this->createAircraft(3300, 'LEMD', 1000, 2700);

        $this->assertSame([], AssignmentGenerator::generate($longHaul));
    }

    public function testKingAirAtCarrierHeadquartersFliesTheCarrierNetwork()
    {
        foreach ($this->runs($this->kingAir('LEMD')) as $result) {
            $this->assertSame(['LEBL', 'LEMG', 'LEZL'], $this->destinationsOf($result));
        }
    }

    public function testKingAirAtRegionalHeadquartersSkipsTheTooCloseHub()
    {
        // LEAS (137 NM) is too close for the King Air's usual 334 NM. Cuatro Vientos (1500 m) is fine for its 1484 m.
        // Lisbon (391 NM) would fit, but Regional Norte is a Spanish-only client and Spain has enough options.
        $results = $this->runs($this->kingAir('LEBB'), 100);
        foreach ($results as $result) {
            $this->assertCount(3, $result);
            $this->assertSame([$this->regional->id => 3], $this->countByClient($result));
        }

        $this->assertSame(
            ['LEBL', 'LECU', 'LEGR', 'LEMD', 'LESA', 'LEVC', 'LEZL'],
            $this->unionOfDestinations($results)
        );
    }

    public function testKingAirIgnoresHeliportsAndAvoidsAirBases()
    {
        // Both fit the King Air's window from Bilbao and have long runways
        $this->createAirportAt('LEPS', 41.1474, 1.1672, [2455.6], self::$SPAIN, 'Mas Passamaner Heliport');
        $this->createAirportAt('LETO', 40.4967, -3.4458, [4100], self::$SPAIN, 'Torrejon Air Base');

        $union = $this->unionOfDestinations($this->runs($this->kingAir('LEBB'), 100));

        $this->assertSame(['LEBL', 'LECU', 'LEGR', 'LEMD', 'LESA', 'LEVC', 'LEZL'], $union);
    }

    // --- Country preference (King Air around Vigo and Porto) ---

    private function createGalicianAirports(): void
    {
        $this->createAirportAt('LEVX', 42.2318, -8.6268, [2400]);
        $this->createAirportAt('LPPR', 41.2481, -8.6814, [3480], self::$PORTUGAL);
    }

    public function testSpanishClientInVigoInventsSpanishDestinationsOnly()
    {
        $this->createGalicianAirports();
        $client = $this->createClient('Galicia Foods', 'LEVX');

        $results = $this->runs($this->kingAir('LEVX'), 100);
        foreach ($results as $result) {
            $this->assertSame([$client->id => 3], $this->countByClient($result));
        }

        // Lisbon (208 NM) and Porto (59 NM, too close) are never offered
        $this->assertSame(
            ['LEBB', 'LEBG', 'LECU', 'LEGR', 'LEHC', 'LELO', 'LEMD', 'LEMG', 'LESO', 'LEZL'],
            $this->unionOfDestinations($results)
        );
    }

    public function testIberianClientInVigoCanInventPortugueseDestinations()
    {
        $this->createGalicianAirports();
        $this->createClient('Atlantic Freight', 'LEVX', ['LPPR']);

        $union = $this->unionOfDestinations($this->runs($this->kingAir('LEVX'), 100));

        $this->assertContains('LPPT', $union);
        $this->assertNotContains('LPPR', $union); // own hub, but only 59 NM away
    }

    public function testPortugueseClientWithNothingSuitableAtHomeGoesToSpain()
    {
        // From Porto the only Portuguese airport is Lisbon (150 NM), below the King Air's 167 NM minimum
        $this->createGalicianAirports();
        $client = $this->createClient('Porto Wines', 'LPPR');

        foreach ($this->runs($this->kingAir('LPPR')) as $result) {
            $this->assertSame([$client->id => 3], $this->countByClient($result));
            $this->assertNotContains('LPPT', $this->destinationsOf($result));
        }
    }

    // --- Variety ---

    public function testClientsSharingAnAirportDoNotFloodItWithAssignments()
    {
        // Three more clients based at Bilbao next to Regional Norte
        $this->createClient('Bilbao Steel', 'LEBB');
        $this->createClient('Bilbao Banking', 'LEBB');
        $this->createClient('Bilbao Energy', 'LEBB');

        foreach ($this->runs($this->c182('LESA')) as $result) {
            // Reverse mode from Salamanca: LEBB, LEAS and LELO are the only client airports in the window.
            // Bilbao is shared by four clients but offered at most twice, so the offer stays at 4
            $this->assertSame(['LEAS', 'LEBB', 'LEBB', 'LELO'], $this->destinationsOf($result));
            // The three distinct destinations come first
            $this->assertCount(3, array_unique(array_column(array_slice($result, 0, 3), 'destination')));
        }
    }

    // --- Headquarters-only client (C182) ---

    public function testC182AtAeroclubGetsThreeInventedSmallAirportAssignments()
    {
        foreach ($this->runs($this->c182('LECU')) as $result) {
            $this->assertCount(3, $result);
            $this->assertSame([$this->aeroclub->id => 3], $this->countByClient($result));
            $this->assertNoBigAirports($this->destinationsOf($result));
            $this->assertNotContains('LEHX', $this->destinationsOf($result));
        }
    }

    public function testC182AtAeroclubNeverGoesToBarajasEvenThoughItIsNextDoor()
    {
        $union = $this->unionOfDestinations($this->runs($this->c182('LECU'), 100));

        // Every small airport between 87.5 and 210 NM from Cuatro Vientos, and nothing else.
        // LELN (exactly 3000 m) is allowed; LESA (86 NM) is too close and LEAS (216 NM) too far.
        $this->assertSame(['LEBB', 'LEBG', 'LEGR', 'LEHC', 'LELN', 'LELO', 'LESO'], $union);
    }

    // --- Client with a single hub (C182) ---

    public function testC182AtRegionalHeadquartersGetsItsHubPlusTwoInventedOnes()
    {
        $fillers = [];
        foreach ($this->runs($this->c182('LEBB')) as $result) {
            $destinations = $this->destinationsOf($result);
            $this->assertCount(3, $result);
            $this->assertSame([$this->regional->id => 3], $this->countByClient($result));
            $this->assertContains('LEAS', $destinations);
            $this->assertNoBigAirports($destinations);
            $fillers = array_merge($fillers, array_diff($destinations, ['LEAS']));
        }

        $fillers = array_values(array_unique($fillers));
        sort($fillers);
        $this->assertSame(['LECU', 'LEHC', 'LELN', 'LESA'], $fillers);
    }

    public function testC182AtRegionalHubAlwaysGetsTheWayBackToHeadquarters()
    {
        foreach ($this->runs($this->c182('LEAS')) as $result) {
            $this->assertContains('LEBB', $this->destinationsOf($result));
            $this->assertNoBigAirports($this->destinationsOf($result));
        }
    }

    // --- Client with headquarters and two hubs (C182, C172) ---

    public function testC182AtCargoHeadquartersGetsBothHubsPlusOneInvented()
    {
        foreach ($this->runs($this->c182('LEHC')) as $result) {
            $destinations = $this->destinationsOf($result);
            $this->assertCount(3, $result);
            $this->assertContains('LELO', $destinations);
            $this->assertContains('LESO', $destinations);
            $this->assertCount(1, array_intersect($destinations, ['LEBB', 'LEBG', 'LECU']));
        }
    }

    public function testC172AtCargoHeadquartersStaysLocal()
    {
        // Window [50, 120]: only the two hubs; LEBL (117 NM) is in reach but too big for a C172
        foreach ($this->runs($this->c172('LEHC')) as $result) {
            $this->assertSame(['LELO', 'LESO'], $this->destinationsOf($result));
        }
    }

    // --- Airport without clients (reverse mode) ---

    public function testC182AtAirportWithoutClientsOnlyGetsSmallClientAirports()
    {
        foreach ($this->runs($this->c182('LESA')) as $result) {
            $this->assertSame(
                ["LEAS#{$this->regional->id}", "LEBB#{$this->regional->id}", "LELO#{$this->cargo->id}"],
                $this->pairsOf($result)
            );
        }
    }

    public function testA320AtAirportWithoutClientsOnlyGetsCarrierAirports()
    {
        // From Valencia: LEMD (154 NM) and LEBL (160 NM) are too close; the other client airports have no 3000 m runway
        foreach ($this->runs($this->a320('LEVC')) as $result) {
            $this->assertSame(
                ["LEMG#{$this->carrier->id}", "LEZL#{$this->carrier->id}"],
                $this->pairsOf($result)
            );
        }
    }

    // --- Origin must suit the aircraft ---

    public function testAircraftAtAnAirportItShouldNotUseGetsNothing()
    {
        // C182 at Barajas (4350 m, too big), A320 at Salamanca (2513 m, below its 2700 m)
        $this->assertSame([], AssignmentGenerator::generate($this->c182('LEMD')));
        $this->assertSame([], AssignmentGenerator::generate($this->a320('LESA')));
    }

    public function testKingAirCanStartFromTheShortRunwaysItHasUsedBefore()
    {
        foreach (['LECU', 'LESO'] as $origin) {
            foreach ($this->runs($this->kingAir($origin)) as $result) {
                $this->assertNotEmpty($result, "King Air at $origin");
            }
        }
    }

    public function testAircraftThatAlwaysUsedLongRunwaysCannotStartFromCuatroVientos()
    {
        // TBM-like: same range as the King Air, but its second shortest takeoff was 2281 m
        $tbm = $this->createAircraft(1730, 'LECU', 305, 2281);

        $this->assertSame([], AssignmentGenerator::generate($tbm));
    }

    public function testMinimumRunwayComesFromRealTakeoffs()
    {
        // Two takeoffs from Cuatro Vientos (1500 m, runway 01/19) and one from Barajas (runway 01, 4350 m)
        $kingAir = $this->createAircraftWithoutHistory(1750, 'LEBB');
        $this->recordFlightFrom($kingAir, 334, Flight::STATUS_FINISHED, 'LECU', '01');
        $this->recordFlightFrom($kingAir, 334, Flight::STATUS_FINISHED, 'LECU', '19');
        $this->recordFlightFrom($kingAir, 334, Flight::STATUS_FINISHED, 'LEMD', '01');

        $this->assertSame(1500.0, AssignmentGenerator::minTakeoffRunway($kingAir->aircraft_configuration_id));
        $this->assertContains('LECU', $this->unionOfDestinations($this->runs($kingAir, 100)));
    }

    public function testC182AtAnAirportWithExactly3000mGetsAssignments()
    {
        // León has exactly 3000 m, so it is still a small airport; no client there, reverse mode
        foreach ($this->runs($this->c182('LELN')) as $result) {
            $this->assertNotEmpty($result);
            $this->assertNoBigAirports($this->destinationsOf($result));
        }
    }

    public function testSmallAircraftAtPlainAirportWithOnlyBigClientAirportsAroundGetsNothing()
    {
        // Window [50, 120]: around Granada only Seville (102 NM) is a client airport, and it is big
        $this->assertSame([], AssignmentGenerator::generate($this->c172('LEGR')));
    }

    // --- Flight history ---

    public function testBarelyFlownAircraftGetsNothingUntilItsThirdFlight()
    {
        $c182 = $this->createAircraftWithoutHistory(915, 'LECU');
        $this->assertSame([], AssignmentGenerator::generate($c182));

        $this->recordFlight($c182, 150, Flight::STATUS_FINISHED, 500);
        $this->recordFlight($c182, 200, Flight::STATUS_FINISHED, 500);
        $this->assertSame([], AssignmentGenerator::generate($c182));

        $this->recordFlight($c182, 175, Flight::STATUS_FINISHED, 500);

        $this->assertCount(3, AssignmentGenerator::generate($c182));
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

        $this->assertGreaterThanOrEqual(5, count($visited));
    }

    public function testA320JourneyNeverGetsStuckNorVisitsShortRunways()
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

    public function testKingAirJourneyNeverGetsStuck()
    {
        $kingAir = $this->kingAir('LEBB');

        mt_srand(11);
        for ($hop = 0; $hop < 100; $hop++) {
            $result = AssignmentGenerator::generate($kingAir);
            $this->assertValidAssignments($result, $kingAir);
            $this->assertNotEmpty($result, "King Air stuck at {$kingAir->location}");
            $this->assertNotSame('LEHX', $kingAir->location);
            $kingAir = $this->moveAircraft($kingAir, $result[mt_rand(0, count($result) - 1)]['destination']);
        }
        mt_srand();
    }
}
