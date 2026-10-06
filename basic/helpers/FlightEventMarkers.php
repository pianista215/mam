<?php

namespace app\helpers;

use app\models\FlightReport;

/**
 * Detects notable discrete events (flaps, gear, lift-off, touchdown, autopilot)
 * in the ACARS event stream so they can be drawn as markers on the flight map.
 *
 * Each ACARS event usually carries the full set of attributes, so changes are
 * detected by comparing every value against the previous one. The first value
 * seen for an attribute is the initial state and never produces a marker.
 */
class FlightEventMarkers
{
    const TYPE_FLAPS = 'flaps';
    const TYPE_GEAR = 'gear';
    const TYPE_LIFTOFF = 'liftoff';
    const TYPE_TOUCHDOWN = 'touchdown';
    const TYPE_AUTOPILOT = 'autopilot';

    /**
     * Flap changes separated by at most this many seconds are merged into a
     * single marker: the simulator samples the flaps while they are moving.
     */
    const FLAPS_SETTLE_SECONDS = 10;

    /** Max seconds between touchdown and a LandingVSFpm sample to attach it. */
    const TOUCHDOWN_VS_WINDOW_SECONDS = 10;

    /**
     * Flattens the report events (phase order, then event order) and extracts the markers.
     */
    public static function fromReport(FlightReport $report): array
    {
        $events = [];
        foreach ($report->flightPhases as $phase) {
            foreach ($phase->flightEvents as $event) {
                $values = [];
                foreach ($event->flightEventDatas as $data) {
                    $values[$data->attribute0->code] = $data->value;
                }
                $events[] = ['timestamp' => $event->timestamp, 'values' => $values];
            }
        }
        return self::extract($events);
    }

    /**
     * @param array $events list of ['timestamp' => 'Y-m-d H:i:s', 'values' => [code => value]]
     * @return array list of markers ordered by timestamp, each one:
     *   ['type', 'short', 'timestamp', 'lon', 'lat', 'heading', 'altitude', 'from', 'to', 'extra']
     */
    public static function extract(array $events): array
    {
        $markers = [];
        $prev = [];
        $position = null;
        $flapsGroup = null;
        $lastTouchdownIdx = null;

        foreach ($events as $event) {
            $values = $event['values'];
            $ts = $event['timestamp'];
            $time = strtotime($ts);

            $position = self::updatePosition($position, $values);

            // Close a pending flaps group once the flaps have settled
            if ($flapsGroup !== null && $time - $flapsGroup['lastTime'] > self::FLAPS_SETTLE_SECONDS) {
                self::closeFlapsGroup($flapsGroup, $markers);
                $flapsGroup = null;
            }

            if (isset($values['Flaps']) && $values['Flaps'] !== '') {
                $flaps = (int)round((float)$values['Flaps']);
                if (array_key_exists('Flaps', $prev) && $prev['Flaps'] !== $flaps && $position !== null) {
                    if ($flapsGroup === null) {
                        $flapsGroup = [
                            'marker' => self::makeMarker(self::TYPE_FLAPS, $ts, $position, $prev['Flaps'], $flaps),
                            'lastTime' => $time,
                        ];
                    } else {
                        $flapsGroup['marker']['to'] = $flaps;
                        $flapsGroup['lastTime'] = $time;
                    }
                }
                $prev['Flaps'] = $flaps;
            }

            $gear = self::detectChange($prev, $values, 'Gear');
            if ($gear !== null && $position !== null) {
                $markers[] = self::makeMarker(self::TYPE_GEAR, $ts, $position, $gear[0], $gear[1]);
            }

            $ground = self::detectChange($prev, $values, 'onGround');
            if ($ground !== null && $position !== null) {
                if ($ground[1] === 'False') {
                    $markers[] = self::makeMarker(self::TYPE_LIFTOFF, $ts, $position);
                } else {
                    $markers[] = self::makeMarker(self::TYPE_TOUCHDOWN, $ts, $position);
                    $lastTouchdownIdx = count($markers) - 1;
                }
            }

            if (isset($values['LandingVSFpm']) && $values['LandingVSFpm'] !== '' && $lastTouchdownIdx !== null) {
                $touchdown = &$markers[$lastTouchdownIdx];
                if ($touchdown['extra'] === null
                    && $time - strtotime($touchdown['timestamp']) <= self::TOUCHDOWN_VS_WINDOW_SECONDS) {
                    $touchdown['extra'] = (int)$values['LandingVSFpm'];
                }
                unset($touchdown);
            }

            $ap = self::detectChange($prev, $values, 'AP');
            if ($ap !== null && $position !== null) {
                $markers[] = self::makeMarker(self::TYPE_AUTOPILOT, $ts, $position, $ap[0], $ap[1]);
            }
        }

        if ($flapsGroup !== null) {
            self::closeFlapsGroup($flapsGroup, $markers);
        }

        // Flaps groups are emitted when they close, restore chronological order (usort is stable)
        usort($markers, fn($a, $b) => strcmp($a['timestamp'], $b['timestamp']));

        foreach ($markers as &$marker) {
            $marker['short'] = self::shortCode($marker);
        }
        unset($marker);

        return $markers;
    }

    /**
     * Returns [previous, current] when the attribute changed, null otherwise.
     * Always records the current value as the new previous one.
     */
    private static function detectChange(array &$prev, array $values, string $code): ?array
    {
        if (!isset($values[$code]) || $values[$code] === '') {
            return null;
        }
        $current = (string)$values[$code];
        $change = null;
        if (array_key_exists($code, $prev) && $prev[$code] !== $current) {
            $change = [$prev[$code], $current];
        }
        $prev[$code] = $current;
        return $change;
    }

    private static function updatePosition(?array $position, array $values): ?array
    {
        if (isset($values['Latitude'], $values['Longitude'])
            && $values['Latitude'] !== '' && $values['Longitude'] !== '') {
            $position = array_merge($position ?? [], [
                'lat' => (float)$values['Latitude'],
                'lon' => (float)$values['Longitude'],
            ]);
        }
        if ($position === null) {
            return null;
        }
        if (isset($values['Heading']) && $values['Heading'] !== '') {
            $position['heading'] = (float)$values['Heading'];
        }
        if (isset($values['Altitude']) && $values['Altitude'] !== '') {
            $position['altitude'] = (int)$values['Altitude'];
        }
        return $position;
    }

    private static function closeFlapsGroup(array $group, array &$markers): void
    {
        $marker = $group['marker'];
        if ($marker['from'] !== $marker['to']) {
            $markers[] = $marker;
        }
    }

    private static function makeMarker(string $type, string $ts, array $position, $from = null, $to = null): array
    {
        return [
            'type' => $type,
            'short' => null,
            'timestamp' => $ts,
            'lon' => $position['lon'],
            'lat' => $position['lat'],
            'heading' => $position['heading'] ?? null,
            'altitude' => $position['altitude'] ?? null,
            'from' => $from,
            'to' => $to,
            'extra' => null,
        ];
    }

    private static function shortCode(array $marker): string
    {
        switch ($marker['type']) {
            case self::TYPE_FLAPS:
                return 'F' . $marker['to'];
            case self::TYPE_GEAR:
                return $marker['to'] === 'Down' ? 'G↓' : 'G↑';
            case self::TYPE_LIFTOFF:
                return 'LO';
            case self::TYPE_TOUCHDOWN:
                return 'TD';
            case self::TYPE_AUTOPILOT:
                return $marker['to'] === 'On' ? 'AP ON' : 'AP OFF';
        }
        return '?';
    }
}
