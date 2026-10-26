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
use app\models\Flight;
use app\models\FlightReport;
use app\models\Pilot;
use app\models\Route;
use app\models\Runway;

/**
 * Shared fixtures, random stubs and invariant checks for the AssignmentGenerator tests.
 */
trait AssignmentGeneratorTestTrait
{
    private int $aircraftCounter = 0;
    private int $flightCounter = 0;
    private ?int $pilotId = null;
    /** @var array<string, string> runway length => reference airport ICAO */
    private array $referenceRunways = [];
    /** @var array<string, int> metric code => metric type id */
    private array $metricTypeIds = [];
    /** @var array<string, int> metric code => phase type id */
    private array $phaseTypeIds = [];

    private static int $SPAIN = 1;
    private static int $PORTUGAL = 2;
    private static int $FRANCE = 3;

    private function createCountry(): void
    {
        (new Country(['id' => self::$SPAIN, 'name' => 'Spain', 'iso2_code' => 'ES']))->save(false);
        (new Country(['id' => self::$PORTUGAL, 'name' => 'Portugal', 'iso2_code' => 'PT']))->save(false);
        (new Country(['id' => self::$FRANCE, 'name' => 'France', 'iso2_code' => 'FR']))->save(false);
    }

    /**
     * @param float[] $runwayLengths One runway per length; empty means an airport without runways
     */
    private function createAirportAt(string $icao, float $latitude, float $longitude, array $runwayLengths = [2000], int $countryId = 1, ?string $name = null): void
    {
        (new Airport([
            'icao_code' => $icao,
            'name' => $name ?? $icao,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'city' => $icao,
            'country_id' => $countryId,
        ]))->save(false);

        foreach (array_values($runwayLengths) as $i => $length) {
            $this->createRunway($icao, $length, sprintf('%02d/%02d', $i + 1, $i + 19));
        }
    }

    /**
     * Creates a runway and one runway end per designator (e.g. "09/27" -> "09" and "27").
     */
    private function createRunway(string $icao, float $lengthM, string $designators = '09/27'): void
    {
        $runway = new Runway(['airport_icao' => $icao, 'designators' => $designators, 'width_m' => 45, 'length_m' => $lengthM]);
        $this->assertTrue($runway->save(), json_encode($runway->errors));

        foreach (explode('/', $designators) as $designator) {
            \Yii::$app->db->createCommand()->insert('runway_end', [
                'runway_id' => $runway->id,
                'designator' => $designator,
                'latitude' => 0,
                'longitude' => 0,
                'true_heading_deg' => 0,
            ])->execute();
        }
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
     * Each aircraft gets its own type and configuration, so ranges and flight history never interfere.
     *
     * @param float|null $averageNm Distance of the MIN_FLIGHTS finished flights recorded for the configuration
     *                              (so it is also their average); null leaves the configuration without flight history
     * @param float $minRunwayM Length of the runway all those flights took off from, so it becomes the configuration's
     *                          minimum runway
     */
    private function createAircraft(int $rangeNm, string $location, ?float $averageNm, float $minRunwayM = 2000, int $paxCapacity = 4): Aircraft
    {
        $aircraft = $this->createAircraftWithoutHistory($rangeNm, $location, $paxCapacity);
        if ($averageNm !== null) {
            for ($i = 0; $i < AssignmentGenerator::MIN_FLIGHTS; $i++) {
                $this->recordFlight($aircraft, $averageNm, Flight::STATUS_FINISHED, $minRunwayM);
            }
        }
        return $aircraft;
    }

    private function createAircraftWithoutHistory(int $rangeNm, string $location, int $paxCapacity = 4): Aircraft
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
            'pax_capacity' => $paxCapacity,
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

    /**
     * Same type and configuration as the given aircraft, at another location (shares its flight history).
     */
    private function createSiblingAircraft(Aircraft $aircraft, string $location): Aircraft
    {
        $this->aircraftCounter++;
        $sibling = new Aircraft([
            'aircraft_configuration_id' => $aircraft->aircraft_configuration_id,
            'registration' => 'EC-' . $this->aircraftCounter,
            'name' => 'Aircraft ' . $this->aircraftCounter,
            'location' => $location,
            'hours_flown' => 0,
        ]);
        $sibling->save(false);
        return Aircraft::findOne($sibling->id);
    }

    /**
     * Records a flight of the aircraft. With $takeoffRunwayM, the flight departs from a reference airport far away
     * whose only runway has that length, and its takeoff phase records that runway.
     */
    private function recordFlight(Aircraft $aircraft, ?float $distanceNm, string $status = Flight::STATUS_FINISHED, ?float $takeoffRunwayM = null): void
    {
        if ($takeoffRunwayM === null) {
            $this->recordFlightFrom($aircraft, $distanceNm, $status, $aircraft->location);
        } else {
            $this->recordFlightFrom($aircraft, $distanceNm, $status, $this->referenceRunwayAirport($takeoffRunwayM), '09');
        }
    }

    /**
     * Airport at latitude -80 (never within any distance window) with a single 09/27 runway of the given length.
     */
    private function referenceRunwayAirport(float $lengthM): string
    {
        $key = (string) $lengthM;
        if (!isset($this->referenceRunways[$key])) {
            $icao = sprintf('R%03d', count($this->referenceRunways) + 1);
            $this->createAirportAt($icao, -80.0, count($this->referenceRunways) * 0.1, [], 1, "Reference $lengthM m");
            $this->createRunway($icao, $lengthM, '09/27');
            $this->referenceRunways[$key] = $icao;
        }
        return $this->referenceRunways[$key];
    }

    /**
     * @param string|null $takeoffDesignator Runway end recorded as TakeoffRunway, if any
     * @param string|null $landingDesignator Runway end recorded as LandingRunway, if any
     */
    private function recordFlightFrom(
        Aircraft $aircraft,
        ?float $distanceNm,
        string $status,
        string $departure,
        ?string $takeoffDesignator = null,
        ?string $landingDesignator = null
    ): void {
        $this->flightCounter++;
        $flight = new Flight([
            'pilot_id' => $this->getPilotId($aircraft->location),
            'aircraft_id' => $aircraft->id,
            'code' => 'ASG' . $this->flightCounter,
            'departure' => $departure,
            'arrival' => $departure,
            'alternative1_icao' => $departure,
            'flight_rules' => 'V',
            'cruise_speed_unit' => 'N',
            'cruise_speed_value' => '120',
            'flight_level_unit' => 'VFR',
            'flight_level_value' => '',
            'route' => 'DCT',
            'estimated_time' => '0100',
            'other_information' => 'Test',
            'endurance_time' => '0300',
            'report_tool' => 'Test',
            'status' => $status,
            'creation_date' => '2026-10-01 10:00:00',
            'flight_type' => 'C',
        ]);
        $flight->save(false);

        $report = new FlightReport([
            'flight_id' => $flight->id,
            'start_time' => '2026-10-01 10:00:00',
            'end_time' => '2026-10-01 11:00:00',
            'flight_time_minutes' => 60,
            'distance_nm' => $distanceNm === null ? null : (int) round($distanceNm),
            'sim_aircraft_name' => 'Test Aircraft',
        ]);
        $report->save(false);

        if ($takeoffDesignator !== null) {
            $this->recordPhaseMetric($report->id, 'takeoff', 'TakeoffRunway', $takeoffDesignator);
        }
        if ($landingDesignator !== null) {
            $this->recordPhaseMetric($report->id, 'final_landing', 'LandingRunway', $landingDesignator);
        }
    }

    private function recordPhaseMetric(int $reportId, string $phaseCode, string $metricCode, string $value): void
    {
        $db = \Yii::$app->db;
        if (!isset($this->metricTypeIds[$metricCode])) {
            $phaseTypeId = $db->createCommand('SELECT id FROM flight_phase_type WHERE code = :code', [':code' => $phaseCode])->queryScalar();
            if ($phaseTypeId === false) {
                $db->createCommand()->insert('flight_phase_type', ['code' => $phaseCode])->execute();
                $phaseTypeId = $db->getLastInsertID();
            }
            $db->createCommand()->insert('flight_phase_metric_type', ['flight_phase_type_id' => $phaseTypeId, 'code' => $metricCode])->execute();
            $this->metricTypeIds[$metricCode] = (int) $db->getLastInsertID();
            $this->phaseTypeIds[$metricCode] = (int) $phaseTypeId;
        }

        $db->createCommand()->insert('flight_phase', [
            'flight_report_id' => $reportId,
            'flight_phase_type_id' => $this->phaseTypeIds[$metricCode],
            'start' => '2026-10-01 10:05:00',
            'end' => '2026-10-01 10:06:00',
        ])->execute();
        $db->createCommand()->insert('flight_phase_metric', [
            'flight_phase_id' => $db->getLastInsertID(),
            'metric_type_id' => $this->metricTypeIds[$metricCode],
            'value' => $value,
        ])->execute();
    }

    private function getPilotId(string $location): int
    {
        if ($this->pilotId === null) {
            $pilot = new Pilot([
                'license' => 'ASG001',
                'name' => 'Test',
                'surname' => 'Pilot',
                'email' => 'assignments@test.com',
                'password' => '$2y$10$72JM.DYpddpessTYjHI0kuH/0NKNYeLP.YoU2AZwGY1kHY.Aow0Mu',
                'country_id' => 1,
                'city' => 'Madrid',
                'date_of_birth' => '1990-01-01',
                'location' => $location,
            ]);
            $pilot->save(false);
            $this->pilotId = $pilot->id;
        }
        return $this->pilotId;
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
        // General aviation (below 1000 NM and fewer than 20 seats) never gets assignments
        if ($range < 1000 && $aircraft->aircraftConfiguration->pax_capacity < 20) {
            $this->assertSame([], $assignments, 'Assignments offered to a light aircraft');
        }

        // Window recomputed here independently of the generator
        $average = (float) (new \yii\db\Query())
            ->from(['fr' => 'flight_report'])
            ->innerJoin(['f' => 'flight'], 'fr.flight_id = f.id')
            ->innerJoin(['a' => 'aircraft'], 'f.aircraft_id = a.id')
            ->where(['a.aircraft_configuration_id' => $aircraft->aircraft_configuration_id, 'f.status' => 'F'])
            ->andWhere(['not', ['fr.distance_nm' => null]])
            ->average('fr.distance_nm');
        if (!empty($assignments)) {
            $flights = (int) (new \yii\db\Query())
                ->from(['fr' => 'flight_report'])
                ->innerJoin(['f' => 'flight'], 'fr.flight_id = f.id')
                ->innerJoin(['a' => 'aircraft'], 'f.aircraft_id = a.id')
                ->where(['a.aircraft_configuration_id' => $aircraft->aircraft_configuration_id, 'f.status' => 'F'])
                ->andWhere(['not', ['fr.distance_nm' => null]])
                ->count();
            $this->assertGreaterThanOrEqual(3, $flights, 'Assignments offered to an aircraft flown fewer than 3 times');
        }
        $minNm = $average * 0.5;
        $maxNm = min($average * 1.2, $range * 0.8);

        // Minimum runway recomputed here independently: second shortest takeoff runway of finished flights
        $takeoffLengths = \Yii::$app->db->createCommand(
            "SELECT r.length_m FROM flight_phase_metric m
             JOIN flight_phase_metric_type mt ON mt.id = m.metric_type_id AND mt.code = 'TakeoffRunway'
             JOIN flight_phase fp ON fp.id = m.flight_phase_id
             JOIN flight_report fr ON fr.id = fp.flight_report_id
             JOIN flight f ON f.id = fr.flight_id AND f.status = 'F'
             JOIN aircraft a ON a.id = f.aircraft_id AND a.aircraft_configuration_id = :config
             JOIN runway r ON r.airport_icao = f.departure
             JOIN runway_end re ON re.runway_id = r.id AND re.designator = m.value
             ORDER BY r.length_m",
            [':config' => $aircraft->aircraft_configuration_id]
        )->queryColumn();
        if (!empty($assignments)) {
            $this->assertGreaterThanOrEqual(2, count($takeoffLengths), 'Assignments offered without 2 known takeoff runways');
        }
        $minRunwayM = (float) ($takeoffLengths[1] ?? 0);
        if (!empty($assignments)) {
            $originRunways = array_map(fn($r) => (float) $r->length_m, Runway::find()->where(['airport_icao' => $origin->icao_code])->all());
            $this->assertNotEmpty($originRunways, "Assignments offered from {$origin->icao_code}, which has no runways");
            $this->assertGreaterThanOrEqual($minRunwayM, max($originRunways), "Origin {$origin->icao_code} runways too short");
        }

        $this->assertLessThanOrEqual(AssignmentGenerator::COUNT, count($assignments));

        $pairs = $this->pairsOf($assignments);
        $this->assertSame($pairs, array_values(array_unique($pairs)), 'Duplicated (destination, client) pair');

        // Distinct destinations come first: once a destination repeats, no new destination may follow
        $seen = [];
        $repeating = false;
        foreach (array_column($assignments, 'destination') as $icao) {
            if (isset($seen[$icao])) {
                $repeating = true;
            } else {
                $this->assertFalse($repeating, "New destination $icao after a repeated one");
                $seen[$icao] = true;
            }
        }

        foreach (array_count_values(array_column($assignments, 'destination')) as $icao => $count) {
            $this->assertLessThanOrEqual(AssignmentGenerator::MAX_PER_DESTINATION, $count, "$icao offered $count times");
        }

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
            $longest = max(array_map(fn($r) => (float) $r->length_m, $runways));
            $this->assertGreaterThanOrEqual($minRunwayM, $longest, "$icao only has $longest m of runway, minimum is $minRunwayM m");

            $dest = Airport::findOne(['icao_code' => $icao]);
            $this->assertDoesNotMatchRegularExpression('/Heliport|Helipuerto|Heliporto/i', $dest->name, "Heliport $icao offered");
            $distance = GeoUtils::haversine($origin->latitude, $origin->longitude, $dest->latitude, $dest->longitude, 'nm');
            $this->assertGreaterThanOrEqual($minNm, $distance, "$icao is closer than the distance window");
            $this->assertLessThanOrEqual($maxNm, $distance, "$icao is beyond the distance window");
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
