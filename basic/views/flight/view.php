<?php

use app\rbac\constants\Permissions;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Flight $model */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Flights'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="flight-view">

    <div class="container mb-3">
        <div class="row">
            <div class="col-md-12">
                <div class="p-3 border rounded bg-light-subtle d-flex justify-content-between align-items-center">
                    <div class="fw-semibold fs-5 text-dark">
                        <?php if (!empty($model->tourStage)): ?>
                        <?= Html::encode($model->tourStage->fplDescription) ?>
                        <?php elseif($model->flight_type === 'C'): ?>
                        <?= Yii::t('app', 'Charter flight') . ' '.' ('.$model->departure.'-'.$model->arrival.')' ?>
                        <?php else: ?>
                        <?= Html::encode(Yii::t('app', 'Route').' '.$model->code) ?>
                        <?php endif; ?>
                    </div>
                    <?php if (Yii::$app->user->can(Permissions::DELETE_OWN_FLIGHT, ['flight' => $model])): ?>
                    <?= Html::a(Yii::t('app', 'Delete flight'), ['delete', 'id' => $model->id], [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => Yii::t('app', 'Are you sure you want to delete this flight? Once deleted, it cannot be recovered.'),
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <h3><?=Yii::t('app', 'Flight plan')?></h1>

    <?= $this->render('_flight_plan', [
        'model' => $model,
        'aircraft' => $model->getAircraft()->one(),
        'pilotName' => $model->pilot->fullname,
    ]) ?>

    <h3><?=Yii::t('app', 'Flight data')?></h3>

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

    <?php if ($model->isPendingValidation() && Yii::$app->user->can(Permissions::VALIDATE_FLIGHT, ['flight' => $model])): ?>
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
