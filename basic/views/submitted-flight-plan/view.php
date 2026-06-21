<?php

use app\rbac\constants\Permissions;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlan $model */

$this->title = Yii::t('app', 'Current Flight Plan');
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="submitted-flight-plan-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->user->can(Permissions::CRUD_OWN_FPL, ['submittedFlightPlan' => $model])) : ?>
    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php
        $confirmMessage = $model->hasLiveFlightPosition()
            ? Yii::t('app', 'This flight plan has recorded flight data. Do not delete if you are still flying or if the flight ended but ACARS could not send the data. If ACARS failed, restart it to complete the upload. If you continue, ALL flight data will be permanently lost. Are you sure?')
            : Yii::t('app', 'Are you sure you want to delete this item?');
        ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => $confirmMessage,
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <?php endif; ?>

    <?= $this->render('_fpl_header', [
            'entity' => $entity,
    ]) ?>

    <?= $this->render('_form', [
        'model' => $model,
        'aircraft' => $model->aircraft,
        'entity' => $entity,
        'pilotName' => $model->pilot->fullname,
        'mode' => 'view',
        'pob' => $pob,
    ]) ?>

    <?php if ($model->pax_adults !== null): ?>
    <div class="card mt-3">
        <div class="card-header"><strong><?= Yii::t('app', 'Load Sheet') ?></strong></div>
        <div class="card-body">
            <?php
            $adultW  = \app\config\ConfigHelper::getPaxAdultWeightKg();
            $childW  = \app\config\ConfigHelper::getPaxChildWeightKg();
            $bagW    = \app\config\ConfigHelper::getPaxCheckedBaggageKg();
            $paxKg   = $model->pax_adults * $adultW + $model->pax_children * $childW;
            $bagsKg  = $model->cargo_bags * $bagW;
            $cargoKg = $bagsKg + $model->cargo_paid_kg;
            ?>
            <p>
                <strong><?= Yii::t('app', 'PAX') ?>:</strong>
                <?= $model->pax_adults ?> <?= Yii::t('app', 'adults') ?>,
                <?= $model->pax_children ?> <?= Yii::t('app', 'children') ?>
                &rarr; (<?= $model->pax_adults ?>&times;<?= $adultW ?> + <?= $model->pax_children ?>&times;<?= $childW ?>) = <strong><?= $paxKg ?> Kg</strong>
            </p>
            <p>
                <strong><?= Yii::t('app', 'Cargo') ?>:</strong>
                <?= Yii::t('app', 'Checked bags') ?> (<?= $model->cargo_bags ?> &times; <?= $bagW ?> Kg = <?= $bagsKg ?> Kg) +
                <?= Yii::t('app', 'Paid cargo') ?> (<?= $model->cargo_paid_kg ?> Kg) = <strong><?= $cargoKg ?> Kg</strong>
            </p>
        </div>
    </div>
    <?php endif; ?>

</div>
