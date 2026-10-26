<?php

namespace tests\unit\helpers;

use app\helpers\AssignmentGenerator;
use app\models\Aircraft;
use app\models\Flight;
use tests\unit\BaseUnitTest;

/**
 * One rule per test on a synthetic network: every airport lies on the same meridian, so the distance
 * from the origin XORG (latitude 0) is exactly the NM given when creating it (negative = south).
 *
 * Default aircraft: 1500 NM range whose configuration has an average flown distance of 1000 NM, so its distance
 * window is [500, 1200] NM, and whose flights took off from 2000 m runways, so it needs a runway of 2000 m or more.
 * shortFieldAircraft() (same range, flights from 600 m runways) needs 600 m; largeAircraft() (2500 NM) needs 3000 m.
 * General aviation (below 1000 NM and fewer than 20 seats) never gets assignments.
 * The origin XORG has a 3000 m runway, so all of them can start there. Every other airport gets a 2000 m runway
 * unless the test says otherwise.
 * Naming: XDnn = client airports, XFnn = plain airports (fillers).
 */
class AssignmentGeneratorTest extends BaseUnitTest
{
    use AssignmentGeneratorTestTrait;

    // Nautical miles per degree of latitude, matching GeoUtils::haversine
    private const NM_PER_DEG = 6371.0 * M_PI / 180 * 0.539957;

    private const AVERAGE = 1000;     // window [500, 1200] unless the range floor or cap applies
    private const MEDIUM_RANGE = 1500;
    private const LARGE_RANGE = 2500;
    private const SHORT_FIELD_MIN_RUNWAY = 600;
    private const LARGE_MIN_RUNWAY = 3000;

    private Aircraft $aircraft;

    protected function _before()
    {
        parent::_before();
        $this->clearDatabase();
        $this->createCountry();
        $this->createAirport('XORG', 0, [3000]);
        $this->aircraft = $this->createAircraft(self::MEDIUM_RANGE, 'XORG', self::AVERAGE);
    }

    private function createAirport(string $icao, float $distanceFromOriginNm, array $runwayLengths = [2000], int $countryId = 1, ?string $name = null): void
    {
        $this->createAirportAt($icao, $distanceFromOriginNm / self::NM_PER_DEG, 0.0, $runwayLengths, $countryId, $name);
    }

    private function createNamedAirport(string $icao, float $distanceFromOriginNm, string $name): void
    {
        $this->createAirport($icao, $distanceFromOriginNm, [2000], self::$SPAIN, $name);
    }

    /** @param array<string, float> $airports icao => NM from origin, all with a 2000 m runway */
    private function createForeignAirports(array $airports, int $countryId): void
    {
        foreach ($airports as $icao => $nm) {
            $this->createAirport($icao, $nm, [2000], $countryId);
        }
    }

    private function distinctCount(array $assignments): int
    {
        return count(array_unique(array_column($assignments, 'destination')));
    }

    /** @param array<string, float> $airports icao => NM from origin, all with a 2000 m runway */
    private function createAirports(array $airports): void
    {
        foreach ($airports as $icao => $nm) {
            $this->createAirport($icao, $nm);
        }
    }

    private function shortFieldAircraft(): Aircraft
    {
        return $this->createAircraft(self::MEDIUM_RANGE, 'XORG', self::AVERAGE, self::SHORT_FIELD_MIN_RUNWAY);
    }

    private function largeAircraft(): Aircraft
    {
        return $this->createAircraft(self::LARGE_RANGE, 'XORG', self::AVERAGE, self::LARGE_MIN_RUNWAY);
    }

    private function generate(?callable $rand = null, ?Aircraft $aircraft = null): array
    {
        $aircraft = $aircraft ?? $this->aircraft;
        $result = AssignmentGenerator::generate($aircraft, $rand);
        $this->assertValidAssignments($result, $aircraft);
        return $result;
    }

    /**
     * Runs the generator with both deterministic stubs and many seeded random runs.
     * @return array<int, array> Every result obtained
     */
    private function generateAllWays(?Aircraft $aircraft = null): array
    {
        $aircraft = $aircraft ?? $this->aircraft;
        $results = [$this->generate($this->alwaysAccept(), $aircraft), $this->generate($this->alwaysReject(), $aircraft)];
        foreach ($this->generateManyTimes($aircraft) as $result) {
            $this->assertValidAssignments($result, $aircraft);
            $results[] = $result;
        }
        return $results;
    }

    // =====================================================================
    // Direct mode: shapes of clients related to the origin
    // =====================================================================

    public function testClientWithManyHubsGetsOnlyItsOwnAirports()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XD04' => 900, 'XD05' => 1000, 'XF01' => 650, 'XF02' => 750]);
        $client = $this->createClient('Big Network', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04', 'XD05']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(AssignmentGenerator::CLIENT_SLOTS, $result);
            foreach ($result as $assignment) {
                $this->assertSame($client->id, $assignment['client_id']);
                $this->assertContains($assignment['destination'], ['XD01', 'XD02', 'XD03', 'XD04', 'XD05'], 'Filler used although slots were full');
            }
        }
    }

    public function testClientWithManyHubsEventuallyOffersAllOfThem()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XD04' => 900, 'XD05' => 1000]);
        $this->createClient('Big Network', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04', 'XD05']);

        $union = $this->unionOfDestinations($this->generateManyTimes($this->aircraft));

        $this->assertSame(['XD01', 'XD02', 'XD03', 'XD04', 'XD05'], $union);
    }

    public function testClientWithExactlyThreeHubsGetsAllOfThem()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XF01' => 650]);
        $this->createClient('Three Hubs', 'XORG', ['XD01', 'XD02', 'XD03']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XD02', 'XD03'], $this->destinationsOf($result));
        }
    }

    public function testHeadquartersOnlyClientGetsThreeInventedAssignments()
    {
        $this->createAirports(['XF01' => 600, 'XF02' => 700, 'XF03' => 800, 'XF04' => 900]);
        $client = $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(3, $result);
            $this->assertSame([$client->id => 3], $this->countByClient($result));
            $destinations = $this->destinationsOf($result);
            $this->assertSame($destinations, array_values(array_unique($destinations)));
            $this->assertEmpty(array_diff($destinations, ['XF01', 'XF02', 'XF03', 'XF04']));
        }
    }

    public function testHeadquartersOnlyClientDeterministicFillers()
    {
        $this->createAirports(['XF01' => 600, 'XF02' => 700, 'XF03' => 800, 'XF04' => 900]);
        $this->createClient('HQ Only', 'XORG');

        // Without swaps the first eligible airports (sorted by ICAO) are used
        $this->assertSame(['XF01', 'XF02', 'XF03'], $this->destinationsOf($this->generate($this->alwaysAccept())));
    }

    public function testHeadquartersOnlyClientFillersVaryBetweenRuns()
    {
        $this->createAirports(['XF01' => 600, 'XF02' => 700, 'XF03' => 800, 'XF04' => 900, 'XF05' => 1000, 'XF06' => 1100]);
        $this->createClient('HQ Only', 'XORG');

        $union = $this->unionOfDestinations($this->generateManyTimes($this->aircraft));

        $this->assertSame(['XF01', 'XF02', 'XF03', 'XF04', 'XF05', 'XF06'], $union);
    }

    public function testOneHubClientGetsItsHubPlusTwoInventedAssignments()
    {
        $this->createAirports(['XD01' => 600, 'XF01' => 650, 'XF02' => 750, 'XF03' => 850, 'XF04' => 950]);
        $client = $this->createClient('One Hub', 'XORG', ['XD01']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(3, $result);
            $this->assertSame([$client->id => 3], $this->countByClient($result));
            $this->assertContains('XD01', array_column($result, 'destination'), 'Own hub must always be offered');
            $fillers = array_diff(array_column($result, 'destination'), ['XD01']);
            $this->assertCount(2, $fillers);
            $this->assertEmpty(array_diff($fillers, ['XF01', 'XF02', 'XF03', 'XF04']));
        }
    }

    public function testTwoHubClientGetsBothHubsPlusOneInventedAssignment()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XF01' => 650, 'XF02' => 750]);
        $this->createClient('Two Hubs', 'XORG', ['XD01', 'XD02']);

        foreach ($this->generateAllWays() as $result) {
            $destinations = array_column($result, 'destination');
            $this->assertCount(3, $result);
            $this->assertContains('XD01', $destinations);
            $this->assertContains('XD02', $destinations);
            $this->assertCount(1, array_intersect($destinations, ['XF01', 'XF02']));
        }
    }

    public function testHubsOutsideTheDistanceWindowAreReplacedByFillers()
    {
        // One hub too close (400 < 500) and one too far (1250 > 1200)
        $this->createAirports(['XD01' => 400, 'XD02' => 1250, 'XF01' => 600, 'XF02' => 700, 'XF03' => 800]);
        $this->createClient('Odd Hubs', 'XORG', ['XD01', 'XD02']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02', 'XF03'], $this->destinationsOf($result));
        }
    }

    public function testHubsWithoutRunwaysAreReplacedByFillers()
    {
        $this->createAirport('XD01', 600, []);
        $this->createAirport('XD02', 700, []);
        $this->createAirports(['XF01' => 650, 'XF02' => 750, 'XF03' => 850]);
        $this->createClient('Heliports', 'XORG', ['XD01', 'XD02']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02', 'XF03'], $this->destinationsOf($result));
        }
    }

    public function testShortFieldAircraftCanAlsoUseBigAirports()
    {
        $this->createAirport('XD01', 550, [3500]);
        $this->createAirport('XD02', 600, [4000]);
        $this->createAirport('XD03', 650, [3200]);
        $this->createAirports(['XF01' => 520, 'XF02' => 700, 'XF03' => 750]);
        $this->createClient('Major Carrier', 'XORG', ['XD01', 'XD02', 'XD03']);

        foreach ($this->generateAllWays($this->shortFieldAircraft()) as $result) {
            $this->assertSame(['XD01', 'XD02', 'XD03'], $this->destinationsOf($result));
        }
    }

    public function testMediumAircraftGetsBigHubs()
    {
        $this->createAirport('XD01', 550, [3500]);
        $this->createAirport('XD02', 600, [4000]);
        $this->createAirport('XD03', 650, [3200]);
        $this->createAirports(['XF01' => 520, 'XF02' => 700, 'XF03' => 750]);
        $this->createClient('Major Carrier', 'XORG', ['XD01', 'XD02', 'XD03']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XD02', 'XD03'], $this->destinationsOf($result));
        }
    }

    public function testLargeAircraftNeverGetsShortRunwayHubsButGetsBigFillers()
    {
        $this->createAirport('XD01', 600, [2000]);
        $this->createAirport('XD02', 700, [2999]);
        $this->createAirport('XF01', 650, [3000]);
        $this->createAirport('XF02', 750, [3500]);
        $this->createAirport('XF03', 850, [1500]);
        $this->createClient('Regional', 'XORG', ['XD01', 'XD02']);

        foreach ($this->generateAllWays($this->largeAircraft()) as $result) {
            $this->assertSame(['XF01', 'XF02'], $this->destinationsOf($result));
        }
    }

    public function testOriginAsHubOffersHeadquartersAndOtherHubs()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XF01' => 650]);
        $client = $this->createClient('Hub Client', 'XD01', ['XORG', 'XD02', 'XD03']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XD02', 'XD03'], $this->destinationsOf($result));
            $this->assertSame([$client->id => 3], $this->countByClient($result));
        }
    }

    public function testOriginAsOnlyHubUsesHeadquartersAndFillers()
    {
        $this->createAirports(['XD01' => 600, 'XF01' => 650, 'XF02' => 750]);
        $this->createClient('Single Hub', 'XD01', ['XORG']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XF01', 'XF02'], $this->destinationsOf($result));
        }
    }

    public function testOriginRelatedToTwoClientsPoolsBothAndPicksFive()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XD04' => 900, 'XD05' => 1000, 'XD06' => 1100]);
        $a = $this->createClient('Client A', 'XORG', ['XD01', 'XD02', 'XD03']);
        $b = $this->createClient('Client B', 'XD04', ['XORG', 'XD05', 'XD06']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(AssignmentGenerator::COUNT, $result);
            foreach ($result as $assignment) {
                $expected = in_array($assignment['destination'], ['XD01', 'XD02', 'XD03']) ? $a->id : $b->id;
                $this->assertSame($expected, $assignment['client_id']);
            }
        }

        // Without swaps: A's three airports first, then B's
        $this->assertSame(
            ["XD01#{$a->id}", "XD02#{$a->id}", "XD03#{$a->id}", "XD04#{$b->id}", "XD05#{$b->id}"],
            $this->pairsOf($this->generate($this->alwaysAccept()))
        );
    }

    public function testSharedHubProducesOneAssignmentPerClient()
    {
        $this->createAirports(['XD01' => 600]);
        $a = $this->createClient('Client A', 'XORG', ['XD01']);
        $b = $this->createClient('Client B', 'XORG', ['XD01']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$a->id}", "XD01#{$b->id}"], $this->pairsOf($result));
        }
    }

    public function testSharedHubWithFillersNeverDuplicatesAPair()
    {
        $this->createAirports(['XD01' => 600, 'XF01' => 650, 'XF02' => 750, 'XF03' => 850]);
        $this->createClient('Client A', 'XORG', ['XD01']);
        $this->createClient('Client B', 'XORG', ['XD01']);

        foreach ($this->generateAllWays() as $result) {
            // 6 candidate pairs (3 per client), 5 picked; uniqueness is checked by assertValidAssignments
            $this->assertCount(AssignmentGenerator::COUNT, $result);
        }
    }

    public function testFillersNeverRepeatOwnAirportsOrEachOther()
    {
        $this->createAirports(['XD01' => 600, 'XF01' => 650, 'XF02' => 750]);
        $this->createClient('One Hub', 'XORG', ['XD01']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XF01', 'XF02'], $this->destinationsOf($result));
        }
    }

    public function testFillerMayBeAnotherClientsAirportButKeepsTheGapClient()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700]);
        $gap = $this->createClient('HQ Only', 'XORG');
        $this->createClient('Elsewhere', 'XD01', ['XD02']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$gap->id}", "XD02#{$gap->id}"], $this->pairsOf($result));
        }
    }

    public function testFillerPoolSmallerThanGapsOffersWhatExists()
    {
        $this->createAirports(['XF01' => 600, 'XF02' => 700]);
        $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02'], $this->destinationsOf($result));
        }
    }

    public function testNoEligibleAirportAtAllReturnsEmpty()
    {
        $this->createAirport('XF01', 1300);     // too far
        $this->createAirport('XF02', 300);      // too close
        $this->createAirport('XF03', 600, []);  // no runways
        $this->createAirport('XF04', 700, [1500]); // runway too short for a medium aircraft
        $this->createClient('Isolated', 'XORG', ['XF01', 'XF02', 'XF03', 'XF04']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame([], $result);
        }
    }

    public function testUnrelatedClientsNeverGetAssignmentsInDirectMode()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XD04' => 900, 'XD05' => 1000]);
        $related = $this->createClient('Related', 'XORG', ['XD01', 'XD02', 'XD03']);
        $this->createClient('Unrelated', 'XD04', ['XD05']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame([$related->id => 3], $this->countByClient($result));
        }
    }

    public function testManyRelatedClientsAreCappedAtFive()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XF01' => 800, 'XF02' => 900, 'XF03' => 1000, 'XF04' => 1100]);
        $ids = [
            $this->createClient('HQ Only A', 'XORG')->id,
            $this->createClient('HQ Only B', 'XORG')->id,
            $this->createClient('One Hub', 'XORG', ['XD01'])->id,
            $this->createClient('Hub Here', 'XD02', ['XORG'])->id,
        ];

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(AssignmentGenerator::COUNT, $result);
            $this->assertEmpty(array_diff(array_keys($this->countByClient($result)), $ids));
        }
    }

    public function testMixedClientsAllContributeOverManyRuns()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XF01' => 800, 'XF02' => 900]);
        $hqOnly = $this->createClient('HQ Only', 'XORG');
        $oneHub = $this->createClient('One Hub', 'XORG', ['XD01']);
        $hubHere = $this->createClient('Hub Here', 'XD02', ['XORG']);

        $seen = [];
        foreach ($this->generateManyTimes($this->aircraft, 60) as $result) {
            $this->assertValidAssignments($result, $this->aircraft);
            foreach ($result as $assignment) {
                $seen[$assignment['client_id']] = true;
            }
        }

        $this->assertArrayHasKey($hqOnly->id, $seen);
        $this->assertArrayHasKey($oneHub->id, $seen);
        $this->assertArrayHasKey($hubHere->id, $seen);
    }

    // =====================================================================
    // Variety: distinct destinations first
    // =====================================================================

    public function testTwoHeadquartersOnlyClientsGetDifferentDestinations()
    {
        $this->createAirports(['XF01' => 600, 'XF02' => 700, 'XF03' => 800, 'XF04' => 900, 'XF05' => 1000, 'XF06' => 1100]);
        $this->createClient('HQ Only A', 'XORG');
        $this->createClient('HQ Only B', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(AssignmentGenerator::COUNT, $result);
            $this->assertSame(AssignmentGenerator::COUNT, $this->distinctCount($result));
        }
    }

    public function testFillersAvoidAirportsOfferedByOtherClients()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XF01' => 650, 'XF02' => 750, 'XF03' => 850]);
        $this->createClient('Network', 'XORG', ['XD01', 'XD02', 'XD03']);
        $hqOnly = $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(AssignmentGenerator::COUNT, $this->distinctCount($result));
            foreach ($result as $assignment) {
                if ($assignment['client_id'] === $hqOnly->id) {
                    $this->assertContains($assignment['destination'], ['XF01', 'XF02', 'XF03']);
                }
            }
        }
    }

    public function testRepeatedDestinationsOnlyFillTheOffer()
    {
        // Two HQ-only clients and only three eligible airports: 6 pairs, 3 distinct destinations
        $this->createAirports(['XF01' => 600, 'XF02' => 700, 'XF03' => 800]);
        $this->createClient('HQ Only A', 'XORG');
        $this->createClient('HQ Only B', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(AssignmentGenerator::COUNT, $result);
            $this->assertSame(3, $this->distinctCount($result));
            // Distinct destinations come first
            $this->assertSame(3, count(array_unique(array_column(array_slice($result, 0, 3), 'destination'))));
        }
    }

    public function testReverseModePrefersDistinctDestinations()
    {
        // Three clients share XD01; a fourth one has four other airports
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XD04' => 900, 'XD05' => 1000]);
        $this->createClient('Shared A', 'XD01');
        $this->createClient('Shared B', 'XD01');
        $this->createClient('Shared C', 'XD01');
        $this->createClient('Network', 'XD02', ['XD03', 'XD04', 'XD05']);

        foreach ($this->generateAllWays() as $result) {
            // Pool: XD01 x3 + 3 of the network's airports -> 4 distinct, XD01 repeated once to reach 5
            $this->assertCount(AssignmentGenerator::COUNT, $result);
            $this->assertSame(4, $this->distinctCount($result));
            $this->assertSame(2, array_count_values(array_column($result, 'destination'))['XD01']);
        }
    }

    public function testADestinationIsNeverOfferedMoreThanTwice()
    {
        // Three HQ-only clients and a single eligible airport: 3 candidate pairs, only 2 offered
        $this->createAirports(['XF01' => 600]);
        $this->createClient('HQ Only A', 'XORG');
        $this->createClient('HQ Only B', 'XORG');
        $this->createClient('HQ Only C', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(AssignmentGenerator::MAX_PER_DESTINATION, $result);
            $this->assertSame(['XF01', 'XF01'], $this->destinationsOf($result));
        }
    }

    public function testReverseModeNeverOffersADestinationMoreThanTwice()
    {
        $this->createAirports(['XD01' => 600]);
        $this->createClient('Shared A', 'XD01');
        $this->createClient('Shared B', 'XD01');
        $this->createClient('Shared C', 'XD01');
        $this->createClient('Shared D', 'XD01');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XD01'], $this->destinationsOf($result));
        }
    }

    public function testRepetitionCapCanLeaveTheOfferShort()
    {
        // 2 distinct destinations, each shared by 3 clients: at most 2 + 2 = 4 assignments
        $this->createAirports(['XD01' => 600, 'XD02' => 700]);
        foreach (['A', 'B', 'C'] as $name) {
            $this->createClient("Shared $name", 'XD01', ['XD02']);
        }

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XD01', 'XD02', 'XD02'], $this->destinationsOf($result));
        }
    }

    public function testOwnAirportsPreferThoseNotOfferedByEarlierClients()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XD04' => 900]);
        $this->createClient('First', 'XORG', ['XD01', 'XD02', 'XD03']);
        $second = $this->createClient('Second', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04']);

        foreach ($this->generateAllWays() as $result) {
            $secondDestinations = array_column(array_filter($result, fn($a) => $a['client_id'] === $second->id), 'destination');
            if (!empty($secondDestinations)) {
                // XD04 is the only airport the first client doesn't offer, so it's always among the second's three
                $this->assertContains('XD04', $secondDestinations);
            }
            $this->assertSame(4, $this->distinctCount($result));
        }
    }

    public function testReverseModeOwnAirportsPreferThoseNotOfferedByEarlierClients()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XD04' => 900]);
        $this->createClient('First', 'XD01', ['XD02', 'XD03']);
        $this->createClient('Second', 'XD04', ['XD01', 'XD02', 'XD03']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(AssignmentGenerator::COUNT, $result);
            $this->assertContains('XD04', array_column($result, 'destination'));
            $this->assertSame(4, $this->distinctCount($result));
        }
    }

    // =====================================================================
    // Country preference for invented destinations
    // =====================================================================

    public function testSingleCountryClientInventsDestinationsInItsCountry()
    {
        $this->createAirports(['XF01' => 600, 'XF02' => 700, 'XF03' => 800]);
        $this->createForeignAirports(['XP01' => 650, 'XP02' => 750, 'XP03' => 850], self::$PORTUGAL);
        $this->createClient('Spanish HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02', 'XF03'], $this->destinationsOf($result));
        }
    }

    public function testSingleCountryClientGoesAbroadOnlyForMissingSlots()
    {
        $this->createAirports(['XF01' => 600]);
        $this->createForeignAirports(['XP01' => 650, 'XP02' => 750, 'XP03' => 850], self::$PORTUGAL);
        $this->createClient('Spanish HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $destinations = $this->destinationsOf($result);
            $this->assertCount(3, $result);
            $this->assertContains('XF01', $destinations, 'Home country airport must be used first');
            $this->assertCount(2, array_intersect($destinations, ['XP01', 'XP02', 'XP03']));
        }
    }

    public function testSingleCountryClientWithNothingAtHomeGoesAbroad()
    {
        $this->createForeignAirports(['XP01' => 650, 'XP02' => 750], self::$PORTUGAL);
        $this->createClient('Spanish HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XP01', 'XP02'], $this->destinationsOf($result));
        }
    }

    public function testOwnAirportsAbroadAreNotAffectedByTheCountryPreference()
    {
        $this->createForeignAirports(['XP01' => 650], self::$PORTUGAL);
        $this->createAirports(['XF01' => 600, 'XF02' => 700, 'XF03' => 800]);
        $client = $this->createClient('Iberian', 'XORG', ['XP01']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertContains('XP01', array_column($result, 'destination'));
            $this->assertSame([$client->id => 3], $this->countByClient($result));
        }
    }

    public function testMultiCountryClientInventsDestinationsInItsCountries()
    {
        // Hub in Portugal (out of the window, but it still makes the client Spanish-Portuguese)
        $this->createForeignAirports(['XP09' => 1300], self::$PORTUGAL);
        $this->createAirports(['XF01' => 600]);
        $this->createForeignAirports(['XP01' => 700, 'XP02' => 800], self::$PORTUGAL);
        $this->createForeignAirports(['XR01' => 650, 'XR02' => 750, 'XR03' => 850], self::$FRANCE);
        $this->createClient('Iberian', 'XORG', ['XP09']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XP01', 'XP02'], $this->destinationsOf($result));
        }
    }

    public function testMultiCountryClientGoesToOtherCountriesOnlyForMissingSlots()
    {
        $this->createForeignAirports(['XP09' => 1300], self::$PORTUGAL);
        $this->createAirports(['XF01' => 600]);
        $this->createForeignAirports(['XR01' => 650, 'XR02' => 750], self::$FRANCE);
        $this->createClient('Iberian', 'XORG', ['XP09']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XR01', 'XR02'], $this->destinationsOf($result));
        }
    }

    public function testEachClientUsesItsOwnCountries()
    {
        $this->createAirports(['XF01' => 600, 'XF02' => 700, 'XF03' => 800]);
        $this->createForeignAirports(['XP09' => 1300], self::$FRANCE);
        $this->createForeignAirports(['XR01' => 650, 'XR02' => 750, 'XR03' => 850], self::$FRANCE);
        $spanish = $this->createClient('Spanish', 'XORG');
        $french = $this->createClient('French', 'XP09', ['XORG']);

        foreach ($this->generateAllWays() as $result) {
            foreach ($result as $assignment) {
                if ($assignment['client_id'] === $spanish->id) {
                    $this->assertContains($assignment['destination'], ['XF01', 'XF02', 'XF03']);
                }
            }
        }
        // The Spanish-French client can be sent to both countries
        $union = [];
        foreach ($this->generateManyTimes($this->aircraft, 60) as $result) {
            foreach ($result as $assignment) {
                if ($assignment['client_id'] === $french->id) {
                    $union[$assignment['destination']] = true;
                }
            }
        }
        $this->assertNotEmpty(array_intersect(array_keys($union), ['XR01', 'XR02', 'XR03']));
    }

    public function testCountryPreferenceDoesNotApplyToReverseMode()
    {
        $this->createAirports(['XD01' => 600]);
        $this->createForeignAirports(['XP01' => 700], self::$PORTUGAL);
        $spanish = $this->createClient('Spanish', 'XD01');
        $portuguese = $this->createClient('Portuguese', 'XP01');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$spanish->id}", "XP01#{$portuguese->id}"], $this->pairsOf($result));
        }
    }

    public function testCountryPreferenceNeverBlocksTheWayBack()
    {
        // The only airport around is abroad: it is offered, and from there the way back is offered too
        $this->createForeignAirports(['XP01' => 700], self::$PORTUGAL);
        $client = $this->createClient('Spanish HQ Only', 'XORG');

        $this->assertSame(["XP01#{$client->id}"], $this->pairsOf($this->generate($this->alwaysReject())));

        $aircraft = $this->moveAircraft($this->aircraft, 'XP01');
        $this->assertSame(["XORG#{$client->id}"], $this->pairsOf($this->generate($this->alwaysReject(), $aircraft)));
    }

    // =====================================================================
    // Reverse mode: no client at the origin
    // =====================================================================

    public function testReverseModeOffersOnlyClientAirports()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XF01' => 650, 'XF02' => 750]);
        $a = $this->createClient('Client A', 'XD01', ['XD02']);
        $b = $this->createClient('Client B', 'XD03');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$a->id}", "XD02#{$a->id}", "XD03#{$b->id}"], $this->pairsOf($result));
        }
    }

    public function testReverseModeCapsEachClientAtThree()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XD04' => 900, 'XD05' => 1000]);
        $client = $this->createClient('Big Network', 'XD01', ['XD02', 'XD03', 'XD04', 'XD05']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame([$client->id => 3], $this->countByClient($result));
        }
    }

    public function testReverseModeWithSeveralClientsPicksFive()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XD04' => 900, 'XD05' => 1000, 'XD06' => 1100]);
        $this->createClient('Client A', 'XD01', ['XD02', 'XD03']);
        $this->createClient('Client B', 'XD04', ['XD05', 'XD06']);
        $this->createClient('Client C', 'XD02');

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(AssignmentGenerator::COUNT, $result);
        }
    }

    public function testReverseModeExcludesIneligibleClientAirports()
    {
        $this->createAirport('XD01', 1250);        // too far
        $this->createAirport('XD02', 600, []);     // no runways
        $this->createAirport('XD03', 300);         // too close
        $this->createAirport('XD04', 800, [1500]); // runway too short for a medium aircraft
        $this->createAirports(['XD05' => 700]);
        $client = $this->createClient('Mixed', 'XD01', ['XD02', 'XD03', 'XD04', 'XD05']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD05#{$client->id}"], $this->pairsOf($result));
        }
    }

    public function testReverseModeRunwayRulesPerAircraft()
    {
        $this->createAirport('XD01', 600, [3500]);
        $this->createAirport('XD02', 700, [2000]);
        $this->createAirport('XD03', 800, [1200]);
        $client = $this->createClient('Mixed', 'XD01', ['XD02', 'XD03']);

        foreach ($this->generateAllWays($this->shortFieldAircraft()) as $result) {
            $this->assertSame(["XD01#{$client->id}", "XD02#{$client->id}", "XD03#{$client->id}"], $this->pairsOf($result));
        }
        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$client->id}", "XD02#{$client->id}"], $this->pairsOf($result));
        }
        foreach ($this->generateAllWays($this->largeAircraft()) as $result) {
            $this->assertSame(["XD01#{$client->id}"], $this->pairsOf($result));
        }
    }

    public function testReverseModeSharedHubGivesOnePairPerClient()
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800]);
        $a = $this->createClient('Client A', 'XD01', ['XD02']);
        $b = $this->createClient('Client B', 'XD03', ['XD02']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(
                ["XD01#{$a->id}", "XD02#{$a->id}", "XD02#{$b->id}", "XD03#{$b->id}"],
                $this->pairsOf($result)
            );
        }
    }

    public function testReverseModeNeverInventsFillers()
    {
        $this->createAirports(['XD01' => 600, 'XF01' => 650, 'XF02' => 750, 'XF03' => 850, 'XF04' => 950]);
        $client = $this->createClient('Lonely', 'XD01');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$client->id}"], $this->pairsOf($result));
        }
    }

    public function testNoClientsAtAllReturnsEmpty()
    {
        $this->createAirports(['XF01' => 600, 'XF02' => 700]);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame([], $result);
        }
    }

    public function testClientsOutsideTheWindowReturnsEmpty()
    {
        $this->createAirports(['XF01' => 600, 'XD01' => 1250, 'XD02' => 300]);
        $this->createClient('Out Of Window', 'XD01', ['XD02']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame([], $result);
        }
    }

    // =====================================================================
    // Runways: minimum learnt from takeoffs
    // =====================================================================

    public function testShortFieldAircraftUsesStripsAndBigAirportsAlike()
    {
        $this->createAirport('XD01', 550, [3000]);
        $this->createAirport('XD02', 600, [3000.1]);
        $this->createAirport('XD03', 650, [3200]);
        $this->createAirport('XD04', 700, [2000, 3200]);
        $this->createAirport('XD05', 750, [800]);  // strip: still above its 600 m
        $this->createAirport('XD06', 800, [500]);  // too short
        $this->createClient('Runway Mix', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04', 'XD05', 'XD06']);

        $results = $this->generateAllWays($this->shortFieldAircraft());
        foreach ($results as $result) {
            $this->assertCount(3, $result);
        }
        $this->assertSame(['XD01', 'XD02', 'XD03', 'XD04', 'XD05'], $this->unionOfDestinations($results));
    }

    public function testMinimumRunwayBoundaries()
    {
        $this->createAirport('XD01', 600, [1999]);       // just below: excluded
        $this->createAirport('XD02', 650, [2000]);       // exactly the minimum: allowed
        $this->createAirport('XD03', 700, [800]);        // strip: excluded
        $this->createAirport('XD04', 750, [800, 2000]);  // one long enough runway is enough
        $this->createAirport('XD05', 800, [4000]);       // big airport: allowed
        $this->createClient('Runway Mix', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04', 'XD05']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD02', 'XD04', 'XD05'], $this->destinationsOf($result));
        }
    }

    public function testLongerMinimumRunwayBoundaries()
    {
        $this->createAirport('XD01', 600, [2999]);       // just below: excluded
        $this->createAirport('XD02', 650, [3000]);       // exactly the minimum: allowed
        $this->createAirport('XD03', 700, [2000]);       // excluded
        $this->createAirport('XD04', 750, [2000, 3000]); // one long enough runway is enough
        $this->createAirport('XD05', 800, [4000]);       // allowed
        $this->createClient('Runway Mix', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04', 'XD05']);

        foreach ($this->generateAllWays($this->largeAircraft()) as $result) {
            $this->assertSame(['XD02', 'XD04', 'XD05'], $this->destinationsOf($result));
        }
    }

    // =====================================================================
    // General aviation: no assignments
    // =====================================================================

    public function testGeneralAviationGetsNoAssignments()
    {
        $this->createAirport('XD01', 600, [3500]);
        $this->createAirport('XD02', 700, [2500]);
        $this->createClient('Runways', 'XORG', ['XD01', 'XD02']);

        $expected = [
            // [range NM, seats, gets assignments?]
            [999, 4, false],
            [999, 19, false],
            [915, 0, false],   // light cargo
            [999, 20, true],   // enough seats to be a commuter
            [935, 34, true],   // Saab 340
            [1000, 4, true],   // enough range
        ];
        foreach ($expected as [$range, $pax, $getsAssignments]) {
            $aircraft = $this->createAircraft($range, 'XORG', self::AVERAGE, 1000, $pax);
            foreach ($this->generateAllWays($aircraft) as $result) {
                $this->assertSame($getsAssignments, !empty($result), "$range NM / $pax seats");
            }
        }
    }

    public function testOnlyTheSeatsTellACommuterFromGeneralAviation()
    {
        $this->createOrigin('XBIG', [3500]);
        // Same range and history, only the seats differ
        $commuter = $this->createAircraft(935, 'XBIG', self::AVERAGE, 1300, 34);
        $light = $this->createAircraft(935, 'XBIG', self::AVERAGE, 1300, 3);

        $this->assertSame(['XF01'], $this->destinationsOf($this->generate($this->alwaysAccept(), $commuter)));
        $this->assertSame([], AssignmentGenerator::generate($light, $this->alwaysAccept()));
    }

    public function testRangeNoLongerDecidesTheMinimumRunway()
    {
        // A long-range aircraft that has always taken off from 1200 m runways can use short runways
        $this->createAirport('XD01', 600, [1200]);
        $this->createAirport('XD02', 700, [1100]);
        $this->createClient('Short Fields', 'XORG', ['XD01', 'XD02']);
        $aircraft = $this->createAircraft(5000, 'XORG', self::AVERAGE, 1200);

        foreach ($this->generateAllWays($aircraft) as $result) {
            $this->assertSame(['XD01'], $this->destinationsOf($result));
        }
    }

    // =====================================================================
    // Minimum runway: second shortest takeoff runway of real flights
    // =====================================================================

    private function aircraftWithTakeoffs(array $takeoffRunwaysM): Aircraft
    {
        $aircraft = $this->createAircraftWithoutHistory(self::MEDIUM_RANGE, 'XORG');
        foreach ($takeoffRunwaysM as $length) {
            $this->recordFlight($aircraft, self::AVERAGE, Flight::STATUS_FINISHED, $length);
        }
        return $aircraft;
    }

    public function testMinimumRunwayIsTheSecondShortestTakeoffRunway()
    {
        $aircraft = $this->aircraftWithTakeoffs([2500, 537, 1500, 3000]);

        $this->assertSame(1500.0, AssignmentGenerator::minTakeoffRunway($aircraft->aircraft_configuration_id));
    }

    public function testTheShortestTakeoffIsIgnored()
    {
        // 537 m was a one-off: a 1200 m runway (above 537, below 1500) must not be offered
        $this->createAirport('XD01', 600, [1200]);
        $this->createAirport('XD02', 700, [1500]);
        $this->createAirport('XD03', 800, [2600]);
        $this->createClient('Runways', 'XORG', ['XD01', 'XD02', 'XD03']);
        $aircraft = $this->aircraftWithTakeoffs([537, 1500, 2500]);

        foreach ($this->generateAllWays($aircraft) as $result) {
            $this->assertSame(['XD02', 'XD03'], $this->destinationsOf($result));
        }
    }

    public function testRepeatedShortestTakeoffIsNoLongerAnOutlier()
    {
        $aircraft = $this->aircraftWithTakeoffs([900, 900, 2500]);

        $this->assertSame(900.0, AssignmentGenerator::minTakeoffRunway($aircraft->aircraft_configuration_id));
    }

    public function testFewerThanTwoKnownTakeoffsMeansNoAssignments()
    {
        $this->createAirports(['XD01' => 600]);
        $this->createClient('Client', 'XORG', ['XD01']);
        $aircraft = $this->aircraftWithTakeoffs([2000]);
        $this->recordFlight($aircraft, self::AVERAGE);
        $this->recordFlight($aircraft, self::AVERAGE);

        // Enough flights for the distance window, but only one known takeoff runway
        $this->assertNotNull(AssignmentGenerator::distanceWindow($aircraft));
        $this->assertNull(AssignmentGenerator::minTakeoffRunway($aircraft->aircraft_configuration_id));
        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));

        $this->recordFlight($aircraft, self::AVERAGE, Flight::STATUS_FINISHED, 2000);

        $this->assertSame(['XD01'], $this->destinationsOf($this->generate($this->alwaysAccept(), $aircraft)));
    }

    public function testTakeoffsOfAllAircraftOfTheConfigurationCount()
    {
        $aircraft = $this->aircraftWithTakeoffs([3000]);
        $this->recordFlight($this->createSiblingAircraft($aircraft, 'XORG'), self::AVERAGE, Flight::STATUS_FINISHED, 1800);

        $this->assertSame(3000.0, AssignmentGenerator::minTakeoffRunway($aircraft->aircraft_configuration_id));
    }

    public function testTakeoffsOfOtherConfigurationsDoNotCount()
    {
        $this->createAircraft(self::MEDIUM_RANGE, 'XORG', self::AVERAGE, 800);

        $this->assertSame(2000.0, AssignmentGenerator::minTakeoffRunway($this->aircraft->aircraft_configuration_id));
    }

    public function testTakeoffsOfUnfinishedFlightsDoNotCount()
    {
        $aircraft = $this->aircraftWithTakeoffs([2000, 2200]);
        foreach (['R', 'C', 'V'] as $status) {
            $this->recordFlight($aircraft, self::AVERAGE, $status, 600);
        }

        $this->assertSame(2200.0, AssignmentGenerator::minTakeoffRunway($aircraft->aircraft_configuration_id));
    }

    public function testUnknownTakeoffRunwaysAreIgnored()
    {
        $aircraft = $this->aircraftWithTakeoffs([2500]);
        // Runway end that doesn't exist at the departure airport
        $this->recordFlightFrom($aircraft, self::AVERAGE, Flight::STATUS_FINISHED, 'XORG', '99');
        $this->assertNull(AssignmentGenerator::minTakeoffRunway($aircraft->aircraft_configuration_id));

        // XORG's runway is 01/19 (3000 m): this one resolves
        $this->recordFlightFrom($aircraft, self::AVERAGE, Flight::STATUS_FINISHED, 'XORG', '19');
        $this->assertSame(3000.0, AssignmentGenerator::minTakeoffRunway($aircraft->aircraft_configuration_id));
    }

    public function testLandingRunwaysAreNotUsed()
    {
        // Departure airport with a 600 m runway (01/19) and a 2500 m one (02/20): take off on 02, land on 01
        $this->createAirportAt('XLND', -80.0, 90.0, [600, 2500]);
        $aircraft = $this->createAircraftWithoutHistory(self::MEDIUM_RANGE, 'XORG');
        for ($i = 0; $i < 3; $i++) {
            $this->recordFlightFrom($aircraft, self::AVERAGE, Flight::STATUS_FINISHED, 'XLND', '02', '01');
        }

        $this->assertSame(2500.0, AssignmentGenerator::minTakeoffRunway($aircraft->aircraft_configuration_id));
    }

    public function testMinimumRunwayAppliesToTheOrigin()
    {
        $this->createOrigin('XO25', [2400]);
        $aircraft = $this->createAircraft(self::MEDIUM_RANGE, 'XO25', self::AVERAGE, 2500);
        $this->createRunway('XF01', 2600, '18/36');

        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));
    }

    public function testKingAirLikeAircraftCanUseShortRunways()
    {
        // Second shortest takeoff 1484 m (real King Air data): 1500 m fields are fine, 1400 m ones are not
        $this->createAirport('XD01', 600, [1500]);
        $this->createAirport('XD02', 700, [1754]);
        $this->createAirport('XD03', 800, [1400]);
        $this->createClient('Fields', 'XORG', ['XD01', 'XD02', 'XD03']);
        $kingAir = $this->createAircraftWithoutHistory(1750, 'XORG');
        foreach ([1209, 1484, 2381] as $length) {
            $this->recordFlight($kingAir, self::AVERAGE, Flight::STATUS_FINISHED, $length);
        }

        foreach ($this->generateAllWays($kingAir) as $result) {
            $this->assertSame(['XD01', 'XD02'], $this->destinationsOf($result));
        }
    }

    public function testAirportWithoutRunwaysIsNeverAFiller()
    {
        $this->createAirport('XF01', 600, []);
        $this->createAirports(['XF02' => 700]);
        $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF02'], $this->destinationsOf($result));
        }
    }

    public function testFillersFollowTheRunwayRules()
    {
        $this->createAirport('XF01', 600, [3500]);
        $this->createAirport('XF02', 700, [2000]);
        $this->createAirport('XF03', 800, [1200]);
        $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays($this->shortFieldAircraft()) as $result) {
            $this->assertSame(['XF01', 'XF02', 'XF03'], $this->destinationsOf($result));
        }
        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02'], $this->destinationsOf($result));
        }
        foreach ($this->generateAllWays($this->largeAircraft()) as $result) {
            $this->assertSame(['XF01'], $this->destinationsOf($result));
        }
    }

    // =====================================================================
    // Origin: same airport rules as destinations
    // =====================================================================

    /**
     * Creates an extra origin next to XORG (same position) with an HQ-only client and one filler 600 NM away.
     */
    private function createOrigin(string $icao, array $runwayLengths, ?string $name = null): void
    {
        $this->createAirport($icao, 0, $runwayLengths, self::$SPAIN, $name);
        $this->createAirports(['XF01' => 600]);
        $this->createClient("HQ at $icao", $icao);
    }

    public function testAircraftAtAnAirportWithShorterRunwaysThanUsualGetsNothing()
    {
        $this->createOrigin('XSHO', [1999]);
        $medium = $this->createAircraft(self::MEDIUM_RANGE, 'XSHO', self::AVERAGE);
        $shortField = $this->createAircraft(self::MEDIUM_RANGE, 'XSHO', self::AVERAGE, self::SHORT_FIELD_MIN_RUNWAY);

        $this->assertSame([], AssignmentGenerator::generate($medium, $this->alwaysAccept()));
        $this->assertSame(['XF01'], $this->destinationsOf($this->generate($this->alwaysAccept(), $shortField)));
    }

    public function testLargeAircraftAtAnAirportWithoutA3000mRunwayGetsNothing()
    {
        $this->createOrigin('XMED', [2999]);
        $this->createRunway('XF01', 3500, '18/36');
        $large = $this->createAircraft(self::LARGE_RANGE, 'XMED', self::AVERAGE, self::LARGE_MIN_RUNWAY);
        $medium = $this->createAircraft(self::MEDIUM_RANGE, 'XMED', self::AVERAGE);

        $this->assertSame([], AssignmentGenerator::generate($large, $this->alwaysAccept()));
        $this->assertSame(['XF01'], $this->destinationsOf($this->generate($this->alwaysAccept(), $medium)));
    }

    public function testEveryAircraftCanStartFromALongEnoughRunway()
    {
        $this->createAirport('XF01', 600, [3000]);
        $this->createClient('HQ Only', 'XORG');

        foreach ([$this->shortFieldAircraft(), $this->aircraft, $this->largeAircraft()] as $aircraft) {
            $this->assertNotEmpty($this->generate($this->alwaysAccept(), $aircraft));
        }
    }

    public function testOriginWithoutRunwaysGetsNothing()
    {
        $this->createOrigin('XNOR', []);
        $aircraft = $this->createAircraft(self::MEDIUM_RANGE, 'XNOR', self::AVERAGE);

        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));
    }

    public function testOriginHeliportGetsNothingEvenWithARunway()
    {
        $this->createOrigin('XHEL', [2455.6], 'Mas Passamaner Heliport');
        $aircraft = $this->createAircraft(self::MEDIUM_RANGE, 'XHEL', self::AVERAGE);

        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));
    }

    public function testMilitaryOriginStillGetsAssignments()
    {
        $this->createOrigin('XMIL', [2500], 'Zaragoza Air Base');
        $aircraft = $this->createAircraft(self::MEDIUM_RANGE, 'XMIL', self::AVERAGE);

        $this->assertSame(['XF01'], $this->destinationsOf($this->generate($this->alwaysAccept(), $aircraft)));
    }

    public function testIsSuitableAirport()
    {
        $this->createAirport('XA01', 600, [3000]);
        $this->createAirport('XA02', 650, [1999]);
        $this->createAirport('XA03', 700, [2000, 3001]);
        $this->createAirport('XA04', 750, []);
        $this->createAirport('XA05', 800, [2000], self::$SPAIN, 'Some Heliport');

        $expected = [
            // icao => [minimum 600 m, 2000 m, 3001 m]
            'XA01' => [true, true, false],
            'XA02' => [true, false, false],
            'XA03' => [true, true, true],
            'XA04' => [false, false, false],
            'XA05' => [false, false, false],
        ];
        foreach ($expected as $icao => [$a, $b, $c]) {
            $this->assertSame($a, AssignmentGenerator::isSuitableAirport($icao, 600), "$icao / 600 m");
            $this->assertSame($b, AssignmentGenerator::isSuitableAirport($icao, 2000), "$icao / 2000 m");
            $this->assertSame($c, AssignmentGenerator::isSuitableAirport($icao, 3001), "$icao / 3001 m");
        }
        $this->assertFalse(AssignmentGenerator::isSuitableAirport('ZZZZ', 1000));
    }

    public function testIsLightAircraft()
    {
        $expected = [
            // [range NM, seats, light?]
            [450, 3, true],     // Maule
            [999, 19, true],
            [999, 20, false],   // enough seats to be a commuter
            [935, 34, false],   // Saab 340
            [1000, 4, false],   // enough range
            [915, 0, true],     // light cargo
        ];
        foreach ($expected as [$range, $pax, $light]) {
            $aircraft = $this->createAircraftWithoutHistory($range, 'XORG', $pax);
            $this->assertSame($light, AssignmentGenerator::isLightAircraft($aircraft->aircraftConfiguration), "$range NM / $pax seats");
        }
    }

    // =====================================================================
    // Heliports and military airports
    // =====================================================================

    public function testHeliportsAreNeverOfferedEvenWithARunway()
    {
        $this->createNamedAirport('XH01', 600, 'Mas Passamaner Heliport');
        $this->createNamedAirport('XH02', 700, 'Helipuerto de Pruebas');
        $this->createNamedAirport('XH03', 800, 'Heliporto do Teste');
        $this->createAirports(['XF01' => 650]);
        $this->createClient('Helicopter Lover', 'XORG', ['XH01']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01'], $this->destinationsOf($result));
        }
    }

    public function testHeliportsAreNeverOfferedInReverseMode()
    {
        $this->createNamedAirport('XH01', 600, 'City Heliport');
        $this->createAirports(['XD01' => 700]);
        $client = $this->createClient('Heli Client', 'XH01', ['XD01']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$client->id}"], $this->pairsOf($result));
        }
    }

    public function testMilitaryAirportsAreNotInventedWhenCivilOnesExist()
    {
        $this->createNamedAirport('XM01', 600, 'Rota Naval Station');
        $this->createNamedAirport('XM02', 650, 'RAF Fairford');
        $this->createNamedAirport('XM03', 700, 'Avord (BA 702) Air Base');
        $this->createNamedAirport('XM04', 750, 'Base Aérea de Morón');
        $this->createNamedAirport('XM05', 800, 'Ramstein Air Base');
        $this->createAirports(['XF01' => 900]);
        $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            // Only one civil airport: the other slots stay empty rather than going to a military base
            $this->assertSame(['XF01'], $this->destinationsOf($result));
        }
    }

    public function testMilitaryAirportsAreInventedAsLastResort()
    {
        $this->createNamedAirport('XM01', 600, 'Moron Air Base');
        $this->createNamedAirport('XM02', 700, 'Torrejon Air Base');
        $client = $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XM01#{$client->id}", "XM02#{$client->id}"], $this->pairsOf($result));
        }
    }

    public function testMilitaryAirportsAreNotInventedWhenTheClientHasOwnAirports()
    {
        $this->createNamedAirport('XM01', 600, 'Moron Air Base');
        $this->createAirports(['XD01' => 700]);
        $this->createClient('One Hub', 'XORG', ['XD01']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01'], $this->destinationsOf($result));
        }
    }

    public function testMilitaryClientAirportIsStillOfferedAsOwnAirport()
    {
        $this->createNamedAirport('XM01', 600, 'Zaragoza Air Base');
        $this->createAirports(['XF01' => 700, 'XF02' => 800]);
        $client = $this->createClient('Aragon', 'XORG', ['XM01']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02', 'XM01'], $this->destinationsOf($result));
            $this->assertSame([$client->id => 3], $this->countByClient($result));
        }
    }

    public function testMilitaryClientAirportIsOfferedInReverseMode()
    {
        $this->createNamedAirport('XM01', 600, 'Zaragoza Air Base');
        $client = $this->createClient('Aragon', 'XM01');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XM01#{$client->id}"], $this->pairsOf($result));
        }
    }

    public function testMilitaryLastResortPrefersTheClientsCountries()
    {
        $this->createNamedAirport('XM01', 600, 'Moron Air Base');
        foreach (['XN01' => 650, 'XN02' => 700, 'XN03' => 750] as $icao => $nm) {
            $this->createAirport($icao, $nm, [2000], self::$PORTUGAL, "$icao Air Base");
        }
        $this->createClient('Spanish HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(3, $result);
            $this->assertContains('XM01', array_column($result, 'destination'));
        }
    }

    public function testCivilNamesResemblingMilitaryOnesAreNotAvoided()
    {
        $this->createNamedAirport('XF01', 600, 'Rafael Hernández Airport');
        $this->createNamedAirport('XF02', 700, 'Navalmoral de la Mata Airfield');
        $this->createNamedAirport('XF03', 800, 'Plain Civil Airport');
        $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02', 'XF03'], $this->destinationsOf($result));
        }
    }

    public function testMilitaryLastResortKeepsTheWayBack()
    {
        $this->createNamedAirport('XM01', 600, 'Moron Air Base');
        $client = $this->createClient('HQ Only', 'XORG');

        $this->assertSame(["XM01#{$client->id}"], $this->pairsOf($this->generate($this->alwaysReject())));

        $aircraft = $this->moveAircraft($this->aircraft, 'XM01');
        $this->assertSame(["XORG#{$client->id}"], $this->pairsOf($this->generate($this->alwaysReject(), $aircraft)));
    }

    // =====================================================================
    // Distance window: 50%-120% of the average flown distance
    // =====================================================================

    public function testDistanceWindowBoundaries()
    {
        // Window [500, 1200]: 499 and 1201 out, 501 and 1199 in
        $this->createAirports(['XD01' => 499, 'XD02' => 501, 'XD03' => 1199, 'XD04' => 1201]);
        $this->createClient('Window Client', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD02', 'XD03'], $this->destinationsOf($result));
        }
    }

    public function testDistanceWindowAppliesToFillers()
    {
        $this->createAirports(['XF01' => 450, 'XF02' => 800, 'XF03' => 1250]);
        $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF02'], $this->destinationsOf($result));
        }
    }

    public function testWindowMaximumIsNeverBelowTwentyPercentOfTheRange()
    {
        // 5000 NM aircraft only flown on 300 NM legs: 120% of the average is 360, but the floor raises it to 1000
        $this->createAirports(['XD01' => 140, 'XD02' => 300, 'XD03' => 900, 'XD04' => 1100]);
        $this->createClient('Network', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04']);
        $underused = $this->createAircraft(5000, 'XORG', 300);

        $this->assertSame([150.0, 1000.0], AssignmentGenerator::distanceWindow($underused));
        foreach ($this->generateAllWays($underused) as $result) {
            $this->assertSame(['XD02', 'XD03'], $this->destinationsOf($result));
        }
    }

    public function testRangeFloorDoesNotApplyWhenTheAverageIsHigher()
    {
        // 1500 NM range: the floor (300 NM) is below 120% of the 1000 NM average
        $this->assertSame([500.0, 1200.0], AssignmentGenerator::distanceWindow($this->aircraft));
    }

    public function testRangeFloorAlsoDecidesWhichRoutesTriggerTheLottery()
    {
        $this->createAirports(['XD01' => 300, 'XF01' => 900]);
        $this->createClient('Client', 'XORG', ['XD01']);
        $this->createRoute('R001', 'XORG', 'XF01');
        $underused = $this->createAircraft(5000, 'XORG', 300); // window [150, 1000]

        // The 900 NM route is beyond 120% of the average but within the raised maximum, so it counts
        $this->assertTrue(AssignmentGenerator::hasFlyableRoutes('XORG', AssignmentGenerator::distanceWindow($underused)[1]));
        $this->assertSame([], $this->generate($this->alwaysReject(), $underused));
    }

    public function testDistanceWindowIsCappedAtEightyPercentOfRange()
    {
        // 1100 NM range: 1.2 * 1000 = 1200 is capped at 0.8 * 1100 = 880
        $this->createAirports(['XD01' => 600, 'XD02' => 870, 'XD03' => 890]);
        $this->createClient('Cap Client', 'XORG', ['XD01', 'XD02', 'XD03']);
        $aircraft = $this->createAircraft(1100, 'XORG', self::AVERAGE);

        $this->assertSame([500.0, 880.0], AssignmentGenerator::distanceWindow($aircraft));
        foreach ($this->generateAllWays($aircraft) as $result) {
            $this->assertSame(['XD01', 'XD02'], $this->destinationsOf($result));
        }
    }

    public function testShortAverageMeansShortAssignments()
    {
        $this->createAirports(['XD01' => 70, 'XD02' => 100, 'XD03' => 130, 'XD04' => 600]);
        $this->createClient('Local', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04']);
        // 700 NM and 34 seats: a commuter whose range floor (140 NM) stays below 120% of its average
        $shortHop = $this->createAircraft(700, 'XORG', 120, self::SHORT_FIELD_MIN_RUNWAY, 34); // window [60, 144]

        foreach ($this->generateAllWays($shortHop) as $result) {
            $this->assertSame(['XD01', 'XD02', 'XD03'], $this->destinationsOf($result));
        }
    }

    public function testDistanceWindowUsesTheAverageOfAllFinishedFlightsOfTheConfiguration()
    {
        $aircraft = $this->createAircraftWithoutHistory(self::MEDIUM_RANGE, 'XORG');
        $sibling = $this->createSiblingAircraft($aircraft, 'XORG');
        $this->recordFlight($aircraft, 700);
        $this->recordFlight($aircraft, 1100);
        $this->recordFlight($sibling, 1200);

        $this->assertSame(1000.0, AssignmentGenerator::averageFlownDistance($aircraft->aircraft_configuration_id));
        $this->assertSame([500.0, 1200.0], AssignmentGenerator::distanceWindow($aircraft));
    }

    public function testOnlyFinishedFlightsWithDistanceCount()
    {
        $aircraft = $this->createAircraft(self::MEDIUM_RANGE, 'XORG', 1000);
        $this->recordFlight($aircraft, 3000, 'R');
        $this->recordFlight($aircraft, 3000, 'C');
        $this->recordFlight($aircraft, 3000, 'V');
        $this->recordFlight($aircraft, null);

        $this->assertSame(1000.0, AssignmentGenerator::averageFlownDistance($aircraft->aircraft_configuration_id));
    }

    public function testFlightsOfOtherConfigurationsDoNotCount()
    {
        $other = $this->createAircraft(self::MEDIUM_RANGE, 'XORG', 3000);
        $this->recordFlight($other, 3000);

        $this->assertSame(1000.0, AssignmentGenerator::averageFlownDistance($this->aircraft->aircraft_configuration_id));
    }

    public function testNeverFlownConfigurationGetsNothing()
    {
        $this->createAirports(['XD01' => 600, 'XF01' => 700]);
        $this->createClient('Client', 'XORG', ['XD01']);
        $aircraft = $this->createAircraftWithoutHistory(self::MEDIUM_RANGE, 'XORG');

        $this->assertNull(AssignmentGenerator::averageFlownDistance($aircraft->aircraft_configuration_id));
        $this->assertNull(AssignmentGenerator::distanceWindow($aircraft));
        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));
    }

    public function testConfigurationWithOnlyUnfinishedFlightsGetsNothing()
    {
        $this->createAirports(['XD01' => 600]);
        $this->createClient('Client', 'XORG', ['XD01']);
        $aircraft = $this->createAircraftWithoutHistory(self::MEDIUM_RANGE, 'XORG');
        $this->recordFlight($aircraft, 600, 'R');
        $this->recordFlight($aircraft, null);

        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));
    }

    public function testThirdFinishedFlightUnlocksAssignments()
    {
        $this->createAirports(['XD01' => 600]);
        $client = $this->createClient('Client', 'XORG', ['XD01']);
        $aircraft = $this->createAircraftWithoutHistory(self::MEDIUM_RANGE, 'XORG');
        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));

        $this->recordFlight($aircraft, 700, Flight::STATUS_FINISHED, 2000);
        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));

        $this->recordFlight($aircraft, 700, Flight::STATUS_FINISHED, 2000);
        $this->assertNull(AssignmentGenerator::averageFlownDistance($aircraft->aircraft_configuration_id));
        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));

        $this->recordFlight($aircraft, 700, Flight::STATUS_FINISHED, 2000);
        $this->assertSame(700.0, AssignmentGenerator::averageFlownDistance($aircraft->aircraft_configuration_id));
        $this->assertSame(["XD01#{$client->id}"], $this->pairsOf($this->generate($this->alwaysAccept(), $aircraft)));
    }

    public function testFlightsOfAllAircraftOfTheConfigurationCountTowardsTheMinimum()
    {
        $aircraft = $this->createAircraftWithoutHistory(self::MEDIUM_RANGE, 'XORG');
        $this->recordFlight($aircraft, 1000);
        $this->recordFlight($this->createSiblingAircraft($aircraft, 'XORG'), 1000);
        $this->assertNull(AssignmentGenerator::distanceWindow($aircraft));

        $this->recordFlight($this->createSiblingAircraft($aircraft, 'XORG'), 1000);

        $this->assertSame([500.0, 1200.0], AssignmentGenerator::distanceWindow($aircraft));
    }

    public function testUnfinishedFlightsAndFlightsWithoutDistanceDoNotCountTowardsTheMinimum()
    {
        $aircraft = $this->createAircraftWithoutHistory(self::MEDIUM_RANGE, 'XORG');
        $this->recordFlight($aircraft, 1000);
        $this->recordFlight($aircraft, 1000);
        $this->recordFlight($aircraft, 1000, 'R');
        $this->recordFlight($aircraft, 1000, 'C');
        $this->recordFlight($aircraft, 1000, 'V');
        $this->recordFlight($aircraft, null);

        $this->assertNull(AssignmentGenerator::averageFlownDistance($aircraft->aircraft_configuration_id));
    }

    // =====================================================================
    // Lottery when regular routes are available
    // =====================================================================

    private function createSixPairNetwork(bool $atOrigin): void
    {
        $this->createAirports(['XD01' => 600, 'XD02' => 700, 'XD03' => 800, 'XD04' => 900, 'XD05' => 1000, 'XD06' => 1100]);
        $this->createClient('Client A', $atOrigin ? 'XORG' : 'XD01', $atOrigin ? ['XD01', 'XD02', 'XD03'] : ['XD02', 'XD03']);
        $this->createClient('Client B', 'XD04', $atOrigin ? ['XORG', 'XD05', 'XD06'] : ['XD05', 'XD06']);
    }

    public function testDirectModeLotteryAcceptsAll()
    {
        $this->createSixPairNetwork(true);
        $this->createRoute('R001', 'XORG', 'XD01');

        $this->assertCount(5, $this->generate($this->alwaysAccept()));
    }

    public function testDirectModeLotteryRejectsAll()
    {
        $this->createSixPairNetwork(true);
        $this->createRoute('R001', 'XORG', 'XD01');

        $this->assertSame([], $this->generate($this->alwaysReject()));
    }

    public function testDirectModeLotteryKeepsOnlyWinners()
    {
        $this->createSixPairNetwork(true);
        $this->createRoute('R001', 'XORG', 'XD01');
        $all = $this->generate($this->alwaysAccept());

        // 70 is the last accepted value, 71 the first rejected one
        $result = $this->generate($this->scriptedLottery([1, 71, 70, 100, 50]));

        $this->assertSame([$all[0], $all[2], $all[4]], $result);
    }

    public function testReverseModeLotteryAcceptsAll()
    {
        $this->createSixPairNetwork(false);
        $this->createRoute('R001', 'XORG', 'XD01');

        $this->assertCount(5, $this->generate($this->alwaysAccept()));
    }

    public function testReverseModeLotteryRejectsAll()
    {
        $this->createSixPairNetwork(false);
        $this->createRoute('R001', 'XORG', 'XD01');

        $this->assertSame([], $this->generate($this->alwaysReject()));
    }

    public function testReverseModeLotteryKeepsOnlyWinners()
    {
        $this->createSixPairNetwork(false);
        $this->createRoute('R001', 'XORG', 'XD01');
        $all = $this->generate($this->alwaysAccept());

        $result = $this->generate($this->scriptedLottery([100, 1, 71, 70, 99]));

        $this->assertSame([$all[1], $all[3]], $result);
    }

    public function testLotteryAcceptanceRateIsAboutSeventyPercent()
    {
        $this->createSixPairNetwork(true);
        $this->createRoute('R001', 'XORG', 'XD01');

        $offered = 0;
        foreach ($this->generateManyTimes($this->aircraft, 400) as $result) {
            $offered += count($result);
        }

        // Expected 0.7 * 5 * 400 = 1400 with a standard deviation of ~20, so ±200 is ~10 deviations
        $this->assertGreaterThan(1200, $offered);
        $this->assertLessThan(1600, $offered);
    }

    public function testRouteBeyondTheWindowMaximumDoesNotTriggerTheLottery()
    {
        $this->createSixPairNetwork(true);
        $this->createAirports(['XFAR' => 1250]);
        $this->createRoute('R001', 'XORG', 'XFAR');

        $this->assertCount(5, $this->generate($this->alwaysReject()));
    }

    public function testRouteWithinRangeButBeyondUsualDistanceDoesNotTriggerTheLottery()
    {
        $this->createAirports(['XF01' => 400, 'XF02' => 900]);
        $this->createClient('HQ Only', 'XORG');
        $this->createRoute('R001', 'XORG', 'XF02');
        $shortHaul = $this->createAircraft(self::MEDIUM_RANGE, 'XORG', 500); // window [250, 600]

        // 900 NM is within 80% of 1500 NM, but beyond the usual 600 NM: no lottery
        $this->assertSame(['XF01'], $this->destinationsOf($this->generate($this->alwaysReject(), $shortHaul)));
        // With the default window [500, 1200] the same route counts
        $this->assertSame([], $this->generate($this->alwaysReject()));
    }

    public function testRouteShorterThanTheWindowMinimumTriggersTheLottery()
    {
        $this->createSixPairNetwork(true);
        $this->createAirports(['XNEA' => 200]);
        $this->createRoute('R001', 'XORG', 'XNEA');

        $this->assertSame([], $this->generate($this->alwaysReject()));
    }

    public function testRouteFromAnotherAirportDoesNotTriggerTheLottery()
    {
        $this->createSixPairNetwork(true);
        $this->createRoute('R001', 'XD01', 'XD02');

        $this->assertCount(5, $this->generate($this->alwaysReject()));
    }

    public function testRouteToTheOriginDoesNotTriggerTheLottery()
    {
        $this->createSixPairNetwork(true);
        $this->createRoute('R001', 'XD01', 'XORG');

        $this->assertCount(5, $this->generate($this->alwaysReject()));
    }

    public function testHasFlyableRoutes()
    {
        $this->createAirports(['XD01' => 750]);
        $this->createRoute('R001', 'XORG', 'XD01');

        $this->assertTrue(AssignmentGenerator::hasFlyableRoutes('XORG', 800));
        $this->assertTrue(AssignmentGenerator::hasFlyableRoutes('XORG', 750));
        $this->assertFalse(AssignmentGenerator::hasFlyableRoutes('XORG', 749));
        $this->assertFalse(AssignmentGenerator::hasFlyableRoutes('XD01', 800));
    }

    // =====================================================================
    // Dead-end safety
    // =====================================================================

    public function testEveryDestinationHasAWayOutAndBack()
    {
        // XORG: HQ of A (hub XD01 north). XD02 (south): HQ-only client B. XF01, XF02: plain airports.
        // Distances: XORG-XD01 600, XORG-XD02 550, XD01-XD02 1150, XORG-XF01 1150, XD01-XF01 550,
        // XORG-XF02 1000 (south), XD02-XF02 450 (too close), XD01-XF02 1600 (too far)
        $this->createAirport('XD01', 600);
        $this->createAirport('XD02', -550);
        $this->createAirport('XF01', 1150);
        $this->createAirport('XF02', -1000);
        $this->createClient('Client A', 'XORG', ['XD01']);
        $this->createClient('Client B', 'XD02');

        $firstHop = $this->unionOfDestinations($this->generateAllWays());
        $this->assertSame(['XD01', 'XD02', 'XF01', 'XF02'], $firstHop, 'Own hub and every filler should show up');

        foreach ($firstHop as $destination) {
            $aircraft = $this->moveAircraft($this->aircraft, $destination);
            foreach ($this->generateAllWays($aircraft) as $result) {
                $this->assertNotEmpty($result, "Pilot stuck at $destination");
            }
        }

        // From A's hub the way back is one of A's own airports
        $aircraft = $this->moveAircraft($this->aircraft, 'XD01');
        $this->assertContains('XORG', array_column($this->generate($this->alwaysReject(), $aircraft), 'destination'));

        // From B's HQ only invented destinations are possible, XORG among them
        $aircraft = $this->moveAircraft($this->aircraft, 'XD02');
        $this->assertSame(['XD01', 'XORG'], $this->destinationsOf($this->generate($this->alwaysReject(), $aircraft)));

        // From plain airports, reverse mode offers the client airports within the window, the origin included
        $aircraft = $this->moveAircraft($this->aircraft, 'XF01');
        $this->assertSame(['XD01', 'XORG'], $this->destinationsOf($this->generate($this->alwaysReject(), $aircraft)));
        $aircraft = $this->moveAircraft($this->aircraft, 'XF02');
        $this->assertSame(['XORG'], $this->destinationsOf($this->generate($this->alwaysReject(), $aircraft)));
    }

    public function testFillerDestinationWithoutClientsLeadsBackToClientAirports()
    {
        $this->createAirports(['XF01' => 800]);
        $client = $this->createClient('HQ Only', 'XORG');

        $result = $this->generate($this->alwaysReject());
        $this->assertSame(["XF01#{$client->id}"], $this->pairsOf($result));

        $aircraft = $this->moveAircraft($this->aircraft, 'XF01');
        $back = $this->generate($this->alwaysReject(), $aircraft);
        $this->assertSame(["XORG#{$client->id}"], $this->pairsOf($back));
    }

    public function testLargeAircraftWayBackAvoidsShortRunways()
    {
        $this->createAirport('XD01', 600, [2000]);
        $this->createAirport('XF01', -600, [3500]);
        $this->createClient('Regional', 'XORG', ['XD01']);
        $large = $this->largeAircraft();

        $this->assertSame(['XF01'], $this->destinationsOf($this->generate($this->alwaysReject(), $large)));

        // At XF01 (no clients) reverse mode only offers the long runway client airport, never XD01
        $large = $this->moveAircraft($large, 'XF01');
        $this->assertSame(['XORG'], $this->destinationsOf($this->generate($this->alwaysReject(), $large)));
    }

    /**
     * Grid of airports about 60 NM apart with a mix of runway setups and client shapes.
     */
    private function createMixedGrid(): void
    {
        $runwaySetups = [[2000], [3500], [], [1200], [3000], [2600, 3400], [1800]];
        $n = 0;
        for ($row = 0; $row < 6; $row++) {
            for ($col = 0; $col < 6; $col++) {
                $this->createAirportAt(sprintf('G%d%dX', $row, $col), $row * 1.0, $col * 1.0, $runwaySetups[$n++ % count($runwaySetups)]);
            }
        }
        $this->createClient('Grid HQ Only', 'G00X');
        $this->createClient('Grid One Hub', 'G23X', ['G30X']);
        $this->createClient('Grid Network', 'G11X', ['G15X', 'G44X', 'G52X', 'G03X']);
        $this->createClient('Grid Big', 'G01X', ['G51X', 'G35X']);
        $this->createClient('Grid Remote', 'G55X');
    }

    private function randomWalk(Aircraft $aircraft, int $hops, int $seed): array
    {
        mt_srand($seed);
        $visited = [$aircraft->location => true];
        for ($i = 0; $i < $hops; $i++) {
            $result = AssignmentGenerator::generate($aircraft);
            $this->assertValidAssignments($result, $aircraft);
            $this->assertNotEmpty($result, "Pilot stuck at {$aircraft->location} after $i hops");

            $next = $result[mt_rand(0, count($result) - 1)]['destination'];
            $visited[$next] = true;
            $aircraft = $this->moveAircraft($aircraft, $next);
        }
        mt_srand();
        $icaos = array_keys($visited);
        sort($icaos, SORT_STRING);
        return $icaos;
    }

    public function testRandomWalkWithShortFieldAircraftNeverGetsStuck()
    {
        $this->createMixedGrid();
        $shortField = $this->createAircraft(1200, 'G00X', 210, self::SHORT_FIELD_MIN_RUNWAY); // window [105, 252]

        $visited = $this->randomWalk($shortField, 200, 42);

        $this->assertGreaterThanOrEqual(5, count($visited));
    }

    public function testRandomWalkWithMediumAircraftNeverGetsStuck()
    {
        $this->createMixedGrid();
        $medium = $this->createAircraft(1500, 'G00X', 260); // window [130, 312], needs 2000 m runways

        $visited = $this->randomWalk($medium, 200, 7);

        $this->assertGreaterThanOrEqual(5, count($visited));
    }

    public function testMediumAircraftInsideAClientNetworkWithEnoughAirportsStaysInIt()
    {
        $this->createMixedGrid();
        $medium = $this->createAircraft(1500, 'G11X', 260);

        // G03X has a 1200 m runway, so it is out for a medium aircraft. Every other Grid Network airport has
        // the other three within the window, so no slot is ever filled with an invented destination
        $visited = $this->randomWalk($medium, 200, 7);

        $this->assertSame(['G11X', 'G15X', 'G44X', 'G52X'], $visited);
    }

    // =====================================================================
    // Invalid input and result shape
    // =====================================================================

    public function testAircraftWithoutRangeReturnsEmpty()
    {
        $this->createAirports(['XF01' => 600]);
        $this->createClient('HQ Only', 'XORG');
        $noRange = $this->createAircraft(0, 'XORG', self::AVERAGE);

        $this->assertNull(AssignmentGenerator::distanceWindow($noRange));
        $this->assertSame([], AssignmentGenerator::generate($noRange, $this->alwaysAccept()));
    }

    public function testAircraftWithoutConfigurationReturnsEmpty()
    {
        $this->createAirports(['XF01' => 600]);
        $this->createClient('HQ Only', 'XORG');
        $aircraft = new Aircraft(['aircraft_configuration_id' => 999999, 'location' => 'XORG']);

        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));
    }

    public function testAircraftAtUnknownAirportReturnsEmpty()
    {
        $this->createAirports(['XF01' => 600]);
        $this->createClient('HQ Only', 'XORG');
        $aircraft = new Aircraft([
            'aircraft_configuration_id' => $this->aircraft->aircraft_configuration_id,
            'location' => 'ZZZZ',
        ]);

        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));
    }

    public function testAirportsInEveryDirectionAreConsidered()
    {
        // At the equator one degree of longitude is as long as one of latitude
        $deg = 700 / self::NM_PER_DEG;
        $this->createAirport('XN01', 700);
        $this->createAirport('XS01', -700);
        $this->createAirportAt('XE01', 0.0, $deg);
        $this->createAirportAt('XW01', 0.0, -$deg);
        $this->createAirportAt('XE02', 0.0, 1300 / self::NM_PER_DEG); // too far east
        $this->createClient('Compass', 'XORG', ['XN01', 'XS01', 'XE01', 'XW01', 'XE02']);

        $union = $this->unionOfDestinations($this->generateAllWays());

        $this->assertSame(['XE01', 'XN01', 'XS01', 'XW01'], $union);
    }

    public function testGenerationDoesNotWriteToTheDatabase()
    {
        $this->createAirports(['XD01' => 600, 'XF01' => 700]);
        $this->createClient('Client', 'XORG', ['XD01']);
        $this->createRoute('R001', 'XORG', 'XF01');
        $tables = ['airport', 'runway', 'client', 'client_hub', 'route', 'aircraft', 'flight', 'flight_report'];
        $count = fn() => array_map(fn($t) => (new \yii\db\Query())->from($t)->count(), $tables);
        $before = $count();
        $location = $this->aircraft->location;

        $this->generateManyTimes($this->aircraft, 10);

        $this->assertSame($before, $count());
        $this->assertSame($location, Aircraft::findOne($this->aircraft->id)->location);
    }

    public function testResultShapeAndDistance()
    {
        $this->createAirports(['XD01' => 750]);
        $client = $this->createClient('Shape Client', 'XORG', ['XD01']);

        $result = $this->generate($this->alwaysAccept());

        $this->assertSame([['destination' => 'XD01', 'client_id' => $client->id, 'distance_nm' => 750]], $result);
    }
}
