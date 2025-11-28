<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/ol@v10.6.0/dist/ol.js',
    ['position' => \yii\web\View::POS_HEAD]
);
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/ol@v10.6.0/ol.css',
    ['rel' => 'stylesheet']
);

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
            'phase' => $phase->flightPhaseType->name,
            'color' => $colors[$phase->flightPhaseType->code],
            'coordinates' => $coordinates,
        ];
    }

    // altitude
    if (!empty($pointsAltitude)) {
        $datasets[] = [
            'label' => $phase->flightPhaseType->name . ' (Avión)',
            'data' => $pointsAltitude,
            'borderColor' => $colors[$phase->flightPhaseType->code],
            'fill' => false,
            'tension' => 0.1,
        ];
    }
    if (!empty($pointsGround)) {
        $datasets[] = [
            'label' => $phase->flightPhaseType->name . ' (Terreno)',
            'data' => $pointsGround,
            'borderColor' => $colors[$phase->flightPhaseType->code],
            'borderDash' => [5, 5], // línea discontinua
            'fill' => false,
            'tension' => 0.1,
        ];
    }
}
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
            <div class="col-md-4 timeline-item">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($phase->flightPhaseType->name) ?></h5>
                            <div class="d-flex align-items-center">
                                <small class="text-muted me-2">
                                    <?= date('H:i', strtotime($phase->start)) ?> - <?= date('H:i', strtotime($phase->end)) ?>
                                </small>
                                <span class="phase-color" style="background-color: <?= $colors[$phase->flightPhaseType->code] ?>;"></span>
                            </div>
                        </div>
                        <?php foreach ($phase->flightPhaseMetrics as $metric): ?>
                            <p class="card-text mb-1">
                                <?= htmlspecialchars($metric->metricType->name) . ' : ' . htmlspecialchars($metric->value) ?>
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

function showRawEvent(index) {
    if (index < 0 || index >= rawEvents.length) return;
    currentEventIndex = index;

    const ev = rawEvents[currentEventIndex];
    document.getElementById('rawEventViewer').textContent =
        JSON.stringify(ev, null, 2);

    triggerChartPointByTimestamp(ev.timestamp);
}

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

const map = new ol.Map({
    target: 'map',
    layers: [
        new ol.layer.Tile({
            source: new ol.source.OSM()
        }),
        pointLayer,
        ...layers
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

    const firstPoint = { datasetIndex: 0, index: labelIndex };
    const coords = myChart.data.datasets[firstPoint.datasetIndex].data[firstPoint.index].coords;
    if (coords) {
        pointSource.clear();
        const mapCoordinates = ol.proj.fromLonLat(coords);
        pointSource.addFeature(new ol.Feature(new ol.geom.Point(mapCoordinates)));
        map.getView().animate({ center: mapCoordinates, duration: 500 });
    }
}

showRawEvent(0);
");
?>