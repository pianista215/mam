<?php

/** @var yii\web\View $this */

$this->title = 'My Yii Application';
?>
<div class="site-index">

    <div class="jumbotron text-center bg-transparent mt-5 mb-5">
        <?= $homeContent ?>
    </div>

    <div class="body-content">

        <!-- Latest Flights -->
        <div class="row mb-5">
            <div class="col-12">
                <h2>Last flights</h2>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?= $pilotModel->getAttributeLabel('license') ?></th>
                            <th><?= $pilotModel->getAttributeLabel('fullname') ?></th>
                            <th><?= $flightModel->getAttributeLabel('departure') ?></th>
                            <th><?= $flightModel->getAttributeLabel('arrival') ?></th>
                            <th>Aircraft</th>
                            <th><?= $flightModel->getAttributeLabel('creation_date') ?></th>
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
        </div>

        <!-- Latest registered pilots -->
        <div class="row">
            <div class="col-12">
                <h2>New pilots</h2>
                <table class="table table-striped table-hover">
                    <thead>
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

    </div>
</div>
