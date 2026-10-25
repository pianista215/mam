<?php

namespace tests\unit\helpers;

use app\helpers\AssignmentGenerator;
use app\models\Aircraft;
use tests\unit\BaseUnitTest;

/**
 * One rule per test on a synthetic network: every airport lies on the same meridian, so the distance
 * from the origin XORG (latitude 0) is exactly the NM given when creating it.
 *
 * Default aircraft range is 1000 NM (limit 800 NM, not a small aircraft). Every airport gets a 2000 m runway
 * unless the test says otherwise. Naming: XDnn = client airports, XFnn = plain airports usable as fillers.
 */
class AssignmentGeneratorTest extends BaseUnitTest
{
    use AssignmentGeneratorTestTrait;

    // Nautical miles per degree of latitude, matching GeoUtils::haversine
    private const NM_PER_DEG = 6371.0 * M_PI / 180 * 0.539957;

    private const LARGE_RANGE = 1000;
    private const SMALL_RANGE = 999;

    private Aircraft $aircraft;

    protected function _before()
    {
        parent::_before();
        $this->clearDatabase();
        $this->createCountry();
        $this->createAirport('XORG', 0);
        $this->aircraft = $this->createAircraft(self::LARGE_RANGE, 'XORG');
    }

    private function createAirport(string $icao, float $distanceFromOriginNm, array $runwayLengths = [2000]): void
    {
        $this->createAirportAt($icao, $distanceFromOriginNm / self::NM_PER_DEG, 0.0, $runwayLengths);
    }

    /** @param array<string, float> $airports icao => NM from origin, all with a 2000 m runway */
    private function createAirports(array $airports): void
    {
        foreach ($airports as $icao => $nm) {
            $this->createAirport($icao, $nm);
        }
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
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300, 'XD04' => 400, 'XD05' => 500, 'XF01' => 150, 'XF02' => 250]);
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
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300, 'XD04' => 400, 'XD05' => 500]);
        $this->createClient('Big Network', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04', 'XD05']);

        $union = $this->unionOfDestinations($this->generateManyTimes($this->aircraft));

        $this->assertSame(['XD01', 'XD02', 'XD03', 'XD04', 'XD05'], $union);
    }

    public function testClientWithExactlyThreeHubsGetsAllOfThem()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300, 'XF01' => 150]);
        $this->createClient('Three Hubs', 'XORG', ['XD01', 'XD02', 'XD03']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XD02', 'XD03'], $this->destinationsOf($result));
        }
    }

    public function testHeadquartersOnlyClientGetsThreeInventedAssignments()
    {
        $this->createAirports(['XF01' => 100, 'XF02' => 200, 'XF03' => 300, 'XF04' => 400]);
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
        $this->createAirports(['XF01' => 100, 'XF02' => 200, 'XF03' => 300, 'XF04' => 400]);
        $this->createClient('HQ Only', 'XORG');

        // Without swaps the first eligible airports (sorted by ICAO) are used
        $this->assertSame(['XF01', 'XF02', 'XF03'], $this->destinationsOf($this->generate($this->alwaysAccept())));
    }

    public function testHeadquartersOnlyClientFillersVaryBetweenRuns()
    {
        $this->createAirports(['XF01' => 100, 'XF02' => 200, 'XF03' => 300, 'XF04' => 400, 'XF05' => 500, 'XF06' => 600]);
        $this->createClient('HQ Only', 'XORG');

        $union = $this->unionOfDestinations($this->generateManyTimes($this->aircraft));

        $this->assertSame(['XF01', 'XF02', 'XF03', 'XF04', 'XF05', 'XF06'], $union);
    }

    public function testOneHubClientGetsItsHubPlusTwoInventedAssignments()
    {
        $this->createAirports(['XD01' => 100, 'XF01' => 150, 'XF02' => 250, 'XF03' => 350, 'XF04' => 450]);
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
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XF01' => 150, 'XF02' => 250]);
        $this->createClient('Two Hubs', 'XORG', ['XD01', 'XD02']);

        foreach ($this->generateAllWays() as $result) {
            $destinations = array_column($result, 'destination');
            $this->assertCount(3, $result);
            $this->assertContains('XD01', $destinations);
            $this->assertContains('XD02', $destinations);
            $this->assertCount(1, array_intersect($destinations, ['XF01', 'XF02']));
        }
    }

    public function testHubsBeyondRangeAreReplacedByFillers()
    {
        $this->createAirports(['XD01' => 850, 'XD02' => 900, 'XF01' => 100, 'XF02' => 200, 'XF03' => 300]);
        $this->createClient('Far Hubs', 'XORG', ['XD01', 'XD02']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02', 'XF03'], $this->destinationsOf($result));
        }
    }

    public function testHubsWithoutRunwaysAreReplacedByFillers()
    {
        $this->createAirport('XD01', 100, []);
        $this->createAirport('XD02', 200, []);
        $this->createAirports(['XF01' => 150, 'XF02' => 250, 'XF03' => 350]);
        $this->createClient('Heliports', 'XORG', ['XD01', 'XD02']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02', 'XF03'], $this->destinationsOf($result));
        }
    }

    public function testSmallAircraftNeverGetsBigHubsButGetsSmallFillers()
    {
        $this->createAirport('XD01', 100, [3500]);
        $this->createAirport('XD02', 200, [4000]);
        $this->createAirport('XD03', 300, [3200]);
        $this->createAirports(['XF01' => 150, 'XF02' => 250, 'XF03' => 350]);
        $this->createClient('Major Carrier', 'XORG', ['XD01', 'XD02', 'XD03']);
        $small = $this->createAircraft(self::SMALL_RANGE, 'XORG');

        foreach ($this->generateAllWays($small) as $result) {
            $this->assertSame(['XF01', 'XF02', 'XF03'], $this->destinationsOf($result));
        }
    }

    public function testLargeAircraftGetsBigHubs()
    {
        $this->createAirport('XD01', 100, [3500]);
        $this->createAirport('XD02', 200, [4000]);
        $this->createAirport('XD03', 300, [3200]);
        $this->createAirports(['XF01' => 150, 'XF02' => 250, 'XF03' => 350]);
        $this->createClient('Major Carrier', 'XORG', ['XD01', 'XD02', 'XD03']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XD02', 'XD03'], $this->destinationsOf($result));
        }
    }

    public function testOriginAsHubOffersHeadquartersAndOtherHubs()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300, 'XF01' => 150]);
        $client = $this->createClient('Hub Client', 'XD01', ['XORG', 'XD02', 'XD03']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XD02', 'XD03'], $this->destinationsOf($result));
            $this->assertSame([$client->id => 3], $this->countByClient($result));
        }
    }

    public function testOriginAsHubOfHeadquartersOnlyLikeClientUsesHeadquartersAndFillers()
    {
        $this->createAirports(['XD01' => 100, 'XF01' => 150, 'XF02' => 250]);
        $this->createClient('Single Hub', 'XD01', ['XORG']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XF01', 'XF02'], $this->destinationsOf($result));
        }
    }

    public function testOriginRelatedToTwoClientsPoolsBothAndPicksFive()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300, 'XD04' => 400, 'XD05' => 500, 'XD06' => 600]);
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
        $this->createAirports(['XD01' => 100]);
        $a = $this->createClient('Client A', 'XORG', ['XD01']);
        $b = $this->createClient('Client B', 'XORG', ['XD01']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$a->id}", "XD01#{$b->id}"], $this->pairsOf($result));
        }
    }

    public function testSharedHubWithFillersNeverDuplicatesAPair()
    {
        $this->createAirports(['XD01' => 100, 'XF01' => 150, 'XF02' => 250, 'XF03' => 350]);
        $this->createClient('Client A', 'XORG', ['XD01']);
        $this->createClient('Client B', 'XORG', ['XD01']);

        foreach ($this->generateAllWays() as $result) {
            // 6 candidate pairs (3 per client), 5 picked; uniqueness is checked by assertValidAssignments
            $this->assertCount(AssignmentGenerator::COUNT, $result);
        }
    }

    public function testFillersNeverRepeatOwnAirportsOrEachOther()
    {
        $this->createAirports(['XD01' => 100, 'XF01' => 150, 'XF02' => 250]);
        $this->createClient('One Hub', 'XORG', ['XD01']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XF01', 'XF02'], $this->destinationsOf($result));
        }
    }

    public function testFillerMayBeAnotherClientsAirportButKeepsTheGapClient()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200]);
        $gap = $this->createClient('HQ Only', 'XORG');
        $this->createClient('Elsewhere', 'XD01', ['XD02']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$gap->id}", "XD02#{$gap->id}"], $this->pairsOf($result));
        }
    }

    public function testFillerPoolSmallerThanGapsOffersWhatExists()
    {
        $this->createAirports(['XF01' => 100, 'XF02' => 200]);
        $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02'], $this->destinationsOf($result));
        }
    }

    public function testNoEligibleAirportAtAllReturnsEmpty()
    {
        $this->createAirport('XF01', 900);
        $this->createAirport('XF02', 100, []);
        $this->createClient('Isolated', 'XORG', ['XF01', 'XF02']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame([], $result);
        }
    }

    public function testUnrelatedClientsNeverGetAssignmentsInDirectMode()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300, 'XD04' => 400, 'XD05' => 500]);
        $related = $this->createClient('Related', 'XORG', ['XD01', 'XD02', 'XD03']);
        $this->createClient('Unrelated', 'XD04', ['XD05']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame([$related->id => 3], $this->countByClient($result));
        }
    }

    public function testManyRelatedClientsAreCappedAtFive()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XF01' => 300, 'XF02' => 400, 'XF03' => 500, 'XF04' => 600]);
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
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XF01' => 300, 'XF02' => 400]);
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
    // Reverse mode: no client at the origin
    // =====================================================================

    public function testReverseModeOffersOnlyClientAirports()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300, 'XF01' => 150, 'XF02' => 250]);
        $a = $this->createClient('Client A', 'XD01', ['XD02']);
        $b = $this->createClient('Client B', 'XD03');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$a->id}", "XD02#{$a->id}", "XD03#{$b->id}"], $this->pairsOf($result));
        }
    }

    public function testReverseModeCapsEachClientAtThree()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300, 'XD04' => 400, 'XD05' => 500]);
        $client = $this->createClient('Big Network', 'XD01', ['XD02', 'XD03', 'XD04', 'XD05']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame([$client->id => 3], $this->countByClient($result));
        }
    }

    public function testReverseModeWithSeveralClientsPicksFive()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300, 'XD04' => 400, 'XD05' => 500, 'XD06' => 600]);
        $this->createClient('Client A', 'XD01', ['XD02', 'XD03']);
        $this->createClient('Client B', 'XD04', ['XD05', 'XD06']);
        $this->createClient('Client C', 'XD02');

        foreach ($this->generateAllWays() as $result) {
            $this->assertCount(AssignmentGenerator::COUNT, $result);
        }
    }

    public function testReverseModeExcludesFarAndRunwaylessAirports()
    {
        $this->createAirport('XD01', 850);
        $this->createAirport('XD02', 100, []);
        $this->createAirports(['XD03' => 200]);
        $client = $this->createClient('Mixed', 'XD01', ['XD02', 'XD03']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD03#{$client->id}"], $this->pairsOf($result));
        }
    }

    public function testReverseModeSmallAircraftExcludesBigAirports()
    {
        $this->createAirport('XD01', 100, [3500]);
        $this->createAirports(['XD02' => 200]);
        $client = $this->createClient('Mixed', 'XD01', ['XD02']);
        $small = $this->createAircraft(self::SMALL_RANGE, 'XORG');

        foreach ($this->generateAllWays($small) as $result) {
            $this->assertSame(["XD02#{$client->id}"], $this->pairsOf($result));
        }
        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$client->id}", "XD02#{$client->id}"], $this->pairsOf($result));
        }
    }

    public function testReverseModeSharedHubGivesOnePairPerClient()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300]);
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
        $this->createAirports(['XD01' => 100, 'XF01' => 150, 'XF02' => 250, 'XF03' => 350, 'XF04' => 450]);
        $client = $this->createClient('Lonely', 'XD01');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(["XD01#{$client->id}"], $this->pairsOf($result));
        }
    }

    public function testNoClientsAtAllReturnsEmpty()
    {
        $this->createAirports(['XF01' => 100, 'XF02' => 200]);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame([], $result);
        }
    }

    public function testClientsOutOfRangeReturnsEmpty()
    {
        $this->createAirports(['XF01' => 100, 'XD01' => 850, 'XD02' => 900]);
        $this->createClient('Far Away', 'XD01', ['XD02']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame([], $result);
        }
    }

    // =====================================================================
    // Eligibility: runways and range
    // =====================================================================

    public function testSmallAircraftRunwayLengthBoundaries()
    {
        $this->createAirport('XD01', 100, [3000]);      // exactly the limit: allowed
        $this->createAirport('XD02', 200, [3000.1]);    // just above: excluded
        $this->createAirport('XD03', 300, [3200]);      // big: excluded
        $this->createAirport('XD04', 400, [2000, 3200]); // any runway above the limit excludes it
        $this->createAirport('XD05', 500, [800]);       // small strip: allowed
        $this->createClient('Runway Mix', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04', 'XD05']);
        $small = $this->createAircraft(self::SMALL_RANGE, 'XORG');

        foreach ($this->generateAllWays($small) as $result) {
            // Only two own airports are eligible and no other airport is left for the third slot
            $this->assertSame(['XD01', 'XD05'], $this->destinationsOf($result));
        }
    }

    public function testLargeAircraftIgnoresRunwayLength()
    {
        $this->createAirport('XD01', 100, [3000]);
        $this->createAirport('XD02', 200, [3000.1]);
        $this->createAirport('XD03', 300, [3200]);
        $this->createAirport('XD04', 400, [2000, 3200]);
        $this->createAirport('XD05', 500, [800]);
        $this->createClient('Runway Mix', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04', 'XD05']);

        $union = $this->unionOfDestinations($this->generateAllWays());

        $this->assertSame(['XD01', 'XD02', 'XD03', 'XD04', 'XD05'], $union);
    }

    public function testRangeBoundaryBetweenSmallAndLargeAircraft()
    {
        $this->createAirport('XD01', 100, [3500]);
        $this->createClient('Big Airport Client', 'XORG', ['XD01']);

        $this->assertSame(['XD01'], $this->destinationsOf($this->generate($this->alwaysAccept())));

        $small = $this->createAircraft(self::SMALL_RANGE, 'XORG');
        $this->assertSame([], $this->generate($this->alwaysAccept(), $small));
    }

    public function testAirportWithoutRunwaysIsNeverAFiller()
    {
        $this->createAirport('XF01', 100, []);
        $this->createAirports(['XF02' => 200]);
        $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF02'], $this->destinationsOf($result));
        }
    }

    public function testBigAirportIsNeverAFillerForSmallAircraft()
    {
        $this->createAirport('XF01', 100, [3500]);
        $this->createAirports(['XF02' => 200]);
        $this->createClient('HQ Only', 'XORG');
        $small = $this->createAircraft(self::SMALL_RANGE, 'XORG');

        foreach ($this->generateAllWays($small) as $result) {
            $this->assertSame(['XF02'], $this->destinationsOf($result));
        }
        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01', 'XF02'], $this->destinationsOf($result));
        }
    }

    public function testEightyPercentRangeLimit()
    {
        // Limit is 800 NM: 750 and 799 in, 801 and 850 out (still within the full 1000 NM range)
        $this->createAirports(['XD01' => 750, 'XD02' => 799, 'XD03' => 801, 'XD04' => 850]);
        $this->createClient('Range Client', 'XORG', ['XD01', 'XD02', 'XD03', 'XD04']);

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XD01', 'XD02'], $this->destinationsOf($result));
        }
    }

    public function testRangeLimitAppliesToFillers()
    {
        $this->createAirports(['XF01' => 700, 'XF02' => 801, 'XF03' => 950]);
        $this->createClient('HQ Only', 'XORG');

        foreach ($this->generateAllWays() as $result) {
            $this->assertSame(['XF01'], $this->destinationsOf($result));
        }
    }

    public function testShorterRangeShrinksTheOffer()
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 300, 'XD03' => 500]);
        $this->createClient('Network', 'XORG', ['XD01', 'XD02', 'XD03']);
        $short = $this->createAircraft(400, 'XORG'); // limit 320 NM

        foreach ($this->generateAllWays($short) as $result) {
            $this->assertSame(['XD01', 'XD02'], $this->destinationsOf($result));
        }
    }

    // =====================================================================
    // Lottery when regular routes are available
    // =====================================================================

    private function createSixPairNetwork(bool $atOrigin): void
    {
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XD03' => 300, 'XD04' => 400, 'XD05' => 500, 'XD06' => 600]);
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
        $runs = 400;
        foreach ($this->generateManyTimes($this->aircraft, $runs) as $result) {
            $offered += count($result);
        }

        // Expected 0.7 * 5 * 400 = 1400 with a standard deviation of ~20, so ±200 is ~10 deviations
        $this->assertGreaterThan(1200, $offered);
        $this->assertLessThan(1600, $offered);
    }

    public function testRouteBeyondEightyPercentDoesNotTriggerTheLottery()
    {
        $this->createSixPairNetwork(true);
        $this->createAirports(['XFAR' => 850]);
        $this->createRoute('R001', 'XORG', 'XFAR');

        $this->assertCount(5, $this->generate($this->alwaysReject()));
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

    public function testRouteOutOfRangeForSmallAircraftDoesNotTriggerTheLottery()
    {
        $this->createAirports(['XF01' => 100, 'XF02' => 790]);
        $this->createClient('HQ Only', 'XORG');
        $this->createRoute('R001', 'XORG', 'XF02');
        $short = $this->createAircraft(500, 'XORG'); // limit 400 NM: the route doesn't count, XF02 isn't eligible

        $this->assertSame(['XF01'], $this->destinationsOf($this->generate($this->alwaysReject(), $short)));
        $this->assertSame([], $this->generate($this->alwaysReject())); // 1000 NM: the route counts
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
        // XORG: HQ of A (one hub XD01). XD02: HQ-only client B. XF01, XF02: plain airports.
        $this->createAirports(['XD01' => 100, 'XD02' => 200, 'XF01' => 300, 'XF02' => 400]);
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

        // From a plain airport, reverse mode offers every client airport in range, the origin included
        $aircraft = $this->moveAircraft($this->aircraft, 'XF01');
        $this->assertSame(['XD01', 'XD02', 'XORG'], $this->destinationsOf($this->generate($this->alwaysReject(), $aircraft)));
    }

    public function testFillerDestinationWithoutClientsLeadsBackToClientAirports()
    {
        $this->createAirports(['XF01' => 300]);
        $client = $this->createClient('HQ Only', 'XORG');

        $result = $this->generate($this->alwaysReject());
        $this->assertSame(["XF01#{$client->id}"], $this->pairsOf($result));

        $aircraft = $this->moveAircraft($this->aircraft, 'XF01');
        $back = $this->generate($this->alwaysReject(), $aircraft);
        $this->assertSame(["XORG#{$client->id}"], $this->pairsOf($back));
    }

    public function testSmallAircraftWayBackAvoidsBigAirports()
    {
        $this->createAirport('XD01', 100, [3500]);
        $this->createAirports(['XF01' => 200]);
        $this->createClient('Major', 'XORG', ['XD01']);
        $small = $this->createAircraft(self::SMALL_RANGE, 'XORG');

        $result = $this->generate($this->alwaysReject(), $small);
        $this->assertSame(['XF01'], $this->destinationsOf($result));

        // At XF01 (no clients) reverse mode only offers the small client airport, never XD01
        $small = $this->moveAircraft($small, 'XF01');
        $this->assertSame(['XORG'], $this->destinationsOf($this->generate($this->alwaysReject(), $small)));
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
                $this->createAirportAt(sprintf('G%d%d', $row, $col) . 'X', $row * 1.0, $col * 1.0, $runwaySetups[$n++ % count($runwaySetups)]);
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
        // Grid Network is absorbing (no fillers inside it), so the start counts to keep the
        // variety check seed-independent: worst case is the start plus the 5 network airports
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
        return array_keys($visited);
    }

    public function testRandomWalkWithSmallAircraftNeverGetsStuckNorLandsOnBigAirports()
    {
        $this->createMixedGrid();
        $small = $this->createAircraft(400, 'G00X'); // limit 320 NM, small aircraft

        $visited = $this->randomWalk($small, 200, 42);

        $this->assertGreaterThan(5, count($visited));
    }

    public function testRandomWalkWithLargeAircraftNeverGetsStuck()
    {
        $this->createMixedGrid();
        $large = $this->createAircraft(1500, 'G00X'); // limit 1200 NM, big airports allowed

        $visited = $this->randomWalk($large, 200, 7);

        $this->assertGreaterThan(5, count($visited));
    }

    public function testLargeAircraftInsideAClientNetworkWithEnoughAirportsStaysInIt()
    {
        $this->createMixedGrid();
        $large = $this->createAircraft(1500, 'G11X');

        // Every Grid Network airport has at least 3 other eligible airports of the same client,
        // so no slot is ever filled with an invented destination
        $visited = $this->randomWalk($large, 200, 7);

        sort($visited, SORT_STRING);
        $this->assertSame(['G03X', 'G11X', 'G15X', 'G44X', 'G52X'], $visited);
    }

    // =====================================================================
    // Invalid input and result shape
    // =====================================================================

    public function testAircraftWithoutRangeReturnsEmpty()
    {
        $this->createAirports(['XF01' => 100]);
        $this->createClient('HQ Only', 'XORG');
        $noRange = $this->createAircraft(0, 'XORG');

        $this->assertSame([], AssignmentGenerator::generate($noRange, $this->alwaysAccept()));
    }

    public function testAircraftWithoutConfigurationReturnsEmpty()
    {
        $this->createAirports(['XF01' => 100]);
        $this->createClient('HQ Only', 'XORG');
        $aircraft = new Aircraft(['aircraft_configuration_id' => 999999, 'location' => 'XORG']);

        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));
    }

    public function testAircraftAtUnknownAirportReturnsEmpty()
    {
        $this->createAirports(['XF01' => 100]);
        $this->createClient('HQ Only', 'XORG');
        $aircraft = new Aircraft([
            'aircraft_configuration_id' => $this->aircraft->aircraft_configuration_id,
            'location' => 'ZZZZ',
        ]);

        $this->assertSame([], AssignmentGenerator::generate($aircraft, $this->alwaysAccept()));
    }

    public function testResultShapeAndDistance()
    {
        $this->createAirports(['XD01' => 750]);
        $client = $this->createClient('Shape Client', 'XORG', ['XD01']);

        $result = $this->generate($this->alwaysAccept());

        $this->assertSame([['destination' => 'XD01', 'client_id' => $client->id, 'distance_nm' => 750]], $result);
    }
}
