<?php

namespace app\helpers;

use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\Airport;
use app\models\Client;
use app\models\ClientHub;
use app\models\Flight;
use app\models\Route;
use yii\db\Query;

/**
 * Proposes client assignments ("encargos") departing from the aircraft's current airport.
 *
 * Who gets assignments
 * - Not general aviation: light aircraft (range below LIGHT_AIRCRAFT_MAX_RANGE_NM and fewer than
 *   LIGHT_AIRCRAFT_MAX_PAX seats, e.g. a Maule or a C172) never get assignments.
 * - The configuration needs MIN_FLIGHTS finished flights with distance and MIN_TAKEOFFS known takeoff runways.
 *
 * Learnt from the configuration's finished flights
 * - Distance window: from MIN_DISTANCE_FACTOR to MAX_DISTANCE_FACTOR of the average flown distance, so assignments
 *   look like its usual flights. The maximum is never below RANGE_FLOOR_FACTOR of the range (so an aircraft only
 *   flown on short legs can still go somewhat further) nor above RANGE_CAP_FACTOR of the range.
 * - Minimum runway: the second shortest runway it has taken off from (the shortest is ignored in case of a one-off
 *   feat). Takeoffs rather than landings, so the aircraft is never sent somewhere it couldn't leave.
 *
 * Eligible airports: not a heliport and with a runway at least as long as the minimum runway. The origin must be
 * eligible too, otherwise there are no assignments. Destinations must also be inside the window.
 *
 * Candidates
 * - Direct mode (some client has its headquarters or a hub at the origin): each of those clients offers up to
 *   CLIENT_SLOTS assignments, its own eligible airports first. Remaining slots are invented on its behalf, preferring
 *   the countries where it has its headquarters or hubs; military airports are only invented when the client would
 *   otherwise offer nothing (they are still offered as a client's own airport).
 * - Reverse mode (no client at the origin): each client offers up to CLIENT_SLOTS of its own eligible airports, and
 *   nothing is invented.
 *
 * Selection: destinations not yet used by another client are preferred; COUNT pairs are picked with distinct
 * destinations first, and a destination only repeats (for another client) to complete the offer, never more than
 * MAX_PER_DESTINATION times. When a regular route up to the window maximum departs from the origin, each assignment
 * independently survives with ACCEPT_PERCENT probability.
 *
 * Dead-end safety: the origin is always eligible and eligibility is symmetric (same distance and same aircraft both
 * ways), so the previous airport P is always eligible from the destination D:
 * - D reached as a client airport: D has clients, so direct mode fills every slot and P is a valid filler (the
 *   country preference never leaves a slot empty while an eligible airport remains, and military airports are used
 *   when nothing else is available).
 * - D reached as an invented destination: those only come from direct mode, so P is a client airport; if D has no
 *   clients, reverse mode offers P.
 * - Reverse mode only lands on client airports, so two client-less airports are never chained.
 * - Without flyable routes at D the lottery is skipped, so the pilot always gets at least one assignment.
 * Not covered: clients or hubs removed afterwards, switching to another aircraft, or the window and minimum runway
 * shifting after new flights; charter remains the fallback.
 */
class AssignmentGenerator
{
    const COUNT = 5;
    const CLIENT_SLOTS = 3;
    const MAX_PER_DESTINATION = 2;
    const ACCEPT_PERCENT = 70;

    const MIN_FLIGHTS = 3;
    const MIN_TAKEOFFS = 2; // the minimum runway is the second shortest takeoff runway

    const MIN_DISTANCE_FACTOR = 0.5; // of the average flown distance
    const MAX_DISTANCE_FACTOR = 1.2; // of the average flown distance
    const RANGE_FLOOR_FACTOR = 0.2;  // the window maximum is at least this share of the range...
    const RANGE_CAP_FACTOR = 0.8;    // ...and at most this one

    // Light aircraft (below both limits) are general aviation and get no assignments
    const LIGHT_AIRCRAFT_MAX_RANGE_NM = 1000;
    const LIGHT_AIRCRAFT_MAX_PAX = 20;

    // Airport names matching these are excluded (heliports) or avoided as invented destinations (military airports)
    const HELIPORT_NAME_PATTERNS = ['%Heliport%', '%Helipuerto%', '%Heliporto%'];
    const MILITARY_NAME_REGEX = '/\b(Air Base|Air Force|Naval|Army|RAF|AFB|Base A[ée]rea)\b|\(BA \d+\)/iu';

    /**
     * @param Aircraft $aircraft Last aircraft flown by the pilot; its location is the origin
     * @param callable|null $rand fn(int $min, int $max): int, defaults to mt_rand (injectable for tests)
     * @return array<int, array{destination: string, client_id: int, distance_nm: int}>
     */
    public static function generate(Aircraft $aircraft, ?callable $rand = null): array
    {
        $rand = $rand ?? 'mt_rand';

        $origin = $aircraft->location0;
        $config = $aircraft->aircraftConfiguration;
        if ($origin === null || $config === null || self::isLightAircraft($config)) {
            return [];
        }

        $window = self::distanceWindow($aircraft);
        $minRunwayM = self::minTakeoffRunway($config->id);
        if ($window === null || $minRunwayM === null || !self::isSuitableAirport($origin->icao_code, $minRunwayM)) {
            return [];
        }
        [$minNm, $maxNm] = $window;

        $eligible = self::findEligibleAirports($origin, $minNm, $maxNm, $minRunwayM);
        if (empty($eligible)) {
            return [];
        }

        $relatedClientIds = self::findRelatedClientIds($origin->icao_code);
        $pool = empty($relatedClientIds)
            ? self::reverseCandidates($eligible, $rand)
            : self::directCandidates($relatedClientIds, $eligible, $rand);

        $assignments = array_map(fn($pair) => [
            'destination' => $pair[0],
            'client_id' => $pair[1],
            'distance_nm' => (int) round($eligible[$pair[0]]['distance']),
        ], self::pickDistinctDestinations($pool, self::COUNT, $rand));

        if (!self::hasFlyableRoutes($origin->icao_code, $maxNm)) {
            return $assignments;
        }

        return array_values(array_filter(
            $assignments,
            fn() => $rand(1, 100) <= self::ACCEPT_PERCENT
        ));
    }

    /**
     * Distance window for the aircraft's assignments, based on the usual flights of its configuration.
     * @return array{0: float, 1: float}|null [min NM, max NM], or null if there is no range or not enough flight history
     */
    public static function distanceWindow(Aircraft $aircraft): ?array
    {
        $range = $aircraft->aircraftConfiguration->aircraftType->max_nm_range ?? null;
        if (empty($range)) {
            return null;
        }

        $average = self::averageFlownDistance($aircraft->aircraft_configuration_id);
        if ($average === null) {
            return null;
        }

        $maxNm = max($average * self::MAX_DISTANCE_FACTOR, $range * self::RANGE_FLOOR_FACTOR);
        return [
            $average * self::MIN_DISTANCE_FACTOR,
            min($maxNm, $range * self::RANGE_CAP_FACTOR),
        ];
    }

    /**
     * Average distance of the finished flights of an aircraft configuration.
     * @return float|null Null when the configuration has fewer than MIN_FLIGHTS finished flights with distance
     */
    public static function averageFlownDistance(int $aircraftConfigurationId): ?float
    {
        $stats = (new Query())
            ->select(['flights' => 'COUNT(*)', 'average' => 'AVG(fr.distance_nm)'])
            ->from(['fr' => 'flight_report'])
            ->innerJoin(['f' => 'flight'], 'fr.flight_id = f.id')
            ->innerJoin(['a' => 'aircraft'], 'f.aircraft_id = a.id')
            ->where([
                'a.aircraft_configuration_id' => $aircraftConfigurationId,
                'f.status' => Flight::STATUS_FINISHED,
            ])
            ->andWhere(['not', ['fr.distance_nm' => null]])
            ->one();

        if ((int) $stats['flights'] < self::MIN_FLIGHTS) {
            return null;
        }
        return (float) $stats['average'];
    }

    /**
     * Minimum runway of an aircraft configuration: the second shortest runway its finished flights took off from,
     * resolved from the TakeoffRunway phase metric against the runway ends of the departure airport.
     * @return float|null Length in metres, or null with fewer than MIN_TAKEOFFS known takeoff runways
     */
    public static function minTakeoffRunway(int $aircraftConfigurationId): ?float
    {
        $length = (new Query())
            ->select('r.length_m')
            ->from(['m' => 'flight_phase_metric'])
            ->innerJoin(['mt' => 'flight_phase_metric_type'], 'mt.id = m.metric_type_id')
            ->innerJoin(['fp' => 'flight_phase'], 'fp.id = m.flight_phase_id')
            ->innerJoin(['fr' => 'flight_report'], 'fr.id = fp.flight_report_id')
            ->innerJoin(['f' => 'flight'], 'f.id = fr.flight_id')
            ->innerJoin(['a' => 'aircraft'], 'a.id = f.aircraft_id')
            ->innerJoin(['r' => 'runway'], 'r.airport_icao = f.departure')
            ->innerJoin(['re' => 'runway_end'], 're.runway_id = r.id AND re.designator = m.value')
            ->where([
                'mt.code' => 'TakeoffRunway',
                'f.status' => Flight::STATUS_FINISHED,
                'a.aircraft_configuration_id' => $aircraftConfigurationId,
            ])
            ->orderBy(['r.length_m' => SORT_ASC])
            ->offset(self::MIN_TAKEOFFS - 1)
            ->limit(1)
            ->scalar();

        return $length === false ? null : (float) $length;
    }

    /**
     * Light (general aviation) aircraft: range below LIGHT_AIRCRAFT_MAX_RANGE_NM and fewer than LIGHT_AIRCRAFT_MAX_PAX
     * seats. They get no assignments.
     */
    public static function isLightAircraft(AircraftConfiguration $config): bool
    {
        return $config->aircraftType->max_nm_range < self::LIGHT_AIRCRAFT_MAX_RANGE_NM
            && $config->pax_capacity < self::LIGHT_AIRCRAFT_MAX_PAX;
    }

    /**
     * Whether the aircraft can operate at the airport: not a heliport, with a runway at least $minRunwayM long.
     */
    public static function isSuitableAirport(string $icao, float $minRunwayM): bool
    {
        $query = (new Query())->from('airport')->where(['icao_code' => $icao]);
        self::applyAirportRules($query, $minRunwayM);
        return $query->exists();
    }

    /**
     * Whether a regular route departs from the airport within the given distance.
     */
    public static function hasFlyableRoutes(string $icao, float $maxNm): bool
    {
        return Route::find()
            ->where(['departure' => $icao])
            ->andWhere(['<=', 'distance_nm', $maxNm])
            ->exists();
    }

    /**
     * Restricts an airport query to airports the aircraft can operate at (see isSuitableAirport()).
     */
    private static function applyAirportRules(Query $query, float $minRunwayM): void
    {
        $longEnoughRunway = (new Query())
            ->from('runway')
            ->where('runway.airport_icao = airport.icao_code')
            ->andWhere(['>=', 'length_m', $minRunwayM]);
        $query->andWhere(['exists', $longEnoughRunway]);

        foreach (self::HELIPORT_NAME_PATTERNS as $pattern) {
            $query->andWhere(['not like', 'airport.name', $pattern, false]);
        }
    }

    /**
     * Airports that can be offered as destination, with their distance from the origin, country and military flag.
     * @return array<string, array{distance: float, country_id: int, military: bool}> keyed by icao, sorted by icao
     */
    private static function findEligibleAirports(Airport $origin, float $minNm, float $maxNm, float $minRunwayM): array
    {
        // One degree of latitude is slightly more than 60 NM, so this box never cuts valid airports
        $latDelta = $maxNm / 60;

        $query = (new Query())
            ->select(['icao_code', 'name', 'latitude', 'longitude', 'country_id'])
            ->from('airport')
            ->where(['<>', 'icao_code', $origin->icao_code])
            ->andWhere(['between', 'latitude', $origin->latitude - $latDelta, $origin->latitude + $latDelta])
            ->orderBy(['icao_code' => SORT_ASC]);
        self::applyAirportRules($query, $minRunwayM);

        $eligible = [];
        foreach ($query->all() as $row) {
            $distance = GeoUtils::haversine(
                $origin->latitude, $origin->longitude,
                (float) $row['latitude'], (float) $row['longitude'],
                'nm'
            );
            if ($distance >= $minNm && $distance <= $maxNm) {
                $eligible[$row['icao_code']] = [
                    'distance' => $distance,
                    'country_id' => (int) $row['country_id'],
                    'military' => (bool) preg_match(self::MILITARY_NAME_REGEX, $row['name']),
                ];
            }
        }
        return $eligible;
    }

    /**
     * Ids of the clients with headquarters or a hub at the airport.
     * @return int[]
     */
    private static function findRelatedClientIds(string $icao): array
    {
        return array_values(array_unique(array_merge(
            Client::find()->select('id')->where(['headquarters_icao' => $icao])->column(),
            ClientHub::find()->select('client_id')->where(['airport_icao' => $icao])->column()
        )));
    }

    /**
     * Direct mode: up to CLIENT_SLOTS pairs per related client, own eligible airports first and invented ones for the
     * remaining slots. Own airports are picked for every client before inventing, so inventions avoid them.
     * @return array<int, array{0: string, 1: int}> [destination, client_id] pairs
     */
    private static function directCandidates(array $clientIds, array $eligible, callable $rand): array
    {
        $clients = self::loadClients($clientIds);
        $used = [];

        $own = [];
        foreach ($clients as $client) {
            $own[$client->id] = self::pickPreferringUnused(self::ownEligibleAirports($client, $eligible), $used, self::CLIENT_SLOTS, $rand);
        }

        $pairs = [];
        foreach ($clients as $client) {
            $slots = self::CLIENT_SLOTS - count($own[$client->id]);
            $invented = self::inventDestinations($client, $own[$client->id], $eligible, $used, $slots, $rand);
            foreach (array_merge($own[$client->id], $invented) as $icao) {
                $pairs[] = [$icao, (int) $client->id];
            }
        }
        return $pairs;
    }

    /**
     * Invents up to $slots destinations for the client among the eligible airports that aren't its own: civil
     * airports in the client's countries first, then civil airports elsewhere; military airports only when the client
     * would otherwise offer nothing.
     * @param string[] $own The client's own airports already picked
     * @param array<string, bool> $used Destinations already taken by other candidates (updated)
     * @return string[]
     */
    private static function inventDestinations(Client $client, array $own, array $eligible, array &$used, int $slots, callable $rand): array
    {
        $countries = self::clientCountries($client);
        $tiers = ['civil' => ['home' => [], 'abroad' => []], 'military' => ['home' => [], 'abroad' => []]];
        foreach (array_diff(array_keys($eligible), $own) as $icao) {
            $kind = $eligible[$icao]['military'] ? 'military' : 'civil';
            $where = in_array($eligible[$icao]['country_id'], $countries, true) ? 'home' : 'abroad';
            $tiers[$kind][$where][] = $icao;
        }

        $invented = self::pickFromTiers(array_values($tiers['civil']), $used, $slots, $rand);
        if (empty($own) && empty($invented)) {
            $invented = self::pickFromTiers(array_values($tiers['military']), $used, $slots, $rand);
        }
        return $invented;
    }

    /**
     * Reverse mode: up to CLIENT_SLOTS pairs per client, only to its own eligible airports.
     * @return array<int, array{0: string, 1: int}> [destination, client_id] pairs
     */
    private static function reverseCandidates(array $eligible, callable $rand): array
    {
        $used = [];
        $pairs = [];
        foreach (self::loadClients() as $client) {
            foreach (self::pickPreferringUnused(self::ownEligibleAirports($client, $eligible), $used, self::CLIENT_SLOTS, $rand) as $icao) {
                $pairs[] = [$icao, (int) $client->id];
            }
        }
        return $pairs;
    }

    /**
     * @param int[]|null $ids Null loads every client
     * @return Client[]
     */
    private static function loadClients(?array $ids = null): array
    {
        return Client::find()
            ->andFilterWhere(['id' => $ids])
            ->with(['headquarters', 'hubs'])
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    /**
     * Headquarters and hubs of the client that are eligible destinations, sorted by icao.
     * @return string[]
     */
    private static function ownEligibleAirports(Client $client, array $eligible): array
    {
        $icaos = array_merge(
            [$client->headquarters_icao],
            array_map(fn($hub) => $hub->icao_code, $client->hubs)
        );
        sort($icaos);
        return array_values(array_filter($icaos, fn($icao) => isset($eligible[$icao])));
    }

    /**
     * Countries of all the client's airports (headquarters and hubs, eligible or not).
     * @return int[]
     */
    private static function clientCountries(Client $client): array
    {
        $airports = array_filter(array_merge([$client->headquarters], $client->hubs));
        return array_values(array_unique(array_map(fn($airport) => (int) $airport->country_id, $airports)));
    }

    /**
     * Picks up to $count airports from the tiers in order, only moving to the next tier when the previous one runs out.
     * @param string[][] $tiers
     * @param array<string, bool> $used Destinations already taken by other candidates (updated)
     * @return string[]
     */
    private static function pickFromTiers(array $tiers, array &$used, int $count, callable $rand): array
    {
        $picked = [];
        foreach ($tiers as $tier) {
            $picked = array_merge($picked, self::pickPreferringUnused($tier, $used, $count - count($picked), $rand));
        }
        return $picked;
    }

    /**
     * Picks up to $count random airports, preferring those not used yet, and marks them as used.
     * @param array<string, bool> $used Destinations already taken by other candidates (updated)
     * @return string[]
     */
    private static function pickPreferringUnused(array $icaos, array &$used, int $count, callable $rand): array
    {
        $unused = array_values(array_filter($icaos, fn($icao) => !isset($used[$icao])));
        $alreadyUsed = array_values(array_diff($icaos, $unused));

        $picked = self::pickRandom($unused, $count, $rand);
        $picked = array_merge($picked, self::pickRandom($alreadyUsed, $count - count($picked), $rand));
        foreach ($picked as $icao) {
            $used[$icao] = true;
        }
        return $picked;
    }

    /**
     * Picks up to $count random pairs, with distinct destinations first; repeated destinations only complete the
     * offer, up to MAX_PER_DESTINATION times each.
     * @param array<int, array{0: string, 1: int}> $pool
     * @return array<int, array{0: string, 1: int}>
     */
    private static function pickDistinctDestinations(array $pool, int $count, callable $rand): array
    {
        $distinct = [];
        $repeated = [];
        foreach (self::pickRandom($pool, count($pool), $rand) as $pair) {
            if (count($distinct) < $count && !isset($distinct[$pair[0]])) {
                $distinct[$pair[0]] = $pair;
            } else {
                $repeated[] = $pair;
            }
        }

        // Repeats are only needed when there were fewer distinct destinations than $count, so every repeated
        // destination is already among the distinct ones
        $picked = array_values($distinct);
        $timesPicked = array_fill_keys(array_keys($distinct), 1);
        foreach ($repeated as $pair) {
            if (count($picked) >= $count) {
                break;
            }
            if ($timesPicked[$pair[0]] < self::MAX_PER_DESTINATION) {
                $timesPicked[$pair[0]]++;
                $picked[] = $pair;
            }
        }
        return $picked;
    }

    /**
     * Picks up to $count random items (partial Fisher–Yates using the injected random source).
     */
    private static function pickRandom(array $items, int $count, callable $rand): array
    {
        $items = array_values($items);
        $count = min(max($count, 0), count($items));
        for ($i = 0; $i < $count; $i++) {
            $j = $rand($i, count($items) - 1);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }
        return array_slice($items, 0, $count);
    }
}
