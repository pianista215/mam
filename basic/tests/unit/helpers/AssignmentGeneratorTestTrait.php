<?php

namespace tests\unit\helpers;

use app\helpers\AssignmentGenerator;
use app\helpers\GeoUtils;
use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftType;
use app\models\Airport;
use app\models\Client;
use app\models\ClientHub;
use app\models\Country;
use app\models\Route;
use app\models\Runway;

/**
 * Shared fixtures, random stubs and invariant checks for the AssignmentGenerator tests.
 */
trait AssignmentGeneratorTestTrait
{
    private int $aircraftCounter = 0;

    private function createCountry(): void
    {
        (new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']))->save(false);
    }

    /**
     * @param float[] $runwayLengths One runway per length; empty means an airport without runways
     */
    private function createAirportAt(string $icao, float $latitude, float $longitude, array $runwayLengths = [2000]): void
    {
        (new Airport([
            'icao_code' => $icao,
            'name' => $icao,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'city' => $icao,
            'country_id' => 1,
        ]))->save(false);

        foreach (array_values($runwayLengths) as $i => $length) {
            $this->createRunway($icao, $length, sprintf('%02d/%02d', $i + 1, $i + 19));
        }
    }

    private function createRunway(string $icao, float $lengthM, string $designators = '09/27'): void
    {
        $runway = new Runway(['airport_icao' => $icao, 'designators' => $designators, 'width_m' => 45, 'length_m' => $lengthM]);
        $this->assertTrue($runway->save(), json_encode($runway->errors));
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

    /**
     * Each aircraft gets its own type, so ranges never interfere between aircraft.
     */
    private function createAircraft(int $rangeNm, string $location): Aircraft
    {
        $this->aircraftCounter++;

        $type = new AircraftType([
            'icao_type_code' => sprintf('T%03d', $this->aircraftCounter),
            'name' => "Type {$this->aircraftCounter}",
            'max_nm_range' => $rangeNm,
        ]);
        $type->save(false);

        $config = new AircraftConfiguration([
            'aircraft_type_id' => $type->id,
            'name' => 'Config ' . $this->aircraftCounter,
            'pax_capacity' => 4,
            'cargo_capacity' => 100,
            'crew' => 1,
            'mtow' => 1400,
            'oew' => 900,
        ]);
        $config->save(false);

        $aircraft = new Aircraft([
            'aircraft_configuration_id' => $config->id,
            'registration' => 'EC-' . $this->aircraftCounter,
            'name' => 'Aircraft ' . $this->aircraftCounter,
            'location' => $location,
            'hours_flown' => 0,
        ]);
        $aircraft->save(false);
        return Aircraft::findOne($aircraft->id);
    }

    private function moveAircraft(Aircraft $aircraft, string $icao): Aircraft
    {
        $aircraft->location = $icao;
        $aircraft->save(false);
        return Aircraft::findOne($aircraft->id);
    }

    // --- Random sources. Picks call rand($i, $n - 1); the lottery calls rand(1, 100). ---

    /** Never swaps on picks (keeps candidate order) and always accepts on the lottery */
    private function alwaysAccept(): callable
    {
        return fn(int $min, int $max) => $min;
    }

    /** Always swaps with the last item on picks and always rejects on the lottery */
    private function alwaysReject(): callable
    {
        return fn(int $min, int $max) => $max;
    }

    /** Never swaps on picks and returns the given values on the lottery */
    private function scriptedLottery(array $values): callable
    {
        return function (int $min, int $max) use (&$values) {
            if ($min === 1 && $max === 100) {
                return array_shift($values);
            }
            return $min;
        };
    }

    // --- Result helpers ---

    private function destinationsOf(array $assignments): array
    {
        $icaos = array_column($assignments, 'destination');
        sort($icaos);
        return $icaos;
    }

    private function pairsOf(array $assignments): array
    {
        $pairs = array_map(fn($a) => $a['destination'] . '#' . $a['client_id'], $assignments);
        sort($pairs);
        return $pairs;
    }

    private function countByClient(array $assignments): array
    {
        return array_count_values(array_column($assignments, 'client_id'));
    }

    /**
     * Runs the generator with real randomness (seeded for reproducibility) and returns every result.
     * @return array<int, array> One result per run
     */
    private function generateManyTimes(Aircraft $aircraft, int $runs = 30, int $seed = 1234): array
    {
        mt_srand($seed);
        $results = [];
        for ($i = 0; $i < $runs; $i++) {
            $results[] = AssignmentGenerator::generate($aircraft);
        }
        mt_srand();
        return $results;
    }

    private function unionOfDestinations(array $results): array
    {
        $union = [];
        foreach ($results as $result) {
            foreach ($result as $assignment) {
                $union[$assignment['destination']] = true;
            }
        }
        $icaos = array_keys($union);
        sort($icaos);
        return $icaos;
    }

    private function clientAirports(int $clientId): array
    {
        $client = Client::findOne($clientId);
        return array_merge(
            [$client->headquarters_icao],
            ClientHub::find()->select('airport_icao')->where(['client_id' => $clientId])->column()
        );
    }

    /**
     * Checks every rule an assignment list must satisfy for the given aircraft.
     */
    private function assertValidAssignments(array $assignments, Aircraft $aircraft): void
    {
        $aircraft = Aircraft::findOne($aircraft->id);
        $origin = $aircraft->location0;
        $range = $aircraft->aircraftConfiguration->aircraftType->max_nm_range;
        $maxNm = $range * AssignmentGenerator::RANGE_FACTOR;
        $isSmall = $range < AssignmentGenerator::SMALL_AIRCRAFT_MAX_RANGE_NM;

        $this->assertLessThanOrEqual(AssignmentGenerator::COUNT, count($assignments));

        $pairs = $this->pairsOf($assignments);
        $this->assertSame($pairs, array_values(array_unique($pairs)), 'Duplicated (destination, client) pair');

        foreach ($this->countByClient($assignments) as $clientId => $count) {
            $this->assertLessThanOrEqual(AssignmentGenerator::CLIENT_SLOTS, $count, "Client $clientId exceeds its slots");
        }

        $relatedClientIds = array_map('intval', array_unique(array_merge(
            Client::find()->select('id')->where(['headquarters_icao' => $origin->icao_code])->column(),
            ClientHub::find()->select('client_id')->where(['airport_icao' => $origin->icao_code])->column()
        )));

        foreach ($assignments as $assignment) {
            $icao = $assignment['destination'];
            $this->assertSame(['destination', 'client_id', 'distance_nm'], array_keys($assignment));
            $this->assertNotSame($origin->icao_code, $icao, 'Origin offered as destination');

            $runways = Runway::find()->where(['airport_icao' => $icao])->all();
            $this->assertNotEmpty($runways, "$icao has no runways");
            if ($isSmall) {
                foreach ($runways as $runway) {
                    $this->assertLessThanOrEqual(
                        AssignmentGenerator::LARGE_RUNWAY_MIN_LENGTH_M,
                        (float) $runway->length_m,
                        "Small aircraft sent to $icao with a {$runway->length_m} m runway"
                    );
                }
            }

            $dest = Airport::findOne(['icao_code' => $icao]);
            $distance = GeoUtils::haversine($origin->latitude, $origin->longitude, $dest->latitude, $dest->longitude, 'nm');
            $this->assertLessThanOrEqual($maxNm, $distance, "$icao is beyond 80% of the range");
            $this->assertSame((int) round($distance), $assignment['distance_nm']);

            if (!empty($relatedClientIds)) {
                // Direct mode: only clients related to the origin
                $this->assertContains($assignment['client_id'], $relatedClientIds, "Client unrelated to the origin");
            } else {
                // Reverse mode: only the client's own airports
                $this->assertContains($icao, $this->clientAirports($assignment['client_id']), "$icao is not an airport of its client");
            }
        }
    }
}
