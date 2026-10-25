<?php

namespace app\helpers;

use app\models\Aircraft;
use app\models\Client;
use app\models\ClientHub;
use app\models\Route;

/**
 * Proposes client assignments ("encargos") departing from the aircraft's current airport.
 *
 * Candidates are (destination, client) pairs: every headquarters or hub of a client related to the
 * origin (as headquarters or hub), excluding the origin itself, within RANGE_FACTOR of the aircraft range.
 * The same destination may be offered by several clients sharing it.
 *
 * Dead-end safety: any offered destination D is related to a client C that is also related to the origin O,
 * and dist(D, O) = dist(O, D), so generating again from D with the same aircraft always has O as a candidate.
 * When D has no flyable regular routes the acceptance lottery is skipped, so the pilot always gets at least
 * one assignment there. This guarantee does not hold if client hubs change afterwards or a shorter range
 * aircraft is used; charter flights remain the fallback in those cases.
 */
class AssignmentGenerator
{
    const COUNT = 5;
    const RANGE_FACTOR = 0.8;
    const ACCEPT_PERCENT = 70;

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

        $picked = array_slice(self::shuffle(self::findCandidates($origin, $maxNm), $rand), 0, self::COUNT);

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
     * @param \app\models\Airport $origin
     * @return array<int, array{destination: string, client_id: int, distance_nm: int}>
     */
    private static function findCandidates($origin, float $maxNm): array
    {
        $clientIds = array_unique(array_merge(
            Client::find()->select('id')->where(['headquarters_icao' => $origin->icao_code])->column(),
            ClientHub::find()->select('client_id')->where(['airport_icao' => $origin->icao_code])->column()
        ));
        if (empty($clientIds)) {
            return [];
        }

        $clients = Client::find()
            ->where(['id' => $clientIds])
            ->with(['headquarters', 'hubs'])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $candidates = [];
        foreach ($clients as $client) {
            $airports = array_merge([$client->headquarters], $client->hubs);
            foreach ($airports as $airport) {
                if ($airport === null || $airport->icao_code === $origin->icao_code) {
                    continue;
                }
                $distance = GeoUtils::haversine(
                    $origin->latitude, $origin->longitude,
                    $airport->latitude, $airport->longitude,
                    'nm'
                );
                if ($distance <= $maxNm) {
                    $candidates[] = [
                        'destination' => $airport->icao_code,
                        'client_id' => (int) $client->id,
                        'distance_nm' => (int) round($distance),
                    ];
                }
            }
        }
        return $candidates;
    }

    /**
     * Fisher–Yates shuffle using the injected random source.
     */
    private static function shuffle(array $items, callable $rand): array
    {
        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = $rand(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }
        return $items;
    }
}
