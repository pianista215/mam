<?php

namespace app\helpers;

use app\models\Aircraft;
use app\models\Airport;
use app\models\Client;
use app\models\ClientHub;
use app\models\Route;
use yii\db\Query;

/**
 * Proposes client assignments ("encargos") departing from the aircraft's current airport.
 *
 * Only eligible airports are ever offered: different from the origin, with at least one runway, within
 * RANGE_FACTOR of the aircraft range and, for small aircraft (range < SMALL_AIRCRAFT_MAX_RANGE_NM), without
 * any runway longer than LARGE_RUNWAY_MIN_LENGTH_M.
 *
 * Direct mode (some client has its headquarters or a hub at the origin): each of those clients gets up to
 * CLIENT_SLOTS assignments to its own eligible airports; remaining slots are filled with random eligible
 * airports on behalf of the same client. Reverse mode (no client at the origin): each client offers up to
 * CLIENT_SLOTS of its own eligible airports, without fillers. In both modes COUNT assignments are picked from
 * the pool and, when a regular route within range departs from the origin, each one survives with
 * ACCEPT_PERCENT probability.
 *
 * Dead-end safety, assuming the aircraft is only ever at an airport it can operate (with runways and, for small
 * aircraft, no runway longer than the limit), so the previous airport P is always eligible from the destination D:
 * - D reached as a client airport: D has clients, so direct mode fills every slot and P is a valid filler.
 * - D reached as a filler: fillers only come from direct mode, so P is a client airport; if D has no clients,
 *   reverse mode offers P.
 * - Reverse mode only lands on client airports, so two client-less airports are never chained.
 * - Without flyable routes at D the lottery is skipped, so the pilot always gets at least one assignment.
 * Not covered: clients or hubs removed afterwards, or switching to another aircraft; charter remains the fallback.
 */
class AssignmentGenerator
{
    const COUNT = 5;
    const CLIENT_SLOTS = 3;
    const RANGE_FACTOR = 0.8;
    const ACCEPT_PERCENT = 70;
    const SMALL_AIRCRAFT_MAX_RANGE_NM = 1000;
    const LARGE_RUNWAY_MIN_LENGTH_M = 3000;

    /**
     * @param Aircraft $aircraft Last aircraft flown by the pilot; its location is the origin
     * @param callable|null $rand fn(int $min, int $max): int, defaults to mt_rand (injectable for tests)
     * @return array<int, array{destination: string, client_id: int, distance_nm: int}>
     */
    public static function generate(Aircraft $aircraft, ?callable $rand = null): array
    {
        $rand = $rand ?? 'mt_rand';

        $origin = $aircraft->location0;
        $range = $aircraft->aircraftConfiguration->aircraftType->max_nm_range ?? null;
        if ($origin === null || empty($range)) {
            return [];
        }
        $maxNm = $range * self::RANGE_FACTOR;
        $isSmallAircraft = $range < self::SMALL_AIRCRAFT_MAX_RANGE_NM;

        $eligible = self::findEligibleAirports($origin, $maxNm, $isSmallAircraft);
        if (empty($eligible)) {
            return [];
        }

        $relatedClientIds = self::findRelatedClientIds($origin->icao_code);
        $pool = empty($relatedClientIds)
            ? self::reverseCandidates($eligible, $rand)
            : self::directCandidates($relatedClientIds, $eligible, $rand);

        $picked = array_map(fn($pair) => [
            'destination' => $pair[0],
            'client_id' => $pair[1],
            'distance_nm' => (int) round($eligible[$pair[0]]),
        ], self::pickRandom($pool, self::COUNT, $rand));

        if (!self::hasFlyableRoutes($origin->icao_code, $maxNm)) {
            return $picked;
        }

        return array_values(array_filter(
            $picked,
            fn() => $rand(1, 100) <= self::ACCEPT_PERCENT
        ));
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
     * Airports that can be offered as destination, with their distance from the origin.
     * @return array<string, float> icao => distance in NM, sorted by icao
     */
    private static function findEligibleAirports(Airport $origin, float $maxNm, bool $isSmallAircraft): array
    {
        // One degree of latitude is slightly more than 60 NM, so this box never cuts valid airports
        $latDelta = $maxNm / 60;
        $runways = (new Query())->from('runway')->where('runway.airport_icao = airport.icao_code');

        $query = (new Query())
            ->select(['icao_code', 'latitude', 'longitude'])
            ->from('airport')
            ->where(['<>', 'icao_code', $origin->icao_code])
            ->andWhere(['between', 'latitude', $origin->latitude - $latDelta, $origin->latitude + $latDelta])
            ->andWhere(['exists', $runways])
            ->orderBy(['icao_code' => SORT_ASC]);

        if ($isSmallAircraft) {
            $query->andWhere(['not exists', (clone $runways)->andWhere(['>', 'length_m', self::LARGE_RUNWAY_MIN_LENGTH_M])]);
        }

        $eligible = [];
        foreach ($query->all() as $row) {
            $distance = GeoUtils::haversine(
                $origin->latitude, $origin->longitude,
                (float) $row['latitude'], (float) $row['longitude'],
                'nm'
            );
            if ($distance <= $maxNm) {
                $eligible[$row['icao_code']] = $distance;
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
     * Up to CLIENT_SLOTS pairs per related client: own eligible airports first, random eligible fillers for the rest.
     * @return array<int, array{0: string, 1: int}> [destination, client_id] pairs
     */
    private static function directCandidates(array $clientIds, array $eligible, callable $rand): array
    {
        $eligibleIcaos = array_keys($eligible);
        $pairs = [];
        foreach (self::loadClients($clientIds) as $client) {
            $own = self::pickRandom(self::ownEligibleAirports($client, $eligible), self::CLIENT_SLOTS, $rand);
            $fillers = self::pickRandom(
                array_values(array_diff($eligibleIcaos, $own)),
                self::CLIENT_SLOTS - count($own),
                $rand
            );
            foreach (array_merge($own, $fillers) as $icao) {
                $pairs[] = [$icao, (int) $client->id];
            }
        }
        return $pairs;
    }

    /**
     * Up to CLIENT_SLOTS pairs per client, only to its own eligible airports.
     * @return array<int, array{0: string, 1: int}> [destination, client_id] pairs
     */
    private static function reverseCandidates(array $eligible, callable $rand): array
    {
        $pairs = [];
        foreach (self::loadClients() as $client) {
            foreach (self::pickRandom(self::ownEligibleAirports($client, $eligible), self::CLIENT_SLOTS, $rand) as $icao) {
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
            ->with('clientHubs')
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    /**
     * Headquarters and hubs of the client that are eligible destinations.
     * @return string[]
     */
    private static function ownEligibleAirports(Client $client, array $eligible): array
    {
        $icaos = array_merge(
            [$client->headquarters_icao],
            array_map(fn($hub) => $hub->airport_icao, $client->clientHubs)
        );
        sort($icaos);
        return array_values(array_filter($icaos, fn($icao) => isset($eligible[$icao])));
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
