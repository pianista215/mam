<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

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
    <div id="map" style="width: 100%; height: 600px;"></div>
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

        rwyFeatures.push(new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.fromLonLat([end1.lon, end1.lat])),
            zone: 'threshold', label: end1.designator
        }));
        rwyFeatures.push(new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.fromLonLat([end2.lon, end2.lat])),
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
                return new ol.style.Style({
                    image: new ol.style.Circle({
                        radius: 6,
                        fill: new ol.style.Fill({ color: '#4caf50' }),
                        stroke: new ol.style.Stroke({ color: '#1b5e20', width: 2 })
                    }),
                    text: new ol.style.Text({
                        text: feature.get('label'),
                        font: 'bold 12px sans-serif',
                        offsetY: -15,
                        fill: new ol.style.Fill({ color: '#1b5e20' }),
                        stroke: new ol.style.Stroke({ color: '#fff', width: 3 })
                    })
                });
            }
            return rwyStyles[zone];
        }
    });

    runwayLayers.push(rwyLayer);
}

const map = new ol.Map({
    target: 'map',
    layers: [
        new ol.layer.Tile({
            source: new ol.source.OSM()
        }),
        ...runwayLayers,
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
            label: 'Plane',
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
            label: 'Terrain',
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
");
?>