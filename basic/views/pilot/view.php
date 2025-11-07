<?php

use app\helpers\TimeHelper;
use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */
/** @var yii\data\ActiveDataProvider $flightsProvider */

$this->title = $model->name . ' ' . $model->surname;
$this->params['breadcrumbs'][] = ['label' => 'Pilots', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="pilot-view container py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>

        <?php if (Yii::$app->user->can('userCrud')): ?>
            <div>
                <?php if (isset($model->license)): ?>
                    <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary me-2']) ?>
                <?php else: ?>
                    <?= Html::a('Activate', ['activate', 'id' => $model->id], ['class' => 'btn btn-success me-2']) ?>
                <?php endif; ?>

                <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Are you sure you want to delete this pilot?',
                        'method' => 'post',
                    ],
                ]) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body d-flex flex-wrap align-items-start">
            <div class="me-4 text-center" style="min-width: 100px;">
                <div class="fw-semibold mb-1"><?= $model->rank ? Html::encode($model->rank->name) : 'No rank' ?></div>
            </div>

            <div class="flex-fill">
                <dl class="row mb-0">
                    <dt class="col-sm-3">License</dt>
                    <dd class="col-sm-9"><?= Html::encode($model->license ?? '(none)') ?></dd>

                    <dt class="col-sm-3">Name</dt>
                    <dd class="col-sm-9"><?= Html::encode($model->name) ?></dd>

                    <dt class="col-sm-3">Surname</dt>
                    <dd class="col-sm-9"><?= Html::encode($model->surname) ?></dd>

                    <dt class="col-sm-3">Registration date</dt>
                    <dd class="col-sm-9"><?= Html::encode($model->registration_date) ?></dd>

                    <?php if (!empty($model->vatsim_id)): ?>
                        <dt class="col-sm-3">VATSIM ID</dt>
                        <dd class="col-sm-9"><?= Html::encode($model->vatsim_id) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($model->ivao_id)): ?>
                        <dt class="col-sm-3">IVAO ID</dt>
                        <dd class="col-sm-9"><?= Html::encode($model->ivao_id) ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-3">Hours flown</dt>
                    <dd class="col-sm-9"><?= Html::encode(TimeHelper::formatHoursMinutes($model->hours_flown)) ?></dd>

                    <dt class="col-sm-3">Location</dt>
                    <dd class="col-sm-9"><?= Html::encode($model->location ?? '') ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <h4 class="mb-3">Recent flights</h4>

    <?= GridView::widget([
        'dataProvider' => $flightsProvider,
        'filterModel' => $flightSearch,
        'summary' => "{count} flights",
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'creation_date',
                'format' => ['datetime', 'php:Y-m-d H:i'],
                'filter' => false,
            ],
            'departure',
            'arrival',
            'aircraft.aircraftConfiguration.aircraftType.icao_type_code',
            [
                'label' => 'Flight time',
                'value' => function ($model) {
                    $minutes = $model->flightReport->flight_time_minutes ?? null;
                    return $minutes !== null
                        ? TimeHelper::formatHoursMinutes($minutes / 60.0)
                        : '-';
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view}',
                'urlCreator' => fn($action, $model) => \yii\helpers\Url::toRoute([$action, 'id' => $model->id]),
            ],
        ],
        'tableOptions' => ['class' => 'table table-striped table-bordered align-middle'],
    ]) ?>

</div>
