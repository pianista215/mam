<?php

use app\rbac\constants\Permissions;
use yii\helpers\Html;
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

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Airports'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
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

    <div class="airport-map mt-4">
        <h4><?=Yii::t('app', 'Airport Location')?></h4>
        <div id="map" style="width: 100%; height: 500px;"></div>
    </div>

    <?php
    $lat = $model->latitude;
    $lon = $model->longitude;

    $this->registerJs("
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

        const map = new ol.Map({
            target: 'map',
            layers: [
                new ol.layer.Tile({
                    source: new ol.source.OSM()
                }),
                vectorLayer
            ],
            view: new ol.View({
                center: airportCoord,
                zoom: 12
            })
        });
    ");
    ?>

</div>
