<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Flight $model */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Flights', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="flight-view">

    <h3>Flight plan</h1>

    <?= $this->render('_flight_plan', [
        'model' => $model,
        'aircraft' => $model->getAircraft()->one(),
        'pilotName' => $model->pilot->fullname,
    ]) ?>

    <h3>Flight data</h3>

    <?= $this->render('_flight_data', [
        'model' => $model,
        'aircraft' => $model->getAircraft()->one(),
        'pilotName' => $model->pilot->fullname,
    ]) ?>

    <?php if ($model->status === 'V' || $model->status === 'F'): ?>
        <?= $this->render('_map_altitude', [
            'report' => $model->flightReport,
        ]) ?>
    <?php endif; ?>

</div>
