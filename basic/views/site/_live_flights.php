<?php
/**
 * Live flights map and table
 * @var \yii\web\View $this
 * @var \app\models\LiveFlightPosition[] $liveFlights
 */

use yii\helpers\Html;

if (empty($liveFlights)) {
    return;
}

echo $this->render('@app/views/layouts/_openlayers');
?>

<div class="live-flights-section mb-4">
    <h4 class="mb-3"><?= Yii::t('app', 'Live Flights') ?></h4>

    <div class="live-flights-table mb-3">
        <?php foreach ($liveFlights as $position): ?>
            <?php
                $fpl = $position->submittedFlightPlan;
                $pilot = $fpl->pilot;
                $entity = $fpl->getEntity();
                $departure = $entity ? $entity->departure : '-';
                $arrival = $entity ? $entity->arrival : '-';
            ?>
            <div class="live-flight-row d-flex flex-wrap align-items-center p-2 border-bottom"
                 data-flight-id="<?= $position->submitted_flight_plan_id ?>"
                 style="cursor: pointer;">
                <div class="col-12 col-md-4 mb-1 mb-md-0">
                    <strong><?= Html::encode($pilot->license) ?></strong>
                    <span class="text-muted d-none d-md-inline"> - </span>
                    <span class="d-block d-md-inline"><?= Html::encode($pilot->fullname) ?></span>
                </div>
                <div class="col-6 col-md-4 text-center">
                    <span class="badge bg-secondary"><?= Html::encode($departure) ?></span>
                    <span class="mx-1">&rarr;</span>
                    <span class="badge bg-secondary"><?= Html::encode($arrival) ?></span>
                </div>
                <div class="col-6 col-md-4 text-end text-muted small">
                    <?= Yii::t('app', 'Altitude') ?>: <?= number_format($position->altitude) ?> ft
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="liveFlightsMap" style="width: 100%; height: 400px; border-radius: 8px;"></div>

    <div id="flightInfoPopup" class="card shadow-sm" style="display: none; position: absolute; z-index: 1000; min-width: 200px;">
        <div class="card-body p-2">
            <div id="popupContent"></div>
        </div>
    </div>
</div>

<style>
.live-flight-row:hover {
    background-color: #f8f9fa;
}
.live-flight-row.active {
    background-color: #e3f2fd;
}
</style>

<?php
$flightsData = [];
foreach ($liveFlights as $position) {
    $fpl = $position->submittedFlightPlan;
    $pilot = $fpl->pilot;
    $entity = $fpl->getEntity();

    $flightsData[] = [
        'id' => $position->submitted_flight_plan_id,
        'latitude' => $position->latitude,
        'longitude' => $position->longitude,
        'altitude' => $position->altitude,
        'heading' => $position->heading,
        'groundSpeed' => $position->ground_speed,
        'pilotLicense' => $pilot->license,
        'pilotName' => $pilot->fullname,
        'departure' => $entity ? $entity->departure : '-',
        'arrival' => $entity ? $entity->arrival : '-',
    ];
}

$flightsJson = json_encode($flightsData);

$altitudeLabel = Yii::t('app', 'Altitude');
$speedLabel = Yii::t('app', 'Ground Speed');

$this->registerJs(<<<JS
(function() {
    const flights = {$flightsJson};

    if (flights.length === 0) return;

    const vectorSource = new ol.source.Vector();

    flights.forEach(flight => {
        const coords = ol.proj.fromLonLat([flight.longitude, flight.latitude]);
        const feature = new ol.Feature({
            geometry: new ol.geom.Point(coords),
            flightData: flight
        });
        vectorSource.addFeature(feature);
    });

    function createPlaneStyle(heading) {
        const rotation = (heading * Math.PI) / 180;
        return new ol.style.Style({
            image: new ol.style.RegularShape({
                points: 3,
                radius: 12,
                rotation: rotation,
                fill: new ol.style.Fill({ color: '#1976d2' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
            })
        });
    }

    const vectorLayer = new ol.layer.Vector({
        source: vectorSource,
        style: function(feature) {
            const data = feature.get('flightData');
            return createPlaneStyle(data.heading);
        }
    });

    const extent = vectorSource.getExtent();
    const center = flights.length === 1
        ? ol.proj.fromLonLat([flights[0].longitude, flights[0].latitude])
        : ol.extent.getCenter(extent);

    const map = new ol.Map({
        target: 'liveFlightsMap',
        layers: [
            new ol.layer.Tile({
                source: new ol.source.OSM()
            }),
            vectorLayer
        ],
        view: new ol.View({
            center: center,
            zoom: flights.length === 1 ? 8 : 4
        })
    });

    if (flights.length > 1) {
        map.getView().fit(extent, { padding: [50, 50, 50, 50], maxZoom: 10 });
    }

    const popup = document.getElementById('flightInfoPopup');
    const popupContent = document.getElementById('popupContent');

    function showPopup(pixel, data) {
        popupContent.innerHTML =
            '<strong>' + data.pilotLicense + '</strong><br>' +
            data.pilotName + '<br>' +
            '<small>' + data.departure + ' → ' + data.arrival + '</small><hr class="my-1">' +
            '<small>{$altitudeLabel}: ' + data.altitude.toLocaleString() + ' ft</small><br>' +
            '<small>{$speedLabel}: ' + data.groundSpeed + ' kts</small>';

        popup.style.left = (pixel[0] + 15) + 'px';
        popup.style.top = (pixel[1] - 15) + 'px';
        popup.style.display = 'block';
    }

    function hidePopup() {
        popup.style.display = 'none';
    }

    map.on('click', function(evt) {
        const feature = map.forEachFeatureAtPixel(evt.pixel, f => f);
        if (feature) {
            const data = feature.get('flightData');
            showPopup(evt.pixel, data);

            document.querySelectorAll('.live-flight-row').forEach(row => {
                row.classList.remove('active');
                if (parseInt(row.dataset.flightId) === data.id) {
                    row.classList.add('active');
                }
            });
        } else {
            hidePopup();
        }
    });

    map.on('pointermove', function(evt) {
        const hit = map.hasFeatureAtPixel(evt.pixel);
        map.getTargetElement().style.cursor = hit ? 'pointer' : '';
    });

    document.querySelectorAll('.live-flight-row').forEach(row => {
        row.addEventListener('click', function() {
            const flightId = parseInt(this.dataset.flightId);
            const flight = flights.find(f => f.id === flightId);

            if (flight) {
                const coords = ol.proj.fromLonLat([flight.longitude, flight.latitude]);
                map.getView().animate({
                    center: coords,
                    zoom: 8,
                    duration: 500
                });

                document.querySelectorAll('.live-flight-row').forEach(r => r.classList.remove('active'));
                this.classList.add('active');

                const pixel = map.getPixelFromCoordinate(coords);
                setTimeout(() => showPopup(pixel, flight), 600);
            }
        });
    });
})();
JS
);
?>
