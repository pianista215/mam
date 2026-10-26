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
 * - Saab 340 commuter: 935 NM, 34 seats, 175 NM → [87.5, 210], 1300 m
 * - Saab 340 on short hops: same, 100 NM → [50, 120]
 * - King Air 350: 1750 NM, 334 NM → [167, 400.8], 1484 m (the real second shortest King Air takeoff)
 * - A320: 3300 NM, 400 NM → [200, 480], 2700 m
 * General aviation (C172, C182, Maule: below 1000 NM and fewer than 20 seats) never gets assignments.
 */
class AssignmentGeneratorScenarioTest extends BaseUnitTest
{
    use AssignmentGeneratorTestTrait;

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

    private function commuter(string $location, float $averageNm = 175): Aircraft
    {
        return $this->createAircraft(935, $location, $averageNm, 1300, 34);
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

        foreach ($this->runs($this->commuter('LESA')) as $result) {
            // Reverse mode from Salamanca: LEMD, LEBB, LEAS and LELO are the client airports in the window.
            // Bilbao is shared by four clients but offered at most twice
            $this->assertSame(['LEAS', 'LEBB', 'LEBB', 'LELO', 'LEMD'], $this->destinationsOf($result));
            // The four distinct destinations come first
            $this->assertCount(4, array_unique(array_column(array_slice($result, 0, 4), 'destination')));
        }
    }

    // --- Headquarters-only client ---

    public function testCommuterAtAeroclubGetsThreeInventedAssignments()
    {
        foreach ($this->runs($this->commuter('LECU')) as $result) {
            $this->assertCount(3, $result);
            $this->assertSame([$this->aeroclub->id => 3], $this->countByClient($result));
            $this->assertNotContains('LEHX', $this->destinationsOf($result));
        }
    }

    public function testCommuterAtAeroclubCoversEveryAirportInItsWindow()
    {
        $union = $this->unionOfDestinations($this->runs($this->commuter('LECU'), 100));

        // Every Spanish airport between 87.5 and 210 NM from Cuatro Vientos, big ones included.
        // Barajas (12 NM) and LESA (86 NM) are too close, LEAS (216 NM) and LEMG (224 NM) too far
        $this->assertSame(['LEBB', 'LEBG', 'LEGR', 'LEHC', 'LELN', 'LELO', 'LESO', 'LEVC', 'LEZL'], $union);
    }

    // --- Client with a single hub ---

    public function testCommuterAtRegionalHeadquartersGetsItsHubPlusTwoInventedOnes()
    {
        $fillers = [];
        foreach ($this->runs($this->commuter('LEBB')) as $result) {
            $destinations = $this->destinationsOf($result);
            $this->assertCount(3, $result);
            $this->assertSame([$this->regional->id => 3], $this->countByClient($result));
            $this->assertContains('LEAS', $destinations);
            $fillers = array_merge($fillers, array_diff($destinations, ['LEAS']));
        }

        $fillers = array_values(array_unique($fillers));
        sort($fillers);
        $this->assertSame(['LECU', 'LEHC', 'LELN', 'LEMD', 'LESA'], $fillers);
    }

    public function testCommuterAtRegionalHubAlwaysGetsTheWayBackToHeadquarters()
    {
        foreach ($this->runs($this->commuter('LEAS')) as $result) {
            $this->assertContains('LEBB', $this->destinationsOf($result));
        }
    }

    // --- Client with headquarters and two hubs ---

    public function testCommuterAtCargoHeadquartersGetsBothHubsPlusOneInvented()
    {
        foreach ($this->runs($this->commuter('LEHC')) as $result) {
            $destinations = $this->destinationsOf($result);
            $this->assertCount(3, $result);
            $this->assertContains('LELO', $destinations);
            $this->assertContains('LESO', $destinations);
            $this->assertCount(1, array_intersect($destinations, ['LEBB', 'LEBG', 'LEBL', 'LECU', 'LEMD', 'LEVC']));
        }
    }

    public function testShortHopCommuterAtCargoHeadquartersStaysLocal()
    {
        // Window [50, 120]: the two hubs plus Barcelona (117 NM) as the only invented option
        foreach ($this->runs($this->commuter('LEHC', 100)) as $result) {
            $this->assertSame(['LEBL', 'LELO', 'LESO'], $this->destinationsOf($result));
        }
    }

    // --- Airport without clients (reverse mode) ---

    public function testCommuterAtAirportWithoutClientsOnlyGetsClientAirports()
    {
        foreach ($this->runs($this->commuter('LESA')) as $result) {
            $this->assertSame(
                ["LEAS#{$this->regional->id}", "LEBB#{$this->regional->id}", "LELO#{$this->cargo->id}", "LEMD#{$this->carrier->id}"],
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

    // --- General aviation ---

    public function testGeneralAviationNeverGetsAssignments()
    {
        $generalAviation = [
            'C172' => [730, 100, 4],
            'C182' => [915, 175, 4],
            'Maule M7' => [450, 139, 3],
        ];
        foreach ($generalAviation as $name => [$range, $average, $seats]) {
            foreach (['LECU', 'LEBB', 'LEHC', 'LESA'] as $origin) {
                $aircraft = $this->createAircraft($range, $origin, $average, 500, $seats);
                $this->assertSame([], AssignmentGenerator::generate($aircraft), "$name at $origin");
            }
        }
    }

    public function testSaabFliesFromBarajasButTheMauleDoesNot()
    {
        // Saab 340: 935 NM but 34 seats, so not general aviation. Maule M7: 450 NM, 3 seats
        $saab = $this->createAircraft(935, 'LEMD', 312, 1306, 34);
        $maule = $this->createAircraft(450, 'LEMD', 139, 1047, 3);

        $this->assertNotEmpty(AssignmentGenerator::generate($saab, $this->alwaysAccept()));
        $this->assertSame([], AssignmentGenerator::generate($maule));
    }

    // --- Origin must suit the aircraft ---

    public function testAircraftAtAnAirportWithShorterRunwaysThanUsualGetsNothing()
    {
        // A320 at Salamanca (2513 m, below its 2700 m)
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

    public function testShortHopCommuterAtGranadaGetsSeville()
    {
        // Window [50, 120]: Seville (102 NM) is the only client airport around Granada; Malaga (46 NM) is too close
        foreach ($this->runs($this->commuter('LEGR', 100)) as $result) {
            $this->assertSame(["LEZL#{$this->carrier->id}"], $this->pairsOf($result));
        }
    }

    // --- Flight history ---

    public function testBarelyFlownAircraftGetsNothingUntilItsThirdFlight()
    {
        $saab = $this->createAircraftWithoutHistory(935, 'LECU', 34);
        $this->assertSame([], AssignmentGenerator::generate($saab));

        $this->recordFlight($saab, 150, Flight::STATUS_FINISHED, 1300);
        $this->recordFlight($saab, 200, Flight::STATUS_FINISHED, 1300);
        $this->assertSame([], AssignmentGenerator::generate($saab));

        $this->recordFlight($saab, 175, Flight::STATUS_FINISHED, 1300);

        $this->assertCount(3, AssignmentGenerator::generate($saab));
    }

    // --- Long journeys ---

    public function testCommuterJourneyAcrossSpainNeverGetsStuck()
    {
        $saab = $this->commuter('LECU');

        mt_srand(99);
        $visited = [];
        for ($hop = 0; $hop < 100; $hop++) {
            $result = AssignmentGenerator::generate($saab);
            $this->assertValidAssignments($result, $saab);
            $this->assertNotEmpty($result, "Saab stuck at {$saab->location}");

            $next = $result[mt_rand(0, count($result) - 1)]['destination'];
            $visited[$next] = true;
            $saab = $this->moveAircraft($saab, $next);
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
