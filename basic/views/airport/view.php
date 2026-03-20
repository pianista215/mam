<?php

use app\models\Navaid;
use app\rbac\constants\Permissions;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Airport $model */

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/ol@v10.6.0/dist/ol.js',
    ['position' => \yii\web\View::POS_HEAD]
);
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/ol@v10.6.0/ol.css',
    ['rel' => 'stylesheet']
);
\app\assets\NavaidMapAsset::register($this);

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Airports'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$runways = $model->getRunways()->with('runwayEnds')->all();
$runwayData = [];
foreach ($runways as $runway) {
    $ends = $runway->runwayEnds;
    if (count($ends) === 2) {
        $runwayData[] = [
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
$runwayDataJson = Json::encode($runwayData);
?>
<div class="airport-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if(Yii::$app->user->can(Permissions::AIRPORT_CRUD)) : ?>
    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'icao_code',
            'name',
            'latitude',
            'longitude',
            'city',
            'country.name',
        ],
    ]) ?>

    <?php if (!empty($runways)): ?>
    <div class="airport-runways mt-4">
        <h4><?= Yii::t('app', 'Runways') ?></h4>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th><?= Yii::t('app', 'Designator') ?></th>
                    <th><?= Yii::t('app', 'Heading') ?> (&deg;)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($runways as $runway): ?>
                    <?php foreach ($runway->runwayEnds as $end): ?>
                        <tr>
                            <td><?= Html::encode($end->designator) ?></td>
                            <td><?= round($end->true_heading_deg) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php
    $navaids = Navaid::find()
        ->with('navPoint')
        ->where(['airport_icao' => $model->icao_code])
        ->orderBy(['nav_point_id' => SORT_ASC])
        ->all();
    ?>
    <?php if (!empty($navaids)): ?>
    <div class="airport-navaids mt-4">
        <h4><?= Yii::t('app', 'Navaids') ?></h4>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th><?= Yii::t('app', 'Type') ?></th>
                    <th><?= Yii::t('app', 'Identifier') ?></th>
                    <th><?= Yii::t('app', 'Frequency') ?></th>
                    <th><?= Yii::t('app', 'Runway') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($navaids as $navaid): ?>
                <tr>
                    <td><?= Html::encode($navaid->navPoint->point_type) ?></td>
                    <td><strong><?= Html::encode($navaid->navPoint->identifier) ?></strong></td>
                    <td><?= Html::encode($navaid->frequency) ?></td>
                    <td><?= Html::encode($navaid->runway_designator ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="airport-map mt-4">
        <h4><?=Yii::t('app', 'Airport Location')?></h4>
        <div id="map" style="width: 100%; height: 500px;"></div>
    </div>

    <?php
    $lat = $model->latitude;
    $lon = $model->longitude;

    $airportNavPointsData = [];
    foreach ($navaids as $navaid) {
        $airportNavPointsData[] = [
            'lat'        => (float)$navaid->navPoint->latitude,
            'lon'        => (float)$navaid->navPoint->longitude,
            'identifier' => $navaid->navPoint->identifier,
            'name'       => $navaid->navPoint->name,
            'point_type' => $navaid->navPoint->point_type,
            'frequency'  => $navaid->frequency,
        ];
    }
    $airportNavPointsJson = Json::encode($airportNavPointsData);

    $this->registerJs("
        const runwayData = $runwayDataJson;
        const airportCoord = ol.proj.fromLonLat([$lon, $lat]);

        const pointFeature = new ol.Feature({
            geometry: new ol.geom.Point(airportCoord)
        });
        pointFeature.setStyle(new ol.style.Style({
            image: new ol.style.Circle({
                radius: 7,
                fill: new ol.style.Fill({ color: 'rgba(255, 0, 0, 0.7)' }),
                stroke: new ol.style.Stroke({ color: 'red', width: 2 })
            })
        }));

        const circleFeature = new ol.Feature({
            geometry: new ol.geom.Circle(airportCoord, 8000) // 8 km
        });
        circleFeature.setStyle(new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: 'rgba(0, 0, 255, 0.6)',
                width: 2
            }),
            fill: new ol.style.Fill({
                color: 'rgba(0, 0, 255, 0.1)'
            })
        }));

        const vectorSource = new ol.source.Vector({
            features: [pointFeature, circleFeature]
        });

        const vectorLayer = new ol.layer.Vector({
            source: vectorSource
        });

        const layers = [
            new ol.layer.Tile({
                source: new ol.source.OSM()
            }),
            vectorLayer
        ];

        if (runwayData.length > 0) {
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

            const runwayFeatures = [];

            runwayData.forEach(function(rwy) {
                const end1 = rwy.end1;
                const end2 = rwy.end2;
                const halfWidth = rwy.width / 2;

                const bearing = calculateBearing(end1.lat, end1.lon, end2.lat, end2.lon);
                const reverseBearing = (bearing + 180) % 360;
                const perpLeft = ((bearing - 90) % 360 + 360) % 360;
                const perpRight = (bearing + 90) % 360;

                // Stopway end1
                if (end1.stopway > 0) {
                    const p1 = offsetPoint(end1.lat, end1.lon, reverseBearing, end1.stopway);
                    const p1L = offsetPoint(p1[0], p1[1], perpLeft, halfWidth);
                    const p1R = offsetPoint(p1[0], p1[1], perpRight, halfWidth);
                    const t1L = offsetPoint(end1.lat, end1.lon, perpLeft, halfWidth);
                    const t1R = offsetPoint(end1.lat, end1.lon, perpRight, halfWidth);
                    runwayFeatures.push(new ol.Feature({
                        geometry: new ol.geom.Polygon(makePolygonCoords([p1L, p1R, t1R, t1L, p1L])),
                        zone: 'stopway',
                        label: 'Stopway ' + end1.designator
                    }));
                }

                // Stopway end2
                if (end2.stopway > 0) {
                    const p2 = offsetPoint(end2.lat, end2.lon, bearing, end2.stopway);
                    const p2L = offsetPoint(p2[0], p2[1], perpLeft, halfWidth);
                    const p2R = offsetPoint(p2[0], p2[1], perpRight, halfWidth);
                    const t2L = offsetPoint(end2.lat, end2.lon, perpLeft, halfWidth);
                    const t2R = offsetPoint(end2.lat, end2.lon, perpRight, halfWidth);
                    runwayFeatures.push(new ol.Feature({
                        geometry: new ol.geom.Polygon(makePolygonCoords([t2L, t2R, p2R, p2L, t2L])),
                        zone: 'stopway',
                        label: 'Stopway ' + end2.designator
                    }));
                }

                // Displaced threshold end1
                if (end1.displaced_threshold > 0) {
                    const dt1 = offsetPoint(end1.lat, end1.lon, bearing, end1.displaced_threshold);
                    const e1L = offsetPoint(end1.lat, end1.lon, perpLeft, halfWidth);
                    const e1R = offsetPoint(end1.lat, end1.lon, perpRight, halfWidth);
                    const dt1L = offsetPoint(dt1[0], dt1[1], perpLeft, halfWidth);
                    const dt1R = offsetPoint(dt1[0], dt1[1], perpRight, halfWidth);
                    runwayFeatures.push(new ol.Feature({
                        geometry: new ol.geom.Polygon(makePolygonCoords([e1L, e1R, dt1R, dt1L, e1L])),
                        zone: 'displaced',
                        label: 'Displaced ' + end1.designator
                    }));
                }

                // Displaced threshold end2
                if (end2.displaced_threshold > 0) {
                    const dt2 = offsetPoint(end2.lat, end2.lon, reverseBearing, end2.displaced_threshold);
                    const e2L = offsetPoint(end2.lat, end2.lon, perpLeft, halfWidth);
                    const e2R = offsetPoint(end2.lat, end2.lon, perpRight, halfWidth);
                    const dt2L = offsetPoint(dt2[0], dt2[1], perpLeft, halfWidth);
                    const dt2R = offsetPoint(dt2[0], dt2[1], perpRight, halfWidth);
                    runwayFeatures.push(new ol.Feature({
                        geometry: new ol.geom.Polygon(makePolygonCoords([dt2L, dt2R, e2R, e2L, dt2L])),
                        zone: 'displaced',
                        label: 'Displaced ' + end2.designator
                    }));
                }

                // Main runway surface (between displaced thresholds)
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
                runwayFeatures.push(new ol.Feature({
                    geometry: new ol.geom.Polygon(makePolygonCoords([s1L, s1R, s2R, s2L, s1L])),
                    zone: 'runway',
                    label: rwy.designators
                }));

                // Threshold markers with labels
                const th1L = offsetPoint(end1.lat, end1.lon, perpLeft, halfWidth);
                const th1R = offsetPoint(end1.lat, end1.lon, perpRight, halfWidth);
                runwayFeatures.push(new ol.Feature({
                    geometry: new ol.geom.LineString([toMapCoord(th1L), toMapCoord(th1R)]),
                    zone: 'threshold',
                    label: end1.designator
                }));
                const th2L = offsetPoint(end2.lat, end2.lon, perpLeft, halfWidth);
                const th2R = offsetPoint(end2.lat, end2.lon, perpRight, halfWidth);
                runwayFeatures.push(new ol.Feature({
                    geometry: new ol.geom.LineString([toMapCoord(th2L), toMapCoord(th2R)]),
                    zone: 'threshold',
                    label: end2.designator
                }));
            });

            const runwayStyles = {
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

            const runwaySource = new ol.source.Vector({
                features: runwayFeatures
            });

            const runwayLayer = new ol.layer.Vector({
                source: runwaySource,
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
                    return runwayStyles[zone];
                }
            });

            layers.push(runwayLayer);
        }

        const airportNavPoints = " . $airportNavPointsJson . ";

        const NAV_SVG = {
            'VOR': [
                `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-12 -12 24 24' width='24' height='24'>` +
                `<polygon points='11,0 5.5,9.5 -5.5,9.5 -11,0 -5.5,-9.5 5.5,-9.5' fill='none' stroke='#2255ff' stroke-width='2'/>` +
                `<circle cx='0' cy='0' r='2.5' fill='#2255ff'/></svg>`, 24, 24],
            'NDB': [
                `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-11 -11 22 22' width='22' height='22'>` +
                `<circle cx='0' cy='0' r='10' fill='none' stroke='#ff8800' stroke-width='2'/>` +
                `<circle cx='0' cy='0' r='5.5' fill='none' stroke='#ff8800' stroke-width='1.5'/>` +
                `<circle cx='0' cy='0' r='2.5' fill='#ff8800'/></svg>`, 22, 22],
            'DME': [
                `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-10 -10 20 20' width='20' height='20'>` +
                `<rect x='-9' y='-9' width='18' height='18' fill='none' stroke='#00aacc' stroke-width='2'/>` +
                `<circle cx='0' cy='0' r='2.5' fill='#00aacc'/></svg>`, 20, 20],
            'ILS-LOC': [
                `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-11 -11 22 22' width='22' height='22'>` +
                `<polygon points='0,-10 10,0 0,10 -10,0' fill='none' stroke='#00cc44' stroke-width='2'/>` +
                `<circle cx='0' cy='0' r='2.5' fill='#00cc44'/></svg>`, 22, 22],
            'LOC': [
                `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-11 -11 22 22' width='22' height='22'>` +
                `<polygon points='0,-10 10,0 0,10 -10,0' fill='none' stroke='#00cc44' stroke-width='2'/>` +
                `<circle cx='0' cy='0' r='2.5' fill='#00cc44'/></svg>`, 22, 22],
        };
        const NAV_SVG_DEFAULT = [
            `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-10 -10 20 20' width='20' height='20'>` +
            `<polygon points='0,-9 8,7 -8,7' fill='none' stroke='#666666' stroke-width='1.8'/></svg>`, 20, 20];

        function makeNavStyle(np) {
            const [svgStr, , iconH] = NAV_SVG[np.point_type] || NAV_SVG_DEFAULT;
            const src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgStr);
            const baseOffsetY = Math.round(iconH / 2) + 9;
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
            if (np.frequency) {
                styles.push(new ol.style.Style({
                    text: new ol.style.Text({
                        text: np.frequency,
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

        const navFeatures = airportNavPoints.map(np => {
            const feature = new ol.Feature({
                geometry: new ol.geom.Point(ol.proj.fromLonLat([np.lon, np.lat]))
            });
            feature.setStyle(makeNavStyle(np));
            return feature;
        });
        layers.push(new ol.layer.Vector({
            source: new ol.source.Vector({ features: navFeatures })
        }));

        const map = new ol.Map({
            target: 'map',
            layers: layers,
            view: new ol.View({
                center: airportCoord,
                zoom: 12
            })
        });
    ");
    ?>

</div>
