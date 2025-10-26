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

    <?php if (!empty($model->tourStage)): ?>
    <div class="container mb-3">
        <div class="row">
            <div class="col-md-12">
                <div class="p-3 border rounded bg-light-subtle">
                    <div class="fw-semibold fs-5 text-dark">
                        <?= Html::encode($model->tourStage->fplDescription) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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

    <?php if ($model->isPendingValidation() || $model->isValidated()): ?>
        <?= $this->render('_issues', [
            'report' => $model->flightReport,
        ]) ?>
    <?php endif; ?>

    <?php if ($model->hasAcarsInfo()): ?>
        <?= $this->render('_map_altitude', [
            'report' => $model->flightReport,
        ]) ?>
    <?php endif; ?>

    <?php if ($model->isPendingValidation() && Yii::$app->user->can('validateFlight', ['flight' => $model])): ?>
        <?= $this->render('_validation_form', [
            'model' => $model,
            'validatorList' => $validatorList ?? [],
        ]) ?>
    <?php elseif ($model->isValidated()): ?>
        <?= $this->render('_validation_view', [
            'model' => $model,
        ]) ?>
    <?php endif; ?>

</div>
