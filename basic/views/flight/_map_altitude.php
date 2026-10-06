<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;
use app\assets\NavaidMapAsset;
use app\helpers\GeoUtils;
use app\models\NavPoint;
use app\models\AirwaySegment;

echo $this->render('@app/views/layouts/_openlayers');
NavaidMapAsset::register($this);

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

/** Returns the minimum distance (km) from a point to any recorded route point. */
$minDistToRouteKm = function(float $npLat, float $npLon) use ($routeCoords): float {
    $min = PHP_FLOAT_MAX;
    foreach ($routeCoords as $coord) {
        $d = GeoUtils::haversine($npLat, $npLon, $coord[1], $coord[0]);
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

/** @var array $eventMarkers markers prepared by FlightEventMarkers::fromReport() */
$eventMarkers = $eventMarkers ?? [];

// Presentation registry for event markers: adding a new marker type only needs an entry here
$eventMarkerTypes = [
    'flaps'     => ['color' => '#1e3a8a', 'label' => Yii::t('app', 'Flaps')],
    'gear'      => ['color' => '#6b21a8', 'label' => Yii::t('app', 'Landing gear')],
    'liftoff'   => ['color' => '#15803d', 'label' => Yii::t('app', 'Lift-off')],
    'touchdown' => ['color' => '#b91c1c', 'label' => Yii::t('app', 'Touchdown')],
    'autopilot' => ['color' => '#0f766e', 'colorOff' => '#c2410c', 'label' => Yii::t('app', 'Autopilot')],
];
$eventMarkerI18n = [
    'flaps'     => Yii::t('app', 'Flaps'),
    'gearDown'  => Yii::t('app', 'Gear Down'),
    'gearUp'    => Yii::t('app', 'Gear Up'),
    'liftoff'   => Yii::t('app', 'Lift-off'),
    'touchdown' => Yii::t('app', 'Touchdown'),
    'apOn'      => Yii::t('app', 'Autopilot engaged'),
    'apOff'     => Yii::t('app', 'Autopilot disengaged'),
    'events'    => Yii::t('app', 'events'),
];
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
.event-marker-popup {
    background: var(--bg-white);
    color: var(--text-dark);
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
    font-size: 12px;
    padding: 4px 8px;
    white-space: nowrap;
}
.event-marker-popup { padding: 4px 0; max-height: 220px; overflow-y: auto; }
.event-marker-popup .event-row {
    display: flex; align-items: center; gap: 6px;
    padding: 2px 10px; cursor: pointer;
}
.event-marker-popup .event-row:hover { background: rgba(0,0,0,.08); }
.event-marker-chip {
    display: inline-block; min-width: 34px; text-align: center;
    border-radius: 8px; color: #fff; font-weight: bold; font-size: 10px; padding: 0 4px;
}
.event-marker-time { color: #777; font-variant-numeric: tabular-nums; }
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
            <div style="font-weight: bold; margin-bottom: 2px; color: var(--text-dark);"><?= Yii::t('app', 'Nav Points') ?></div>
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
            <?php if (!empty($eventMarkers)): ?>
            <hr style="margin: 3px 0;">
            <div style="font-weight: bold; margin-bottom: 2px; color: var(--text-dark);"><?= Yii::t('app', 'Events') ?></div>
            <?php foreach ($eventMarkerTypes as $type => $def):
                $swatch = isset($def['colorOff'])
                    ? "linear-gradient(90deg, {$def['color']} 50%, {$def['colorOff']} 50%)"
                    : $def['color'];
            ?>
            <label style="display:flex; align-items:center; gap:5px; cursor:pointer; color:var(--text-dark);">
                <input type="checkbox" class="event-filter-check" data-type="<?= $type ?>" checked>
                <span style="display:inline-block; width:14px; height:10px; border-radius:5px;
                             background:<?= $swatch ?>; flex-shrink:0;"></span>
                <?= Html::encode($def['label']) ?>
            </label>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div id="map" style="width: 100%; height: 600px;"></div>
        <div id="eventMarkerPopup" class="event-marker-popup" style="display:none;"></div>
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

// Selected event index in labels; drawn by selectedEventPlugin so hovering does not clear it
let selectedChartIndex = null;
const selectedEventPlugin = {
    id: 'selectedEvent',
    afterDatasetsDraw(chart) {
        if (selectedChartIndex === null) return;
        const xScale = chart.scales.x;
        if (selectedChartIndex < xScale.min || selectedChartIndex > xScale.max) return;
        const point = chart.getDatasetMeta(0).data[selectedChartIndex];
        if (!point) return;
        const area = chart.chartArea;
        const c = chart.ctx;
        c.save();
        c.beginPath();
        c.setLineDash([4, 4]);
        c.moveTo(point.x, area.top);
        c.lineTo(point.x, area.bottom);
        c.lineWidth = 1.5;
        c.strokeStyle = 'rgba(255, 0, 0, 0.8)';
        c.stroke();
        c.setLineDash([]);
        c.beginPath();
        c.arc(point.x, point.y, 6, 0, 2 * Math.PI);
        c.fillStyle = 'rgba(255, 0, 0, 0.7)';
        c.fill();
        c.lineWidth = 2;
        c.strokeStyle = 'rgba(255, 0, 0, 1)';
        c.stroke();
        c.restore();
    }
};

const ctx = document.getElementById('altitudeChart').getContext('2d');
const myChart = new Chart(ctx, {
  type: 'line',
  plugins: [selectedEventPlugin],
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
        // Nearest sample on the x axis, no need to hit the line exactly
        const points = myChart.getElementsAtEventForMode(e, 'index', { intersect: false }, true);
        if (points.length) {
            showRawEventByTimestamp(myChart.data.labels[points[0].index]);
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

// Index of the label closest in time (labels are sorted timestamps)
function nearestLabelIndex(timestamp) {
    if (labels.length === 0) return -1;
    let lo = 0, hi = labels.length - 1;
    while (lo < hi) {
        const mid = (lo + hi) >> 1;
        if (labels[mid] < timestamp) lo = mid + 1; else hi = mid;
    }
    const t = ts => Date.parse(ts.replace(' ', 'T'));
    if (lo > 0 && t(timestamp) - t(labels[lo - 1]) < t(labels[lo]) - t(timestamp)) {
        return lo - 1;
    }
    return lo;
}

function triggerChartPointByTimestamp(timestamp) {
    const labelIndex = nearestLabelIndex(timestamp);
    if (labelIndex === -1) return;
    selectedChartIndex = labelIndex;

    // When zoomed in, pan the chart so the selected point is visible, keeping the zoom level
    const xScale = myChart.scales.x;
    if (xScale && (labelIndex < xScale.min || labelIndex > xScale.max)) {
        const range = xScale.max - xScale.min;
        const min = Math.max(0, Math.min(labels.length - 1 - range, Math.round(labelIndex - range / 2)));
        myChart.options.scales.x.min = min;
        myChart.options.scales.x.max = min + range;
    }
    myChart.update('none');
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
<?php
if (!empty($eventMarkers)) {
    $this->registerJs(
        'const eventMarkers = ' . Json::encode($eventMarkers) . ";\n" .
        'const EVENT_MARKER_TYPES = ' . Json::encode($eventMarkerTypes) . ";\n" .
        'const eventMarkerI18n = ' . Json::encode($eventMarkerI18n) . ";\n"
    );
    $this->registerJs(<<<'JS'
// ---- Event markers (flaps, gear, lift-off, touchdown, autopilot) ----
const eventTypeVisible = {};
Object.keys(EVENT_MARKER_TYPES).forEach(t => { eventTypeVisible[t] = true; });

const LABEL_FONT = 'bold 11px Arial, sans-serif';
const ICON_FONT = '900 11px "Font Awesome 6 Free"';
// Font Awesome glyphs (already loaded by the main layout) drawn before the label
const MARKER_ICONS = {
    liftoff:   { glyph: '\uf5b0', css: 'fa-plane-departure' },
    touchdown: { glyph: '\uf5af', css: 'fa-plane-arrival' },
};
const CLUSTER_DISTANCE = 55;
const CLUSTER_COLOR = '#212529';
const MAX_STACK = 3;

function markerColor(m) {
    const def = EVENT_MARKER_TYPES[m.type] || { color: '#333' };
    if (m.type === 'autopilot' && m.to !== 'On') return def.colorOff;
    return def.color;
}

// Readable label drawn on the map
function markerLabel(m) {
    switch (m.type) {
        case 'flaps':     return eventMarkerI18n.flaps + ' ' + m.to + '%';
        case 'gear':      return m.to === 'Down' ? eventMarkerI18n.gearDown : eventMarkerI18n.gearUp;
        case 'liftoff':   return eventMarkerI18n.liftoff;
        case 'touchdown': return eventMarkerI18n.touchdown + (m.extra !== null ? ' ' + m.extra + ' fpm' : '');
        case 'autopilot': return m.to === 'On' ? 'AP ON' : 'AP OFF';
    }
    return m.short;
}

// Full description used by the cluster list
function markerLongText(m) {
    switch (m.type) {
        case 'flaps':     return eventMarkerI18n.flaps + ' ' + m.from + '% → ' + m.to + '%';
        case 'autopilot': return m.to === 'On' ? eventMarkerI18n.apOn : eventMarkerI18n.apOff;
    }
    return markerLabel(m);
}

function timeOf(ts) { return ts.substring(11); }

function htmlEscape(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ---- Canvas rendering ----
const DPR = window.devicePixelRatio || 1;
const PILL_H = 20, PILL_PAD = 7, ICON_GAP = 4, STACK_GAP = 2;
const measureCtx = document.createElement('canvas').getContext('2d');

function measure(font, text) {
    measureCtx.font = font;
    return Math.ceil(measureCtx.measureText(text).width);
}

// item: { label, icon, color }
function pillWidth(item) {
    const iconW = item.icon ? measure(ICON_FONT, item.icon) + ICON_GAP : 0;
    return Math.max(PILL_H, PILL_PAD * 2 + iconW + measure(LABEL_FONT, item.label));
}

function stackSize(items) {
    return {
        w: Math.max(...items.map(pillWidth)),
        h: items.length * PILL_H + (items.length - 1) * STACK_GAP,
    };
}

function roundRectPath(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

// Draws the items as a vertical stack of equal-width pills centered on (cx, top).
// tail (optional): { index, tip: [x, y], base: [x, y], half } merges a pointer into that pill.
function drawStack(ctx, cx, top, items, tail) {
    const w = stackSize(items).w;
    const x = cx - w / 2;
    const yOf = i => top + i * (PILL_H + STACK_GAP);
    const tailPath = () => {
        const [tx, ty] = tail.tip, [bx, by] = tail.base;
        const len = Math.hypot(bx - tx, by - ty) || 1;
        const nx = -(by - ty) / len * tail.half, ny = (bx - tx) / len * tail.half;
        ctx.beginPath();
        ctx.moveTo(tx, ty);
        ctx.lineTo(bx + nx, by + ny);
        ctx.lineTo(bx - nx, by - ny);
        ctx.closePath();
    };

    // Pass 1: white outline (with a soft shadow) of every shape
    ctx.save();
    ctx.shadowColor = 'rgba(0,0,0,0.35)';
    ctx.shadowBlur = 3;
    ctx.shadowOffsetY = 1;
    ctx.lineWidth = 3;
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#fff';
    items.forEach((item, i) => {
        roundRectPath(ctx, x, yOf(i), w, PILL_H, PILL_H / 2);
        ctx.stroke();
    });
    if (tail) {
        tailPath();
        ctx.stroke();
    }
    ctx.restore();

    // Pass 2: fills cover the inner half of the outlines, so pill + tail read as a single shape
    items.forEach((item, i) => {
        ctx.fillStyle = item.color;
        roundRectPath(ctx, x, yOf(i), w, PILL_H, PILL_H / 2);
        ctx.fill();
        if (tail && tail.index === i) {
            tailPath();
            ctx.fill();
        }
    });

    // Pass 3: icon + label
    ctx.fillStyle = '#fff';
    ctx.textBaseline = 'middle';
    ctx.textAlign = 'left';
    items.forEach((item, i) => {
        const cy = yOf(i) + PILL_H / 2 + 0.5;
        let tx = x + PILL_PAD;
        if (item.icon) {
            ctx.font = ICON_FONT;
            ctx.fillText(item.icon, tx, cy);
            tx += measure(ICON_FONT, item.icon) + ICON_GAP;
        }
        ctx.font = LABEL_FONT;
        ctx.fillText(item.label, tx, cy);
    });
}

let styleCache = {};
function canvasStyle(key, width, height, anchor, draw) {
    if (!styleCache[key]) {
        const canvas = document.createElement('canvas');
        canvas.width = Math.ceil(width * DPR);
        canvas.height = Math.ceil(height * DPR);
        const ctx = canvas.getContext('2d');
        ctx.scale(DPR, DPR);
        draw(ctx);
        styleCache[key] = new ol.style.Style({
            image: new ol.style.Icon({
                img: canvas,
                anchor: [anchor[0] * DPR, anchor[1] * DPR],
                anchorXUnits: 'pixels',
                anchorYUnits: 'pixels',
                scale: 1 / DPR,
            }),
        });
    }
    return styleCache[key];
}

// Speech-bubble labels placed beside the track, perpendicular to the aircraft heading, so the
// route stays visible; the tail tip marks the exact position of the event
function bubbleStyle(items, heading, side) {
    const angle = Math.round(((heading || 0) + 90 * side + 360) % 360);
    const { w, h } = stackSize(items), m = 6, tailLen = 10;
    const rad = angle * Math.PI / 180;
    const dx = Math.sin(rad), dy = -Math.cos(rad);
    // Place the labels so that the point where the tail ray leaves the label box is tailLen away
    const halfAlongRay = Math.min(
        Math.abs(dx) > 1e-6 ? w / 2 / Math.abs(dx) : Infinity,
        Math.abs(dy) > 1e-6 ? h / 2 / Math.abs(dy) : Infinity
    );
    const reach = tailLen + halfAlongRay;
    const px = dx * reach, py = dy * reach;
    const minX = Math.min(-5, px - w / 2) - m, maxX = Math.max(5, px + w / 2) + m;
    const minY = Math.min(-5, py - h / 2) - m, maxY = Math.max(5, py + h / 2) + m;
    const ox = -minX, oy = -minY;
    // The tail belongs to the pill it touches
    const hitY = dy * tailLen - (py - h / 2);
    const hitIndex = Math.min(items.length - 1, Math.max(0, Math.floor(hitY / (PILL_H + STACK_GAP))));
    const key = angle + '|' + JSON.stringify(items);
    return canvasStyle(key, maxX - minX, maxY - minY, [ox, oy], ctx => {
        // Base well inside the pill so the join is hidden by the pill fill
        const inset = 6;
        drawStack(ctx, ox + px, oy + py - h / 2, items, {
            index: hitIndex,
            tip: [ox, oy],
            base: [ox + dx * (tailLen + inset), oy + dy * (tailLen + inset)],
            half: 5,
        });
    });
}

function markerItem(m) {
    return { label: markerLabel(m), icon: MARKER_ICONS[m.type]?.glyph || null, color: markerColor(m) };
}

function eventMarkerStyle(feature) {
    const members = feature.get('features');
    const markers = sortedMembers(members);
    // Clusters show their events as a readable stack; long ones are summarized
    let items = markers.map(markerItem);
    if (items.length > MAX_STACK) {
        items = items.slice(0, MAX_STACK - 1);
        items.push({ label: '+' + (markers.length - MAX_STACK + 1) + ' ' + eventMarkerI18n.events, icon: null, color: CLUSTER_COLOR });
    }
    return bubbleStyle(items, markers[0].heading, members[0].get('side'));
}

// ---- Layer ----
const eventMarkerSource = new ol.source.Vector({
    features: eventMarkers.map((m, i) => {
        const f = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([m.lon, m.lat])) });
        f.set('marker', m);
        // Alternate label side so that consecutive labels do not overlap
        f.set('side', i % 2 === 0 ? 1 : -1);
        return f;
    }),
});

const eventClusterSource = new ol.source.Cluster({
    distance: CLUSTER_DISTANCE,
    source: eventMarkerSource,
    // Hidden types are excluded from clustering
    geometryFunction: f => eventTypeVisible[f.get('marker').type] ? f.getGeometry() : null,
});

const eventMarkerLayer = new ol.layer.Vector({
    source: eventClusterSource,
    style: eventMarkerStyle,
    zIndex: 100,
});
map.addLayer(eventMarkerLayer);

// Icon glyphs need the webfont: redraw once it is available
if (document.fonts && document.fonts.load) {
    document.fonts.load(ICON_FONT, '\uf5b0').then(() => {
        styleCache = {};
        eventMarkerLayer.changed();
    }).catch(() => { /* labels still render without icons */ });
}

document.querySelectorAll('.event-filter-check').forEach(cb => {
    cb.addEventListener('change', function() {
        eventTypeVisible[this.dataset.type] = this.checked;
        eventMarkerSource.changed();
        hideEventPopup();
    });
});

// ---- Cluster popup ----
const popupEl = document.getElementById('eventMarkerPopup');
const popupOverlay = new ol.Overlay({ element: popupEl, offset: [0, -16], positioning: 'bottom-center', stopEvent: true });
map.addOverlay(popupOverlay);

function hideEventPopup() {
    popupEl.style.display = 'none';
    popupOverlay.setPosition(undefined);
}

function sortedMembers(members) {
    return members.map(f => f.get('marker')).sort((a, b) => a.timestamp.localeCompare(b.timestamp));
}

function chipHtml(m) {
    const icon = MARKER_ICONS[m.type] ? '<i class="fa-solid ' + MARKER_ICONS[m.type].css + '"></i> ' : '';
    return '<span class="event-marker-chip" style="background:' + markerColor(m) + '">' + icon
        + htmlEscape(markerLabel(m)) + '</span>';
}

function markerDetailHtml(m) {
    const alt = m.altitude !== null ? ' · ' + m.altitude + ' ft' : '';
    const detail = markerLongText(m) !== markerLabel(m) ? ' ' + htmlEscape(markerLongText(m)) : '';
    return chipHtml(m) + detail + ' <span class="event-marker-time">' + timeOf(m.timestamp) + alt + '</span>';
}

function eventClusterAtPixel(pixel) {
    return map.forEachFeatureAtPixel(pixel, f => f, { layerFilter: l => l === eventMarkerLayer, hitTolerance: 3 });
}

// ---- Click on the track: jump to the nearest recorded event (like the altitude chart) ----
const TRACK_HIT_PX = 10;
const trackPoints = [];
rawEvents.forEach((ev, idx) => {
    const lat = ev.values?.Latitude, lon = ev.values?.Longitude;
    if (lat === undefined || lon === undefined) return;
    trackPoints.push({ idx: idx, coord: ol.proj.fromLonLat([parseFloat(lon), parseFloat(lat)]) });
});

function nearestTrackEvent(pixel) {
    let best = null, bestDist = TRACK_HIT_PX * TRACK_HIT_PX;
    trackPoints.forEach(p => {
        const px = map.getPixelFromCoordinate(p.coord);
        if (!px) return;
        const d = (px[0] - pixel[0]) ** 2 + (px[1] - pixel[1]) ** 2;
        if (d <= bestDist) { bestDist = d; best = p; }
    });
    return best;
}

// Only a pointer cursor on hover: markers and track are clickable, no hover popup
map.on('pointermove', evt => {
    if (evt.dragging) return;
    const clickable = eventClusterAtPixel(evt.pixel) || nearestTrackEvent(evt.pixel) !== null;
    map.getTargetElement().style.cursor = clickable ? 'pointer' : '';
});

function showEventPopup(coordinate, markers) {
    popupEl.innerHTML = markers.map(m =>
        '<div class="event-row" data-ts="' + htmlEscape(m.timestamp) + '">' + markerDetailHtml(m) + '</div>'
    ).join('');
    popupEl.querySelectorAll('.event-row').forEach(row => {
        row.addEventListener('click', () => {
            hideEventPopup();
            showRawEventByTimestamp(row.dataset.ts);
        });
    });
    popupEl.style.display = '';
    popupOverlay.setPosition(coordinate);
}

map.on('singleclick', evt => {
    const cluster = eventClusterAtPixel(evt.pixel);
    if (!cluster) {
        hideEventPopup();
        const point = nearestTrackEvent(evt.pixel);
        if (point) showRawEvent(point.idx);
        return;
    }
    const members = cluster.get('features');
    if (members.length === 1) {
        hideEventPopup();
        showRawEventByTimestamp(members[0].get('marker').timestamp);
        return;
    }
    // Zoom into the cluster when that splits it; otherwise list its events
    const view = map.getView();
    const extent = ol.extent.boundingExtent(members.map(f => f.getGeometry().getCoordinates()));
    const size = map.getSize();
    const padding = 120;
    const targetZoom = view.getZoomForResolution(
        view.getResolutionForExtent(extent, [Math.max(1, size[0] - 2 * padding), Math.max(1, size[1] - 2 * padding)])
    );
    if ((ol.extent.getWidth(extent) < 1 && ol.extent.getHeight(extent) < 1) || targetZoom - view.getZoom() < 0.5) {
        showEventPopup(cluster.getGeometry().getCoordinates(), sortedMembers(members));
    } else {
        hideEventPopup();
        view.fit(extent, { padding: [padding, padding, padding, padding], duration: 500, maxZoom: 18 });
    }
});
JS
    );
}
?>
