<?php

namespace app\helpers;

use app\models\Flight;
use app\models\FlightReport;
use Yii;
use yii\db\Query;

/**
 * Builds a KML document from the ACARS black box so the flight can be replayed in Google Earth.
 *
 * Output: 3D track coloured by phase (one line per phase, extruded to the ground), a clickable dot for every ACARS event with all its values, event
 * markers (flaps, gear, lift-off, touchdown, AP), detected issues and the departure/arrival
 * airports.
 *
 * Heights are written relative to Google's terrain using the simulator AGL: where both terrains
 * match it equals the recorded altitude, and near the ground nothing is hidden under Google's
 * terrain or floats over it. Using a single altitude mode keeps every phase a single connected
 * line, which also gives a continuous elevation profile.
 *
 * Only plain KML 2.2 is used (no gx:Track, no TimeStamp) so the file also works in
 * Google Earth web, which doesn't support those elements.
 */
class KmlGenerator
{
    const FEET_TO_METERS = 0.3048;

    /** Same palette used by the flight map (views/flight/_map_altitude.php), in #rrggbb. */
    const PHASE_COLORS = [
        'startup' => '#00ccff',
        'taxi' => '#ffff00',
        'takeoff' => '#00ff00',
        'cruise' => '#0000ff',
        'touch_go' => '#800080',
        'approach' => '#ff0000',
        'final_landing' => '#ff9900',
        'shutdown' => '#cc00cc',
        'unknown' => '#888888',
        'backtrack' => '#a52a2a',
    ];

    /** Attributes shown in the event dot balloon, in this order; unknown ones are appended. */
    const POINT_DATA = [
        'Altitude' => 'Altitude (ft)',
        'AGLAltitude' => 'Altitude AGL (ft)',
        'IASKnots' => 'IAS (kt)',
        'GSKnots' => 'GS (kt)',
        'VSFpm' => 'VS (fpm)',
        'LandingVSFpm' => 'Landing VS (fpm)',
        'Heading' => 'Heading',
        'onGround' => 'On ground',
        'Flaps' => 'Flaps',
        'Gear' => 'Gear',
        'AP' => 'Autopilot',
        'FuelKg' => 'Fuel (kg)',
        'Squawk' => 'Squawk',
        'Altimeter' => 'Altimeter',
        'QNHSet' => 'QNH set',
    ];

    /** Attributes not repeated in the balloon (already shown as position). */
    const POINT_DATA_SKIP = ['Latitude', 'Longitude'];

    /** Same colours as the flight map event markers; order = priority when several share a point. */
    const MARKER_COLORS = [
        FlightEventMarkers::TYPE_TOUCHDOWN => '#b91c1c',
        FlightEventMarkers::TYPE_LIFTOFF => '#15803d',
        FlightEventMarkers::TYPE_GEAR => '#6b21a8',
        FlightEventMarkers::TYPE_FLAPS => '#1e3a8a',
        FlightEventMarkers::TYPE_AUTOPILOT => '#0f766e',
    ];

    /** Key events: bigger white dot and label. */
    const KEY_MARKERS = [FlightEventMarkers::TYPE_LIFTOFF, FlightEventMarkers::TYPE_TOUCHDOWN];

    const DOT_ICON = 'https://maps.google.com/mapfiles/kml/shapes/placemark_circle.png';
    const ISSUE_ICON = 'https://maps.google.com/mapfiles/kml/shapes/caution.png';
    const ISSUE_COLOR = '#f59e0b';
    const ISSUE_TEXT_COLOR = '#b91c1c';

    const AIRPORT_ICON = 'https://maps.google.com/mapfiles/kml/shapes/flag.png';

    /**
     * Loads every event of the report with a single query (avoids the N+1 of lazy relations).
     *
     * @return array list of ['timestamp', 'phase' => phase type code, 'phase_id', 'values' => [code => value]]
     *   ordered by phase start, then event timestamp
     */
    public static function loadEvents(FlightReport $report): array
    {
        $rows = (new Query())
            ->select([
                'event_id' => 'fe.id',
                'timestamp' => 'fe.timestamp',
                'phase_id' => 'fp.id',
                'phase' => 'fpt.code',
                'code' => 'fea.code',
                'value' => 'fed.value',
            ])
            ->from(['fe' => 'flight_event'])
            ->innerJoin(['fp' => 'flight_phase'], 'fp.id = fe.phase_id')
            ->innerJoin(['fpt' => 'flight_phase_type'], 'fpt.id = fp.flight_phase_type_id')
            ->innerJoin(['fed' => 'flight_event_data'], 'fed.event_id = fe.id')
            ->innerJoin(['fea' => 'flight_event_attribute'], 'fea.id = fed.attribute_id')
            ->where(['fp.flight_report_id' => $report->id])
            ->orderBy(['fp.start' => SORT_ASC, 'fp.id' => SORT_ASC, 'fe.timestamp' => SORT_ASC, 'fe.id' => SORT_ASC])
            ->each(5000);

        $events = [];
        $index = [];
        foreach ($rows as $row) {
            $id = $row['event_id'];
            if (!isset($index[$id])) {
                $index[$id] = count($events);
                $events[] = [
                    'timestamp' => $row['timestamp'],
                    'phase' => $row['phase'],
                    'phase_id' => (int)$row['phase_id'],
                    'values' => [],
                ];
            }
            $events[$index[$id]]['values'][$row['code']] = $row['value'];
        }
        return $events;
    }

    /**
     * Builds the KML document.
     *
     * @param Flight $flight
     * @param array $events as returned by loadEvents()
     * @param array $markers as returned by FlightEventMarkers::extract()
     * @param array $phaseNames phase type code => translated name
     * @param array $issues list of ['timestamp', 'description', 'penalty']
     * @return string|null KML, or null when no event has a position
     */
    public static function generate(Flight $flight, array $events, array $markers, array $phaseNames, array $issues = []): ?string
    {
        $points = self::buildPoints($events);
        if (empty($points)) {
            return null;
        }
        $points = self::attachMarkers($points, $markers);
        $points = self::attachIssues($points, $issues);

        $w = new \XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        $w->startDocument('1.0', 'UTF-8');
        $w->startElement('kml');
        $w->writeAttribute('xmlns', 'http://www.opengis.net/kml/2.2');
        $w->startElement('Document');
        $w->writeElement('name', $flight->title);
        $w->writeElement('open', '1');
        $w->startElement('description');
        $w->writeCdata(self::flightDescription($flight));
        $w->endElement();

        self::writeLookAt($w, $points);
        self::writeStyles($w);

        self::writePhaseLines($w, $points, $phaseNames);
        self::writeIssues($w, $points, $phaseNames);
        self::writeEventMarkers($w, $points, $phaseNames);
        self::writeEventDots($w, $points, $phaseNames);
        self::writeAirports($w, $flight);

        $w->endElement(); // Document
        $w->endElement(); // kml
        $w->endDocument();
        return $w->outputMemory();
    }

    /**
     * Keeps the events with a position and computes their height above the terrain in meters.
     *
     * The height is the simulator AGL (0 on ground). When AGL is missing it is derived from the
     * altitude and the last known terrain elevation (Altitude - AGL); if no terrain elevation has
     * been seen yet, the altitude itself is used.
     */
    private static function buildPoints(array $events): array
    {
        $points = [];
        $lastAltitudeFt = null;
        $terrainFt = null;
        foreach ($events as $event) {
            $v = $event['values'];
            if (!isset($v['Latitude'], $v['Longitude']) || $v['Latitude'] === '' || $v['Longitude'] === '') {
                continue;
            }
            if (isset($v['Altitude']) && $v['Altitude'] !== '') {
                $lastAltitudeFt = (float)$v['Altitude'];
            }
            $onGround = ($v['onGround'] ?? '') === 'True';
            if (isset($v['AGLAltitude']) && $v['AGLAltitude'] !== '') {
                $aglFt = (float)$v['AGLAltitude'];
                if ($lastAltitudeFt !== null) {
                    $terrainFt = $lastAltitudeFt - $aglFt;
                }
            } elseif ($lastAltitudeFt !== null) {
                $aglFt = $lastAltitudeFt - ($terrainFt ?? 0);
            } else {
                $aglFt = 0.0;
            }
            if ($onGround) {
                $aglFt = 0.0;
            }
            $points[] = [
                'timestamp' => $event['timestamp'],
                'phase' => $event['phase'],
                'phase_id' => $event['phase_id'],
                'lat' => (float)$v['Latitude'],
                'lon' => (float)$v['Longitude'],
                'agl' => round(max($aglFt, 0) * self::FEET_TO_METERS, 1),
                'markers' => [],
                'issues' => [],
                'values' => $v,
            ];
        }
        return $points;
    }

    private static function flightDescription(Flight $flight): string
    {
        $lines = [
            'Pilot: ' . $flight->pilot->fullname,
            'Aircraft: ' . $flight->aircraft->registration . ' (' . $flight->aircraft->name . ')',
            'Route: ' . $flight->departure . ' - ' . $flight->arrival,
            'Date: ' . $flight->creation_date,
        ];
        return implode('<br>', array_map('htmlspecialchars', $lines));
    }

    private static function writeLookAt(\XMLWriter $w, array $points): void
    {
        $lats = array_column($points, 'lat');
        $lons = array_column($points, 'lon');
        $centerLat = (min($lats) + max($lats)) / 2;
        $centerLon = (min($lons) + max($lons)) / 2;
        $spanKm = max(
            GeoUtils::haversine(min($lats), min($lons), max($lats), max($lons)),
            5
        );
        $w->startElement('LookAt');
        $w->writeElement('longitude', (string)$centerLon);
        $w->writeElement('latitude', (string)$centerLat);
        $w->writeElement('altitude', '0');
        $w->writeElement('heading', '0');
        $w->writeElement('tilt', '45');
        $w->writeElement('range', (string)round($spanKm * 1500));
        $w->writeElement('altitudeMode', 'relativeToGround');
        $w->endElement();
    }

    private static function writeStyles(\XMLWriter $w): void
    {
        foreach (self::PHASE_COLORS as $code => $hex) {
            $w->startElement('Style');
            $w->writeAttribute('id', 'phase_' . $code);
            $w->startElement('LineStyle');
            $w->writeElement('color', self::kmlColor($hex, 'ff'));
            $w->writeElement('width', '3');
            $w->endElement();
            $w->startElement('PolyStyle');
            $w->writeElement('color', self::kmlColor($hex, '33'));
            $w->endElement();
            $w->endElement();

            // Event dot: small coloured circle, no label (the balloon has the details)
            $w->startElement('Style');
            $w->writeAttribute('id', 'dot_' . $code);
            $w->startElement('IconStyle');
            $w->writeElement('color', self::kmlColor($hex, 'ff'));
            $w->writeElement('scale', '0.5');
            $w->startElement('Icon');
            $w->writeElement('href', self::DOT_ICON);
            $w->endElement();
            $w->endElement();
            $w->startElement('LabelStyle');
            $w->writeElement('scale', '0');
            $w->endElement();
            $w->endElement();
        }

        foreach (self::MARKER_COLORS as $type => $hex) {
            $key = in_array($type, self::KEY_MARKERS, true);
            $w->startElement('Style');
            $w->writeAttribute('id', 'marker_' . $type);
            $w->startElement('IconStyle');
            // Key events (lift-off, touchdown) in white so they stand out over any terrain
            $w->writeElement('color', $key ? 'ffffffff' : self::kmlColor($hex, 'ff'));
            $w->writeElement('scale', $key ? '1.3' : '0.9');
            $w->startElement('Icon');
            $w->writeElement('href', self::DOT_ICON);
            $w->endElement();
            $w->endElement();
            $w->startElement('LabelStyle');
            $w->writeElement('color', 'ffffffff');
            $w->writeElement('scale', $key ? '1.3' : '0.9');
            $w->endElement();
            $w->endElement();
        }

        // Issues: caution icon only; the text is in the balloon. With an event at the same point
        // the event label is still shown.
        foreach (['issue' => '0', 'issue_event' => '0.9'] as $id => $labelScale) {
            $w->startElement('Style');
            $w->writeAttribute('id', $id);
            $w->startElement('IconStyle');
            $w->writeElement('scale', '1.2');
            $w->startElement('Icon');
            $w->writeElement('href', self::ISSUE_ICON);
            $w->endElement();
            $w->endElement();
            $w->startElement('LabelStyle');
            $w->writeElement('color', 'ffffffff');
            $w->writeElement('scale', $labelScale);
            $w->endElement();
            $w->endElement();
        }

        $w->startElement('Style');
        $w->writeAttribute('id', 'airport');
        $w->startElement('IconStyle');
        $w->startElement('Icon');
        $w->writeElement('href', self::AIRPORT_ICON);
        $w->endElement();
        $w->endElement();
        $w->endElement();
    }

    /**
     * One LineString per phase. The first point of each phase is also appended to the previous
     * one so consecutive phases are connected (same as the flight map). Extruded to the ground.
     */
    private static function writePhaseLines(\XMLWriter $w, array $points, array $phaseNames): void
    {
        $segments = [];
        foreach ($points as $p) {
            $last = count($segments) - 1;
            if ($last < 0 || $segments[$last]['phase_id'] !== $p['phase_id']) {
                if ($last >= 0) {
                    $segments[$last]['coords'][] = $p;
                }
                $segments[] = [
                    'phase_id' => $p['phase_id'],
                    'phase' => $p['phase'],
                    'coords' => [],
                ];
                $last++;
            }
            $segments[$last]['coords'][] = $p;
        }

        $w->startElement('Folder');
        $w->writeElement('name', 'Track');
        foreach ($segments as $seg) {
            if (count($seg['coords']) < 2) {
                continue;
            }
            $code = self::styleCode($seg['phase']);
            $w->startElement('Placemark');
            $w->writeElement('name', $phaseNames[$seg['phase']] ?? $seg['phase']);
            $w->writeElement('styleUrl', '#phase_' . $code);
            $w->startElement('LineString');
            $w->writeElement('extrude', '1');
            $w->writeElement('tessellate', '1');
            $w->writeElement('altitudeMode', 'relativeToGround');
            $coords = array_map(fn($c) => self::coordinates($c), $seg['coords']);
            $w->writeElement('coordinates', implode(' ', $coords));
            $w->endElement();
            $w->endElement();
        }
        $w->endElement();
    }

    /**
     * A clickable dot for every ACARS event, grouped by phase, whose balloon shows all its values.
     * Points with markers are skipped: they are drawn by writeEventMarkers().
     */
    private static function writeEventDots(\XMLWriter $w, array $points, array $phaseNames): void
    {
        $w->startElement('Folder');
        $w->writeElement('name', 'Event points');
        $currentPhase = null;
        foreach ($points as $p) {
            if (!empty($p['markers']) || !empty($p['issues'])) {
                continue; // drawn in the Events / Issues folders
            }
            if ($p['phase_id'] !== $currentPhase) {
                if ($currentPhase !== null) {
                    $w->endElement(); // Folder (phase)
                }
                $currentPhase = $p['phase_id'];
                $w->startElement('Folder');
                $w->writeElement('name', $phaseNames[$p['phase']] ?? $p['phase']);
            }
            $w->startElement('Placemark');
            $w->writeElement('name', substr($p['timestamp'], 11));
            $w->writeElement('styleUrl', '#dot_' . self::styleCode($p['phase']));
            self::writePointBody($w, $p, $phaseNames);
            $w->endElement();
        }
        if ($currentPhase !== null) {
            $w->endElement();
        }
        $w->endElement();
    }

    private static function pointDescription(array $p, array $phaseNames): string
    {
        $rows = [
            'Time (UTC)' => $p['timestamp'],
            'Phase' => $phaseNames[$p['phase']] ?? $p['phase'],
            'Position' => sprintf('%.5f, %.5f', $p['lat'], $p['lon']),
        ];
        $values = $p['values'];
        foreach (self::POINT_DATA as $code => $label) {
            if (isset($values[$code]) && $values[$code] !== '') {
                $rows[$label] = $values[$code];
            }
            unset($values[$code]);
        }
        foreach ($values as $code => $value) {
            if (!in_array($code, self::POINT_DATA_SKIP, true) && $value !== '') {
                $rows[$code] = $value;
            }
        }
        $html = '';
        foreach ($p['issues'] as $issue) {
            $penalty = $issue['penalty'] !== null
                ? ' (' . Yii::t('app', 'Penalty') . ': ' . $issue['penalty'] . ')' : '';
            $html .= '<p style="color:' . self::ISSUE_TEXT_COLOR . '"><b>⚠ '
                . htmlspecialchars($issue['description'] . $penalty) . '</b></p>';
        }
        foreach ($p['markers'] as $m) {
            $html .= '<p style="color:' . self::MARKER_COLORS[$m['type']] . '"><b>'
                . htmlspecialchars(self::markerLongText($m)) . '</b></p>';
        }
        $html .= '<table>';
        foreach ($rows as $label => $value) {
            $html .= '<tr><td><b>' . htmlspecialchars($label) . '</b></td><td>'
                . htmlspecialchars(self::formatValue((string)$value)) . '</td></tr>';
        }
        return $html . '</table>';
    }

    /**
     * Attaches every marker to the point of the same event (same timestamp), or to the last
     * point before it when that event had no position.
     */
    private static function attachMarkers(array $points, array $markers): array
    {
        $byTimestamp = [];
        foreach ($points as $i => $p) {
            $byTimestamp[$p['timestamp']] = $byTimestamp[$p['timestamp']] ?? $i;
        }
        foreach ($markers as $m) {
            $idx = $byTimestamp[$m['timestamp']] ?? null;
            if ($idx === null) {
                foreach ($points as $i => $p) {
                    if ($p['timestamp'] > $m['timestamp']) {
                        break;
                    }
                    $idx = $i;
                }
            }
            if ($idx !== null) {
                $points[$idx]['markers'][] = $m;
            }
        }
        return $points;
    }

    /**
     * Attaches every issue to the point of the same timestamp or, when there is none, to the
     * nearest point in time (issues are raised at an event, but it may have had no position).
     */
    private static function attachIssues(array $points, array $issues): array
    {
        foreach ($issues as $issue) {
            $target = strtotime($issue['timestamp']);
            $best = null;
            $bestDiff = PHP_INT_MAX;
            foreach ($points as $i => $p) {
                $diff = abs(strtotime($p['timestamp']) - $target);
                if ($diff < $bestDiff) {
                    $best = $i;
                    $bestDiff = $diff;
                }
                if ($diff === 0) {
                    break;
                }
            }
            if ($best !== null) {
                $points[$best]['issues'][] = $issue;
            }
        }
        return $points;
    }

    /**
     * Points with issues: caution icon without text on the map (only the event label, if any);
     * the balloon shows the issues with their penalty on top, then the event data.
     */
    private static function writeIssues(\XMLWriter $w, array $points, array $phaseNames): void
    {
        $issuePoints = array_filter($points, fn($p) => !empty($p['issues']));
        if (empty($issuePoints)) {
            return;
        }
        $w->startElement('Folder');
        $w->writeElement('name', Yii::t('app', 'Detected Issues'));
        $w->writeElement('open', '1');
        foreach ($issuePoints as $p) {
            // Event label if there is one (shown on the map); otherwise the issue text, which is
            // hidden on the map but names the point in the layers panel
            if (!empty($p['markers'])) {
                $name = implode(' · ', array_map(fn($m) => self::markerLabel($m), $p['markers']));
                $style = '#issue_event';
            } else {
                $name = '⚠ ' . implode(' · ', array_column($p['issues'], 'description'));
                $style = '#issue';
            }
            $w->startElement('Placemark');
            $w->writeElement('name', $name);
            $w->writeElement('styleUrl', $style);
            self::writePointBody($w, $p, $phaseNames);
            $w->endElement();
        }
        $w->endElement();
    }

    /**
     * Points with markers (flaps, gear, lift-off, touchdown, AP): labelled like the flight map,
     * coloured by the most relevant marker, and clickable with the same data as any other point.
     */
    private static function writeEventMarkers(\XMLWriter $w, array $points, array $phaseNames): void
    {
        $w->startElement('Folder');
        $w->writeElement('name', Yii::t('app', 'Events'));
        $w->writeElement('open', '1');
        foreach ($points as $p) {
            if (empty($p['markers']) || !empty($p['issues'])) {
                continue;
            }
            $labels = array_map(fn($m) => self::markerLabel($m), $p['markers']);
            $w->startElement('Placemark');
            $w->writeElement('name', implode(' · ', $labels));
            $w->writeElement('styleUrl', '#marker_' . self::mainMarkerType($p['markers']));
            self::writePointBody($w, $p, $phaseNames);
            $w->endElement();
        }
        $w->endElement();
    }

    /** Writes the description and the Point geometry of a point placemark. */
    private static function writePointBody(\XMLWriter $w, array $p, array $phaseNames): void
    {
        $w->startElement('description');
        $w->writeCdata(self::pointDescription($p, $phaseNames));
        $w->endElement();
        $w->startElement('Point');
        $w->writeElement('altitudeMode', 'relativeToGround');
        $w->writeElement('coordinates', self::coordinates($p));
        $w->endElement();
    }

    private static function mainMarkerType(array $markers): string
    {
        $types = array_column($markers, 'type');
        foreach (array_keys(self::MARKER_COLORS) as $type) {
            if (in_array($type, $types, true)) {
                return $type;
            }
        }
        return $types[0];
    }

    /** Short label shown on the map, same texts as the flight map. */
    private static function markerLabel(array $m): string
    {
        switch ($m['type']) {
            case FlightEventMarkers::TYPE_FLAPS:
                return Yii::t('app', 'Flaps') . ' ' . $m['to'] . '%';
            case FlightEventMarkers::TYPE_GEAR:
                return $m['to'] === 'Down' ? Yii::t('app', 'Gear Down') : Yii::t('app', 'Gear Up');
            case FlightEventMarkers::TYPE_LIFTOFF:
                return Yii::t('app', 'Lift-off');
            case FlightEventMarkers::TYPE_TOUCHDOWN:
                return Yii::t('app', 'Touchdown')
                    . ($m['extra'] !== null ? ' ' . $m['extra'] . ' fpm' : '');
            case FlightEventMarkers::TYPE_AUTOPILOT:
                return $m['to'] === 'On' ? 'AP ON' : 'AP OFF';
        }
        return $m['short'];
    }

    /** Long text shown in the balloon, same texts as the flight map popup. */
    private static function markerLongText(array $m): string
    {
        switch ($m['type']) {
            case FlightEventMarkers::TYPE_FLAPS:
                return Yii::t('app', 'Flaps') . ' ' . $m['from'] . '% → ' . $m['to'] . '%';
            case FlightEventMarkers::TYPE_AUTOPILOT:
                return $m['to'] === 'On' ? Yii::t('app', 'Autopilot engaged') : Yii::t('app', 'Autopilot disengaged');
            case FlightEventMarkers::TYPE_TOUCHDOWN:
                return Yii::t('app', 'Touchdown') . ($m['extra'] !== null ? ' ' . $m['extra'] . ' fpm' : '');
        }
        return self::markerLabel($m);
    }

    private static function coordinates(array $p): string
    {
        return $p['lon'] . ',' . $p['lat'] . ',' . $p['agl'];
    }

    private static function writeAirports(\XMLWriter $w, Flight $flight): void
    {
        $airports = array_filter([$flight->departure0, $flight->arrival0]);
        if (empty($airports)) {
            return;
        }
        $w->startElement('Folder');
        $w->writeElement('name', 'Airports');
        foreach ($airports as $airport) {
            $w->startElement('Placemark');
            $w->writeElement('name', $airport->icao_code);
            $w->writeElement('description', $airport->name);
            $w->writeElement('styleUrl', '#airport');
            $w->startElement('Point');
            $w->writeElement('coordinates', $airport->longitude . ',' . $airport->latitude . ',0');
            $w->endElement();
            $w->endElement();
        }
        $w->endElement();
    }

    /** Converts #rrggbb to KML aabbggrr. */
    private static function kmlColor(string $hex, string $alpha): string
    {
        $hex = ltrim($hex, '#');
        return $alpha . substr($hex, 4, 2) . substr($hex, 2, 2) . substr($hex, 0, 2);
    }

    /** Rounds decimal numbers (ACARS may send them with a comma) to one decimal. */
    private static function formatValue(string $value): string
    {
        $normalized = str_replace(',', '.', $value);
        if (is_numeric($normalized) && strpos($normalized, '.') !== false) {
            return (string)round((float)$normalized, 1);
        }
        return $value;
    }

    private static function styleCode(string $phase): string
    {
        return isset(self::PHASE_COLORS[$phase]) ? $phase : 'unknown';
    }
}
