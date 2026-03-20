<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;
use app\models\NavPoint;
use app\models\AirwaySegment;

echo $this->render('@app/views/layouts/_openlayers');

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js',
    ['position' => \yii\web\View::POS_HEAD]
);

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js',
    ['position' => \yii\web\View::POS_HEAD]
);
?>

<?php
$colors = array(
    'startup' => '#00ccff',
    'taxi' => 'yellow',
    'takeoff' => '#00ff00',
    'cruise' => 'blue',
    'touch_go'=> 'purple',
    'approach' => 'red',
    'final_landing' => '#ff9900',
    'shutdown' => '#cc00cc',
    'unknown' => '#888888',
    'backtrack' => 'brown'
);

$segments = [];
$altitudePoints = [];
$groundPoints = [];
$phaseIntervals = [];
$labels = [];
$allEvents = [];
$counter = 1;

foreach ($report->flightPhases as $phase) {
    $start = $phase->start;
    $end   = $phase->end;
    $color = $colors[$phase->flightPhaseType->code];

    $phaseIntervals[] = [
        'start' => $start,
        'end'   => $end,
        'color' => $color,
    ];

    $coordinates = [];
    foreach ($phase->flightEvents as $event) {
        $eventData = [
            'eventIndex' => $counter,
            'id' => $event->id,
            'timestamp' => $event->timestamp,
            'values' => []
        ];
        foreach ($event->flightEventDatas as $data) {
            $eventData['values'][$data->attribute0->code] = $data->value;
        }
        $allEvents[] = $eventData;
        $counter++;

        $lat = null;
        $lon = null;
        $altitude = null;
        $aglAltitude = null;
        foreach($event->flightEventDatas as $data) {
            $code = $data->attribute0->code;
            if($code == 'Latitude'){
                $lat = (float)$data->value;
            } else if($code == 'Longitude'){
                $lon = (float)$data->value;
            } elseif ($code == 'Altitude') {
                $altitude = (int)$data->value;
            } elseif ($code == 'AGLAltitude') {
                $aglAltitude = (int)$data->value;
            }
            if($lat != null && $lon != null && $altitude != null && $aglAltitude != null) break;
        }
        if ($lat !== null && $lon !== null) {
            $coordinates[] = [$lon, $lat];
        }
        if($altitude != null && $aglAltitude != null){
            $timestamp = $event->timestamp;
            $labels[] = $timestamp;
            if($lat !== null && $lon !== null){
                $altitudePoints[] = ['x' => $timestamp, 'y' => $altitude, 'coords' => [$lon, $lat]];
            } else {
                $altitudePoints[] = ['x' => $timestamp, 'y' => $altitude];
            }

            $groundAltitude = $altitude - $aglAltitude;
            $groundPoints[] = ['x' => $timestamp, 'y' => $groundAltitude];
        }
    }

    // map
    if (!empty($coordinates)) {
        if (count($segments) > 0) {
            $lastIndex = count($segments) - 1;
            $segments[$lastIndex]['coordinates'][] = $coordinates[0];
        }
        $segments[] = [
            'phase' => $phase->flightPhaseType->lang->name,
            'code'  => $phase->flightPhaseType->code,
            'color' => $colors[$phase->flightPhaseType->code],
            'coordinates' => $coordinates,
        ];
    }

    // altitude
    if (!empty($pointsAltitude)) {
        $datasets[] = [
            'label' => $phase->flightPhaseType->lang->name . ' (Avión)',
            'data' => $pointsAltitude,
            'borderColor' => $colors[$phase->flightPhaseType->code],
            'fill' => false,
            'tension' => 0.1,
        ];
    }
    if (!empty($pointsGround)) {
        $datasets[] = [
            'label' => $phase->flightPhaseType->lang->name . ' (Terreno)',
            'data' => $pointsGround,
            'borderColor' => $colors[$phase->flightPhaseType->code],
            'borderDash' => [5, 5], // línea discontinua
            'fill' => false,
            'tension' => 0.1,
        ];
    }
}

// Collect route coordinates as [lon, lat] pairs
$routeCoords = [];
foreach ($segments as $seg) {
    foreach ($seg['coordinates'] as $coord) {
        $routeCoords[] = $coord;
    }
}

/**
 * Distance (km) from point P to segment AB using flat-earth approximation
 * (accurate enough for distances under ~200 km).
 */
$pointToSegmentKm = function(float $pLat, float $pLon, float $aLat, float $aLon, float $bLat, float $bLon): float {
    $cosLat = cos(deg2rad(($aLat + $bLat) / 2));
    $px = ($pLon - $aLon) * 111.0 * $cosLat;
    $py = ($pLat - $aLat) * 111.0;
    $bx = ($bLon - $aLon) * 111.0 * $cosLat;
    $by = ($bLat - $aLat) * 111.0;
    $lenSq = $bx * $bx + $by * $by;
    if ($lenSq < 1e-10) {
        return sqrt($px * $px + $py * $py);
    }
    $t = max(0.0, min(1.0, ($px * $bx + $py * $by) / $lenSq));
    $dx = $px - $t * $bx;
    $dy = $py - $t * $by;
    return sqrt($dx * $dx + $dy * $dy);
};

/** Returns the minimum distance (km) from a point to the flight route polyline. */
$minDistToRouteKm = function(float $npLat, float $npLon) use ($routeCoords, $pointToSegmentKm): float {
    $min = PHP_FLOAT_MAX;
    $n = count($routeCoords);
    for ($i = 0; $i < $n - 1; $i++) {
        $d = $pointToSegmentKm(
            $npLat, $npLon,
            $routeCoords[$i][1],   $routeCoords[$i][0],
            $routeCoords[$i + 1][1], $routeCoords[$i + 1][0]
        );
        if ($d < $min) $min = $d;
    }
    return $min;
};

$navPointsJson = '[]';
$airwaySegmentsJson = '[]';
if (!empty($routeCoords)) {
    $allLats = array_column($routeCoords, 1);
    $allLons = array_column($routeCoords, 0);
    // Generous bbox margin for SQL pre-filter (~110 km); fine filtering is done in PHP
    $margin = 1.0;
    $minLat = min($allLats) - $margin;
    $maxLat = max($allLats) + $margin;
    $minLon = min($allLons) - $margin;
    $maxLon = max($allLons) + $margin;

    $candidates = NavPoint::find()
        ->where(['between', 'latitude', $minLat, $maxLat])
        ->andWhere(['between', 'longitude', $minLon, $maxLon])
        ->with('navaids')
        ->all();

    // Fine filter: keep only points within 10 km of any route segment
    $navPoints = array_filter($candidates, fn($np) =>
        $minDistToRouteKm((float)$np->latitude, (float)$np->longitude) <= 10.0
    );

    $npById = [];
    $navPointsData = [];
    foreach ($navPoints as $np) {
        $npById[$np->id] = $np;
        $navaidsData = [];
        foreach ($np->navaids as $navaid) {
            $navaidsData[] = [
                'frequency' => $navaid->frequency,
            ];
        }
        $navPointsData[] = [
            'id'         => $np->id,
            'lat'        => (float)$np->latitude,
            'lon'        => (float)$np->longitude,
            'identifier' => $np->identifier,
            'name'       => $np->name,
            'point_type' => $np->point_type,
            'navaids'    => $navaidsData,
        ];
    }
    $navPointsJson = Json::encode($navPointsData);

    $navPointIds = array_keys($npById);
    if (!empty($navPointIds)) {
        $airwaySegments = AirwaySegment::find()
            ->where(['from_nav_point_id' => $navPointIds])
            ->andWhere(['to_nav_point_id' => $navPointIds])
            ->all();

        $airwaySegmentsData = [];
        foreach ($airwaySegments as $seg2) {
            $from = $npById[$seg2->from_nav_point_id];
            $to   = $npById[$seg2->to_nav_point_id];
            $airwaySegmentsData[] = [
                'from_lon'     => (float)$from->longitude,
                'from_lat'     => (float)$from->latitude,
                'to_lon'       => (float)$to->longitude,
                'to_lat'       => (float)$to->latitude,
                'airway_names' => $seg2->airway_names,
            ];
        }
        $airwaySegmentsJson = Json::encode($airwaySegmentsData);
    }
}

$airportRunways = [];
$runwayAirports = [$report->flight->departure0];
if ($report->landingAirport !== null) {
    $runwayAirports[] = $report->landingAirport;
}
foreach ($runwayAirports as $airport) {
    $rwys = $airport->getRunways()->with('runwayEnds')->all();
    foreach ($rwys as $runway) {
        $ends = $runway->runwayEnds;
        if (count($ends) === 2) {
            $airportRunways[] = [
                'designators' => $runway->designators,
                'width' => (float)$runway->width_m,
                'end1' => [
                    'designator' => $ends[0]->designator,
                    'lat' => (float)$ends[0]->latitude,
                    'lon' => (float)$ends[0]->longitude,
                    'displaced_threshold' => (float)($ends[0]->displaced_threshold_m ?? 0),
                    'stopway' => (float)($ends[0]->stopway_m ?? 0),
                ],
                'end2' => [
                    'designator' => $ends[1]->designator,
                    'lat' => (float)$ends[1]->latitude,
                    'lon' => (float)$ends[1]->longitude,
                    'displaced_threshold' => (float)($ends[1]->displaced_threshold_m ?? 0),
                    'stopway' => (float)($ends[1]->stopway_m ?? 0),
                ],
            ];
        }
    }
}
$airportRunwaysJson = Json::encode($airportRunways);
?>

<div class="container">
    <div class="timeline">
        <div class="row mb-3 d-flex align-items-stretch">
        <?php
        $count = 0;
        foreach ($report->flightPhases as $phase):
            if ($phase->flightPhaseType->code !== 'unknown'):
                $count++;
        ?>
            <div class="col-md-4 timeline-item phase-card"
                 data-start-ts="<?= Html::encode($phase->start) ?>"
                 style="cursor:pointer">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($phase->flightPhaseType->lang->name) ?></h5>
                            <div class="d-flex align-items-center">
                                <small class="text-muted me-2">
                                    <?= date('H:i', strtotime($phase->start)) ?> - <?= date('H:i', strtotime($phase->end)) ?>
                                </small>
                                <span class="phase-color" style="background-color: <?= $colors[$phase->flightPhaseType->code] ?>;"></span>
                            </div>
                        </div>
                        <?php foreach ($phase->flightPhaseMetrics as $metric): ?>
                            <p class="card-text mb-1">
                                <?= htmlspecialchars($metric->metricType->lang->name) . ' : ' . htmlspecialchars($metric->value) ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if ($count % 3 === 0): ?>
                </div>
                <div class="row mb-3 d-flex align-items-stretch">
            <?php endif; ?>

        <?php
            endif;
        endforeach;
        ?>
        </div>
    </div>
</div>

<style>
.phase-color {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 1px solid #333;
    flex-shrink: 0;
}
.phase-card {
    transition: transform .1s ease, box-shadow .1s ease;
}
.phase-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,.25);
}
</style>



<div class="container">
    <div style="position: relative;">
        <div style="position: absolute; top: 8px; right: 8px; z-index: 1000;">
            <div class="btn-group btn-group-sm shadow-sm" role="group">
                <button id="mapStyleOSM" class="btn" style="background:var(--brand);color:var(--bg-white);border-color:var(--brand-dark);">VFR</button>
                <button id="mapStyleIFR" class="btn" style="background:var(--bg-white);color:var(--brand);border-color:var(--brand);">IFR</button>
            </div>
        </div>
        <div style="position: absolute; top: 8px; left: 8px; z-index: 1000;
                    background: var(--bg-white); border: 1px solid #ccc; border-radius: 4px;
                    padding: 6px 10px; font-size: 11px; line-height: 1.8;">
            <div style="font-weight: bold; margin-bottom: 2px; color: var(--text-dark);">Nav Points</div>
            <label style="display:flex; align-items:center; gap:5px; cursor:pointer; color:var(--text-dark);">
                <input type="checkbox" id="airwayFilterCheck" checked>
                <span style="display:inline-block; width:10px; height:3px;
                             background:rgba(60,60,200,0.65); flex-shrink:0;"></span>
                <?= Yii::t('app', 'Airways') ?>
            </label>
            <hr style="margin: 3px 0;">
            <?php
            $navTypes = [
                'VOR'     => '#2255ff',
                'NDB'     => '#ff8800',
                'DME'     => '#00aacc',
                'ILS-LOC' => '#00cc44',
                'LOC'     => '#00cc44',
                'FIX'     => '#666666',
            ];
            foreach ($navTypes as $type => $color):
            ?>
            <label style="display:flex; align-items:center; gap:5px; cursor:pointer; color:var(--text-dark);">
                <input type="checkbox" class="nav-filter-check" data-type="<?= $type ?>" checked>
                <span style="display:inline-block; width:10px; height:10px; border-radius:50%;
                             background:<?= $color ?>; border:1px solid rgba(0,0,0,0.2); flex-shrink:0;"></span>
                <?= $type ?>
            </label>
            <?php endforeach; ?>
        </div>
        <div id="map" style="width: 100%; height: 600px;"></div>
    </div>
    <canvas id="altitudeChart" width="800" height="400"></canvas>
    <div class="mt-4">
        <h5><?=Yii::t('app', 'Raw Event Viewer')?></h5>
        <div class="d-flex gap-2 mb-2">
            <button id="prevEvent" class="btn btn-sm btn-secondary">⬅ <?=Yii::t('app', 'Previous')?></button>
            <button id="nextEvent" class="btn btn-sm btn-secondary"><?=Yii::t('app', 'Next')?> ➡</button>
        </div>
        <pre id="rawEventViewer" style="background:#111;color:#0f0;padding:10px;height:250px;overflow:auto;border:1px solid #444;"></pre>
    </div>
</div>

<?php
$this->registerJs("
const rawEvents = ". json_encode($allEvents) . ";

const rawEventIndexByTimestamp = {};
rawEvents.forEach((ev, idx) => {
    rawEventIndexByTimestamp[ev.timestamp] = idx;
});

const segments = " . json_encode($segments) . ";

let currentEventIndex = 0;

function updateMapFromEvent(ev) {
    const lat = ev.values?.Latitude;
    const lon = ev.values?.Longitude;

    if (lat === undefined || lon === undefined) return;

    const coords = [parseFloat(lon), parseFloat(lat)];
    const mapCoordinates = ol.proj.fromLonLat(coords);

    pointSource.clear();
    pointSource.addFeature(new ol.Feature(new ol.geom.Point(mapCoordinates)));

    map.getView().animate({
        center: mapCoordinates,
        duration: 500
    });
}

function showRawEvent(index) {
    if (index < 0 || index >= rawEvents.length) return;
    currentEventIndex = index;

    const ev = rawEvents[currentEventIndex];
    document.getElementById('rawEventViewer').textContent =
        JSON.stringify(ev, null, 2);

    triggerChartPointByTimestamp(ev.timestamp);
    updateMapFromEvent(ev);
}

function showRawEventByTimestamp(ts) {
    const index = rawEventIndexByTimestamp[ts];
    if (index !== undefined) {
        showRawEvent(index);
    }
}

document.querySelectorAll('.phase-card').forEach(card => {
    card.addEventListener('click', () => {
        const ts = card.dataset.startTs;
        const index = rawEventIndexByTimestamp[ts];
        if (index !== undefined) {
            showRawEvent(index);
        }
    });
});

window.addEventListener('jumpToTimestamp', (e) => {
    showRawEventByTimestamp(e.detail.timestamp);
});

document.getElementById('prevEvent').addEventListener('click', () => {
    showRawEvent(currentEventIndex - 1);
});

document.getElementById('nextEvent').addEventListener('click', () => {
    showRawEvent(currentEventIndex + 1);
});

// https://github.com/openlayers/openlayers/issues/11681
function splitAtDateLine(coords) {
    const lineStrings = [];
    let lastX = Infinity
    let lineString;
    for (let i = 0, ii = coords.length; i < ii; ++i) {
        const coord = coords[i];
        const x = coord[0];
        if (Math.abs(lastX - x) > 180) { // Crossing date line will be shorter
            if (lineString) {
                const prevCoord = coords[i - 1];
                const w1 = 180 - Math.abs(lastX);
                const w2 = 180 - Math.abs(x);
                const y = (w1 / (w1 + w2)) * (coord[1] - prevCoord[1]) + prevCoord[1];
                if (Math.abs(lastX) !== 180) {
                    lineString.push(ol.proj.fromLonLat([lastX > 0 ? 180 : -180, y]));
                }
                lineStrings.push(lineString = []);
                if (Math.abs(x) !== 180) {
                    lineString.push(ol.proj.fromLonLat([x > 0 ? 180 : -180, y]));
                }
            } else {
                lineStrings.push(lineString = []);
            }
        }
        lastX = x;
        lineString.push(ol.proj.fromLonLat(coord));
    }
    return lineStrings;
}

const layers = segments.map(seg => {
    const feature = new ol.Feature({
        geometry: new ol.geom.MultiLineString(splitAtDateLine(seg.coordinates))
    });
    const source = new ol.source.Vector({ features: [feature] });
    return new ol.layer.Vector({
        source: source,
        style: new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: seg.color,
                width: 3
            })
        })
    });
});

const phaseMarkers = [];
segments.forEach(seg => {
    if ((seg.code === 'startup' || seg.code === 'shutdown') && seg.coordinates.length > 0) {
        const coord = ol.proj.fromLonLat(seg.coordinates[0]);
        const feature = new ol.Feature({ geometry: new ol.geom.Point(coord) });
        feature.setStyle(new ol.style.Style({
            image: new ol.style.Circle({
                radius: 7,
                fill: new ol.style.Fill({ color: seg.color }),
                stroke: new ol.style.Stroke({ color: '#000', width: 2 })
            })
        }));
        phaseMarkers.push(feature);
    }
});
const phaseMarkerLayer = new ol.layer.Vector({
    source: new ol.source.Vector({ features: phaseMarkers })
});

const pointSource = new ol.source.Vector();
const pointLayer = new ol.layer.Vector({
    source: pointSource,
    style: new ol.style.Style({
        image: new ol.style.Circle({
            radius: 7,
            fill: new ol.style.Fill({
                color: 'rgba(255, 0, 0, 0.7)' // Rojo semi-transparente
            }),
            stroke: new ol.style.Stroke({
                color: 'rgba(255, 0, 0, 1)',
                width: 2
            })
        })
    })
});

const airportRunways = " . $airportRunwaysJson . ";
const runwayLayers = [];

if (airportRunways.length > 0) {
    function offsetPoint(lat, lon, bearingDeg, distanceM) {
        const R = 6371000;
        const bearing = bearingDeg * Math.PI / 180;
        const lat1 = lat * Math.PI / 180;
        const lon1 = lon * Math.PI / 180;
        const lat2 = lat1 + (distanceM / R) * Math.cos(bearing);
        const lon2 = lon1 + (distanceM / R) * Math.sin(bearing) / Math.cos(lat1);
        return [lat2 * 180 / Math.PI, lon2 * 180 / Math.PI];
    }

    function calculateBearing(lat1, lon1, lat2, lon2) {
        const lat1R = lat1 * Math.PI / 180;
        const lon1R = lon1 * Math.PI / 180;
        const lat2R = lat2 * Math.PI / 180;
        const lon2R = lon2 * Math.PI / 180;
        const dlon = lon2R - lon1R;
        const x = Math.sin(dlon) * Math.cos(lat2R);
        const y = Math.cos(lat1R) * Math.sin(lat2R) - Math.sin(lat1R) * Math.cos(lat2R) * Math.cos(dlon);
        return ((Math.atan2(x, y) * 180 / Math.PI) + 360) % 360;
    }

    function toMapCoord(latLon) {
        return ol.proj.fromLonLat([latLon[1], latLon[0]]);
    }

    function makePolygonCoords(corners) {
        return [corners.map(function(c) { return toMapCoord(c); })];
    }

    const rwyFeatures = [];

    airportRunways.forEach(function(rwy) {
        const end1 = rwy.end1;
        const end2 = rwy.end2;
        const halfWidth = rwy.width / 2;

        const bearing = calculateBearing(end1.lat, end1.lon, end2.lat, end2.lon);
        const reverseBearing = (bearing + 180) % 360;
        const perpLeft = ((bearing - 90) % 360 + 360) % 360;
        const perpRight = (bearing + 90) % 360;

        if (end1.stopway > 0) {
            const p1 = offsetPoint(end1.lat, end1.lon, reverseBearing, end1.stopway);
            const p1L = offsetPoint(p1[0], p1[1], perpLeft, halfWidth);
            const p1R = offsetPoint(p1[0], p1[1], perpRight, halfWidth);
            const t1L = offsetPoint(end1.lat, end1.lon, perpLeft, halfWidth);
            const t1R = offsetPoint(end1.lat, end1.lon, perpRight, halfWidth);
            rwyFeatures.push(new ol.Feature({
                geometry: new ol.geom.Polygon(makePolygonCoords([p1L, p1R, t1R, t1L, p1L])),
                zone: 'stopway', label: 'Stopway ' + end1.designator
            }));
        }

        if (end2.stopway > 0) {
            const p2 = offsetPoint(end2.lat, end2.lon, bearing, end2.stopway);
            const p2L = offsetPoint(p2[0], p2[1], perpLeft, halfWidth);
            const p2R = offsetPoint(p2[0], p2[1], perpRight, halfWidth);
            const t2L = offsetPoint(end2.lat, end2.lon, perpLeft, halfWidth);
            const t2R = offsetPoint(end2.lat, end2.lon, perpRight, halfWidth);
            rwyFeatures.push(new ol.Feature({
                geometry: new ol.geom.Polygon(makePolygonCoords([t2L, t2R, p2R, p2L, t2L])),
                zone: 'stopway', label: 'Stopway ' + end2.designator
            }));
        }

        if (end1.displaced_threshold > 0) {
            const dt1 = offsetPoint(end1.lat, end1.lon, bearing, end1.displaced_threshold);
            const e1L = offsetPoint(end1.lat, end1.lon, perpLeft, halfWidth);
            const e1R = offsetPoint(end1.lat, end1.lon, perpRight, halfWidth);
            const dt1L = offsetPoint(dt1[0], dt1[1], perpLeft, halfWidth);
            const dt1R = offsetPoint(dt1[0], dt1[1], perpRight, halfWidth);
            rwyFeatures.push(new ol.Feature({
                geometry: new ol.geom.Polygon(makePolygonCoords([e1L, e1R, dt1R, dt1L, e1L])),
                zone: 'displaced', label: 'Displaced ' + end1.designator
            }));
        }

        if (end2.displaced_threshold > 0) {
            const dt2 = offsetPoint(end2.lat, end2.lon, reverseBearing, end2.displaced_threshold);
            const e2L = offsetPoint(end2.lat, end2.lon, perpLeft, halfWidth);
            const e2R = offsetPoint(end2.lat, end2.lon, perpRight, halfWidth);
            const dt2L = offsetPoint(dt2[0], dt2[1], perpLeft, halfWidth);
            const dt2R = offsetPoint(dt2[0], dt2[1], perpRight, halfWidth);
            rwyFeatures.push(new ol.Feature({
                geometry: new ol.geom.Polygon(makePolygonCoords([dt2L, dt2R, e2R, e2L, dt2L])),
                zone: 'displaced', label: 'Displaced ' + end2.designator
            }));
        }

        let s1Lat = end1.lat, s1Lon = end1.lon;
        let s2Lat = end2.lat, s2Lon = end2.lon;
        if (end1.displaced_threshold > 0) {
            const s = offsetPoint(end1.lat, end1.lon, bearing, end1.displaced_threshold);
            s1Lat = s[0]; s1Lon = s[1];
        }
        if (end2.displaced_threshold > 0) {
            const s = offsetPoint(end2.lat, end2.lon, reverseBearing, end2.displaced_threshold);
            s2Lat = s[0]; s2Lon = s[1];
        }

        const s1L = offsetPoint(s1Lat, s1Lon, perpLeft, halfWidth);
        const s1R = offsetPoint(s1Lat, s1Lon, perpRight, halfWidth);
        const s2L = offsetPoint(s2Lat, s2Lon, perpLeft, halfWidth);
        const s2R = offsetPoint(s2Lat, s2Lon, perpRight, halfWidth);
        rwyFeatures.push(new ol.Feature({
            geometry: new ol.geom.Polygon(makePolygonCoords([s1L, s1R, s2R, s2L, s1L])),
            zone: 'runway', label: rwy.designators
        }));

        const th1L = offsetPoint(end1.lat, end1.lon, perpLeft, halfWidth);
        const th1R = offsetPoint(end1.lat, end1.lon, perpRight, halfWidth);
        rwyFeatures.push(new ol.Feature({
            geometry: new ol.geom.LineString([toMapCoord(th1L), toMapCoord(th1R)]),
            zone: 'threshold', label: end1.designator
        }));
        const th2L = offsetPoint(end2.lat, end2.lon, perpLeft, halfWidth);
        const th2R = offsetPoint(end2.lat, end2.lon, perpRight, halfWidth);
        rwyFeatures.push(new ol.Feature({
            geometry: new ol.geom.LineString([toMapCoord(th2L), toMapCoord(th2R)]),
            zone: 'threshold', label: end2.designator
        }));
    });

    const rwyStyles = {
        'runway': new ol.style.Style({
            fill: new ol.style.Fill({ color: 'rgba(51, 51, 51, 0.8)' }),
            stroke: new ol.style.Stroke({ color: '#000', width: 1 })
        }),
        'displaced': new ol.style.Style({
            fill: new ol.style.Fill({ color: 'rgba(255, 152, 0, 0.8)' }),
            stroke: new ol.style.Stroke({ color: '#e65100', width: 1 })
        }),
        'stopway': new ol.style.Style({
            fill: new ol.style.Fill({ color: 'rgba(244, 67, 54, 0.8)' }),
            stroke: new ol.style.Stroke({ color: '#b71c1c', width: 1 })
        })
    };

    const rwyLayer = new ol.layer.Vector({
        source: new ol.source.Vector({ features: rwyFeatures }),
        style: function(feature) {
            const zone = feature.get('zone');
            if (zone === 'threshold') {
                return [
                    new ol.style.Style({
                        stroke: new ol.style.Stroke({ color: '#1b5e20', width: 6 })
                    }),
                    new ol.style.Style({
                        stroke: new ol.style.Stroke({ color: '#4caf50', width: 3 }),
                        text: new ol.style.Text({
                            text: feature.get('label'),
                            font: 'bold 12px sans-serif',
                            offsetY: -15,
                            fill: new ol.style.Fill({ color: '#1b5e20' }),
                            stroke: new ol.style.Stroke({ color: '#fff', width: 3 })
                        })
                    })
                ];
            }
            return rwyStyles[zone];
        }
    });

    runwayLayers.push(rwyLayer);
}

const navPoints = " . $navPointsJson . ";
const airwaySegments = " . $airwaySegmentsJson . ";

// SVG symbol definitions per navaid type (ICAO-inspired)
// Each entry: [svgString, width, height]
const NAV_SVG = {
    // VOR: hexagon outline + center dot
    'VOR': [
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-12 -12 24 24' width='24' height='24'>` +
        `<polygon points='11,0 5.5,9.5 -5.5,9.5 -11,0 -5.5,-9.5 5.5,-9.5' fill='none' stroke='#2255ff' stroke-width='2'/>` +
        `<circle cx='0' cy='0' r='2.5' fill='#2255ff'/></svg>`, 24, 24],
    // NDB: two concentric circles + center dot
    'NDB': [
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-11 -11 22 22' width='22' height='22'>` +
        `<circle cx='0' cy='0' r='10' fill='none' stroke='#ff8800' stroke-width='2'/>` +
        `<circle cx='0' cy='0' r='5.5' fill='none' stroke='#ff8800' stroke-width='1.5'/>` +
        `<circle cx='0' cy='0' r='2.5' fill='#ff8800'/></svg>`, 22, 22],
    // DME: square outline + center dot
    'DME': [
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-10 -10 20 20' width='20' height='20'>` +
        `<rect x='-9' y='-9' width='18' height='18' fill='none' stroke='#00aacc' stroke-width='2'/>` +
        `<circle cx='0' cy='0' r='2.5' fill='#00aacc'/></svg>`, 20, 20],
    // ILS-LOC / LOC: diamond outline + center dot (placeholder until full beam is implemented)
    'ILS-LOC': [
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-11 -11 22 22' width='22' height='22'>` +
        `<polygon points='0,-10 10,0 0,10 -10,0' fill='none' stroke='#00cc44' stroke-width='2'/>` +
        `<circle cx='0' cy='0' r='2.5' fill='#00cc44'/></svg>`, 22, 22],
    'LOC': [
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-11 -11 22 22' width='22' height='22'>` +
        `<polygon points='0,-10 10,0 0,10 -10,0' fill='none' stroke='#00cc44' stroke-width='2'/>` +
        `<circle cx='0' cy='0' r='2.5' fill='#00cc44'/></svg>`, 22, 22],
};
// FIX and unknown types: open upward triangle
const NAV_SVG_DEFAULT = [
    `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-10 -10 20 20' width='20' height='20'>` +
    `<polygon points='0,-9 8,7 -8,7' fill='none' stroke='#666666' stroke-width='1.8'/></svg>`, 20, 20];

function makeNavStyle(np) {
    const [svgStr, , iconH] = NAV_SVG[np.point_type] || NAV_SVG_DEFAULT;
    const src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgStr);
    const baseOffsetY = Math.round(iconH / 2) + 9;
    const freqStr = np.navaids.map(n => n.frequency).join(' / ');
    const styles = [
        new ol.style.Style({
            image: new ol.style.Icon({
                src: src,
                anchor: [0.5, 0.5],
                anchorXUnits: 'fraction',
                anchorYUnits: 'fraction',
            })
        }),
        new ol.style.Style({
            text: new ol.style.Text({
                text: np.identifier,
                font: 'bold 10px sans-serif',
                offsetY: baseOffsetY,
                textAlign: 'center',
                fill: new ol.style.Fill({ color: '#222' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
            })
        })
    ];
    if (freqStr) {
        styles.push(new ol.style.Style({
            text: new ol.style.Text({
                text: freqStr,
                font: 'bold 11px sans-serif',
                offsetY: baseOffsetY + 12,
                textAlign: 'center',
                fill: new ol.style.Fill({ color: '#555' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
            })
        }));
    }
    return styles;
}

const navTypeVisible = { 'VOR': true, 'NDB': true, 'DME': true, 'ILS-LOC': true, 'LOC': true, 'FIX': true };

const navFeatures = navPoints.map(np => {
    const feature = new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.fromLonLat([np.lon, np.lat]))
    });
    feature.set('np', np);
    return feature;
});
const navSource = new ol.source.Vector({ features: navFeatures });
const navLayer = new ol.layer.Vector({
    source: navSource,
    style: function(feature) {
        const np = feature.get('np');
        if (!navTypeVisible[np.point_type]) return null;
        return makeNavStyle(np);
    }
});

document.querySelectorAll('.nav-filter-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        navTypeVisible[this.dataset.type] = this.checked;
        navSource.changed();
    });
});

document.getElementById('airwayFilterCheck').addEventListener('change', function() {
    airwayLayer.setVisible(this.checked);
});

const airwayFeatures = airwaySegments.map(seg => {
    const feature = new ol.Feature({
        geometry: new ol.geom.LineString([
            ol.proj.fromLonLat([seg.from_lon, seg.from_lat]),
            ol.proj.fromLonLat([seg.to_lon, seg.to_lat])
        ])
    });
    feature.set('airway_names', seg.airway_names);
    return feature;
});
const airwayLayer = new ol.layer.Vector({
    source: new ol.source.Vector({ features: airwayFeatures }),
    declutter: true,
    style: function(feature) {
        return [
            new ol.style.Style({
                stroke: new ol.style.Stroke({ color: 'rgba(60,60,200,0.65)', width: 3 })
            }),
            new ol.style.Style({
                text: new ol.style.Text({
                    text: feature.get('airway_names'),
                    font: 'bold 11px sans-serif',
                    placement: 'line',
                    fill: new ol.style.Fill({ color: '#1a1aaa' }),
                    stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
                    overflow: true
                })
            })
        ];
    }
});

const map = new ol.Map({
    target: 'map',
    layers: [
        new ol.layer.Tile({
            source: new ol.source.OSM()
        }),
        new ol.layer.Tile({
            source: new ol.source.XYZ({
                url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
                attributions: 'Tiles &copy; <a href=\"https://www.esri.com/\">Esri</a>'
            }),
            visible: false
        }),
        ...runwayLayers,
        airwayLayer,
        navLayer,
        pointLayer,
        ...layers,
        phaseMarkerLayer
    ],
    view: new ol.View({
        center: ol.proj.fromLonLat(segments[0].coordinates[0]),
        zoom: 15
    })
});

const phaseIntervals = " . json_encode($phaseIntervals) . ";

const labels = " . json_encode($labels) . ";
const altitudePoints = " . json_encode($altitudePoints) . ";
const groundPoints = " . json_encode($groundPoints) . ";

function getPhaseColor(ts) {
  for (const phase of phaseIntervals) {
    if (ts >= phase.start && ts <= phase.end) {
      return phase.color;
    }
  }
  return '#888888'; // fallback
}

const ctx = document.getElementById('altitudeChart').getContext('2d');
const myChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: labels,
    datasets: [
    {
            label: '" . Yii::t('app', 'Plane') . "',
            data: altitudePoints,
            segment: {
                borderColor: ctx => {
                    const ts = ctx.p1.raw.x;
                    return getPhaseColor(ts);
                }
            },
            borderColor: 'blue', // fallback
            fill: false,
            tension: 0.1,
            pointRadius: 0,
            hitRadius: 10,
            pointHoverRadius: 10,
          },
          {
            label: '" . Yii::t('app', 'Terrain') . "',
            data: groundPoints,
            borderColor: 'brown',
            fill: false,
            tension: 0.1,
            pointRadius: 0,
            hitRadius: 10,
            pointHoverRadius: 10,
          }
    ]
  },
  options: {
    onClick: (e) => {
        const points = myChart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
            if (points.length) {
                const firstPoint = points[0];
                const coords = myChart.data.datasets[firstPoint.datasetIndex].data[firstPoint.index].coords;
                pointSource.clear();
                const mapCoordinates = ol.proj.fromLonLat(coords);

                const pointFeature = new ol.Feature({
                    geometry: new ol.geom.Point(mapCoordinates)
                });

                pointSource.addFeature(pointFeature);

                map.getView().animate({
                    center: ol.proj.fromLonLat(coords),
                    duration: 1000
                });
                const timestamp = myChart.data.labels[firstPoint.index];
                const eventIndex = rawEventIndexByTimestamp[timestamp];

                if (eventIndex !== undefined) {
                    showRawEvent(eventIndex);
                }
            }
    },
    responsive: true,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    plugins: {
                zoom: {
                    pan: {
                        enabled: true,
                        mode: 'x',
                    },
                    zoom: {
                        wheel: { enabled: true },
                        pinch: { enabled: true },
                        mode: 'x',
                    }
                }
            },
    scales: {
      y: {
        title: {
          display: true,
          text: 'Altitude'
        }
      },
      x: {
        title: {
          display: true,
          text: 'Time'
        }
      }
    }
  }
});

function triggerChartPointByTimestamp(timestamp) {
    const labelIndex = labels.indexOf(timestamp);
    if (labelIndex === -1) return;

    myChart.setActiveElements([{ datasetIndex: 0, index: labelIndex }]);
    myChart.tooltip.setActiveElements([{ datasetIndex: 0, index: labelIndex }], { x: 0, y: 0 });
    myChart.update();
}

showRawEvent(0);

const mapLayers = map.getLayers().getArray();
const osmLayer = mapLayers[0];
const ifrLayer = mapLayers[1];

function setActiveMapBtn(activeId) {
    const btns = { mapStyleOSM: osmLayer, mapStyleIFR: ifrLayer };
    Object.entries(btns).forEach(([id, layer]) => {
        const btn = document.getElementById(id);
        const active = id === activeId;
        layer.setVisible(active);
        btn.style.background    = active ? 'var(--brand)'    : 'var(--bg-white)';
        btn.style.color         = active ? 'var(--bg-white)' : 'var(--brand)';
        btn.style.borderColor   = active ? 'var(--brand-dark)' : 'var(--brand)';
    });
}
document.getElementById('mapStyleOSM').addEventListener('click', () => setActiveMapBtn('mapStyleOSM'));
document.getElementById('mapStyleIFR').addEventListener('click', () => setActiveMapBtn('mapStyleIFR'));
");
?>