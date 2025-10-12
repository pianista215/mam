<?php

/** @var yii\web\View $this */

$this->title = 'My Yii Application';
?>
<div class="site-index">

    <div class="jumbotron text-center bg-transparent mt-5 mb-5">
        <?= $homeContent ?>
    </div>

    <div class="body-content">
        <div class="row">

            <!-- Columna izquierda: tablas -->
            <div class="col-md-8">

                <!-- Latest Flights -->
                <div class="mb-4">
                    <h4 class="mb-3">Last flights</h4>
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-light">
                        <tr>
                            <th><?= $pilotModel->getAttributeLabel('license') ?></th>
                            <th><?= $pilotModel->getAttributeLabel('fullname') ?></th>
                            <th><?= $flightModel->getAttributeLabel('departure') ?></th>
                            <th><?= $flightModel->getAttributeLabel('arrival') ?></th>
                            <th>Aircraft</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lastFlights as $flight): ?>
                            <tr>
                                <td><?= htmlspecialchars($flight->pilot->license) ?></td>
                                <td><?= htmlspecialchars($flight->pilot->fullname) ?></td>
                                <td><?= htmlspecialchars($flight->departure) ?></td>
                                <td><?= htmlspecialchars($flight->arrival) ?></td>
                                <td><?= htmlspecialchars($flight->aircraft->aircraftConfiguration->aircraftType->icao_type_code ?? 'N/A') ?></td>
                                <td><?= Yii::$app->formatter->asDate($flight->creation_date, 'php:d/m/Y') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Latest registered pilots -->
                <div class="mb-4">
                    <h4 class="mb-3">New pilots</h4>
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-light">
                        <tr>
                            <th><?= $pilotModel->getAttributeLabel('license') ?></th>
                            <th><?= $pilotModel->getAttributeLabel('fullname') ?></th>
                            <th><?= $pilotModel->getAttributeLabel('registration_date') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lastPilots as $pilot): ?>
                            <tr>
                                <td><?= htmlspecialchars($pilot->license) ?></td>
                                <td><?= htmlspecialchars($pilot->fullname) ?></td>
                                <td><?= Yii::$app->formatter->asDate($pilot->registration_date, 'php:d/m/Y') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Columna derecha: estadísticas -->
            <div class="col-md-4">
                <div class="card border-secondary mb-4">
                    <div class="card-header bg-light"><strong>Statistics</strong></div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Pilots:</strong> <span class="fw-normal"><?= number_format($totalPilots) ?></span></p>
                        <p class="mb-2"><strong>Aircraft:</strong> <span class="fw-normal"><?= number_format($totalAircraft) ?></span></p>
                        <p class="mb-2"><strong>Routes:</strong> <span class="fw-normal"><?= number_format($totalRoutes) ?></span></p>
                        <p class="mb-0"><strong>Flights:</strong> <span class="fw-normal"><?= number_format($totalFlights) ?></span></p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
