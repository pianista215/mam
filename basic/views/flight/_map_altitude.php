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

<div class="container">

    <div id="map" style="width: 100%; height: 400px;"></div>
    <canvas id="altitudeChart" width="800" height="400"></canvas>

</div>

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
    'unknown' => '#888888'
);

$segments = [];
$altitudePoints = [];
$groundPoints = [];
$phaseIntervals = [];

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
            $altitudePoints[] = ['x' => $timestamp, 'y' => $altitude];
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

$this->registerJs("
    const segments = " . json_encode($segments) . ";

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

    const map = new ol.Map({
        target: 'map',
        layers: [
            new ol.layer.Tile({
                source: new ol.source.OSM()
            }),
            ...layers
        ],
        view: new ol.View({
            center: ol.proj.fromLonLat(segments[0].coordinates[0]),
            zoom: 15
        })
    });
");

$this->registerJs("

const phaseIntervals = " . json_encode($phaseIntervals) . ";

const labels = " . json_encode($labels) . ";
const altitudePoints = " . json_encode($altitudePoints) . ";
const groundPoints = " . json_encode($groundPoints) . ";

function getPhaseColor(ts) {
  console.log(ts);
  for (const phase of phaseIntervals) {
    if (ts >= phase.start && ts <= phase.end) {
      return phase.color;
    }
  }
  return '#888888'; // fallback
}

const ctx = document.getElementById('altitudeChart').getContext('2d');
new Chart(ctx, {
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
          },
          {
            label: 'Terrain',
            data: groundPoints,
            borderColor: 'brown',
            fill: false,
            tension: 0.1,
            pointRadius: 0,
          }
    ]
  },
  options: {
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
");
?>