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

?>

<div class="container">

    <div id="map" style="width: 100%; height: 400px;"></div>

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
foreach ($report->flightPhases as $phase) {
    $coordinates = [];
    foreach ($phase->flightEvents as $event) {
        $lat = null;
        $lon = null;
        foreach($event->flightEventDatas as $data) {
            $code = $data->attribute0->code;
            if($code == 'Latitude'){
                $lat = (float)$data->value;
            } else if($code == 'Longitude'){
                $lon = (float)$data->value;
            }
            if($lat != null && $lon != null) break;
        }
        if ($lat !== null && $lon !== null) {
            $coordinates[] = [$lon, $lat];
        }
    }

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
?>