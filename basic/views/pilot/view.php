<?php

use app\helpers\TimeHelper;
use app\helpers\ImageMam;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */
/** @var yii\data\ActiveDataProvider $flightsProvider */

$this->title = $model->fullName;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pilots'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="pilot-view container py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>

        <?php if (Yii::$app->user->can('userCrud')): ?>
            <div>
                <?php if (isset($model->license)): ?>
                    <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary me-2']) ?>
                <?php else: ?>
                    <?= Html::a(Yii::t('app', 'Activate'), ['activate', 'id' => $model->id], ['class' => 'btn btn-success me-2']) ?>
                <?php endif; ?>

                <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                        'method' => 'post',
                    ],
                ]) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body d-flex align-items-start">
            <div class="pilot-photo me-4" style="min-width: 150px; text-align:center;">
                <?= ImageMam::render('pilot_profile', $model->id) ?>
            </div>

            <div class="pilot-data flex-fill me-4">
                <dl class="row mb-0">
                    <dt class="col-sm-4"><?=Yii::t('app', 'License')?></dt>
                    <dd class="col-sm-8"><?= Html::encode($model->license ?? '(none)') ?></dd>

                    <dt class="col-sm-4"><?=Yii::t('app', 'Name')?></dt>
                    <dd class="col-sm-8"><?= Html::encode($model->name) ?></dd>

                    <dt class="col-sm-4"><?=Yii::t('app', 'Surname')?></dt>
                    <dd class="col-sm-8"><?= Html::encode($model->surname) ?></dd>

                    <dt class="col-sm-4"><?=Yii::t('app', 'Registration Date')?></dt>
                    <dd class="col-sm-8"><?= Html::encode($model->registration_date) ?></dd>

                    <?php if (!empty($model->vatsim_id)): ?>
                        <dt class="col-sm-4"><?=Yii::t('app', 'Vatsim ID')?></dt>
                        <dd class="col-sm-8"><?= Html::encode($model->vatsim_id) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($model->ivao_id)): ?>
                        <dt class="col-sm-4"><?=Yii::t('app', 'Ivao ID')?></dt>
                        <dd class="col-sm-8"><?= Html::encode($model->ivao_id) ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-4"><?=Yii::t('app', 'Location')?></dt>
                    <dd class="col-sm-8"><?= Html::encode($model->location ?? '') ?></dd>
                </dl>
            </div>

            <div class="pilot-rank text-center" style="min-width: 120px;">
                <?php if ($model->rank): ?>
                    <div class="rank-image mb-1">
                        <?= ImageMam::render('rank_icon', $model->rank->id, 0, ['class' => 'img-fluid']) ?>
                    </div>
                    <div class="rank-name fw-semibold"><?= Html::encode($model->rank->name) ?></div>
                <?php else: ?>
                    <div class="text-muted fst-italic"><?=Yii::t('app', 'No rank')?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4"><?= Yii::t('app', 'Flight statistics') ?></h5>

            <div class="row text-center">

                <div class="col-md-2 col-6 mb-3">
                    <div class="text-muted small"><?= Yii::t('app', 'Hours Flown') ?></div>
                    <div class="fs-4 fw-semibold">
                        <?= TimeHelper::formatHoursMinutes($stats['hours_flown']) ?>
                    </div>
                </div>

                <div class="col-md-2 col-6 mb-3">
                    <div class="text-muted small"><?= Yii::t('app', 'Total flights') ?></div>
                    <div class="fs-4 fw-semibold">
                        <?= $stats['total_flights'] ?>
                    </div>
                </div>

                <div class="col-md-2 col-6 mb-3">
                    <div class="text-muted small"><?= Yii::t('app', 'Regular flights') ?></div>
                    <div class="fs-4 fw-semibold">
                        <?= $stats['regular_flights'] ?>
                    </div>
                </div>

                <div class="col-md-2 col-6 mb-3">
                    <div class="text-muted small"><?= Yii::t('app', 'Charter flights') ?></div>
                    <div class="fs-4 fw-semibold">
                        <?= $stats['charter_flights'] ?>
                    </div>
                </div>

                <div class="col-md-2 col-6 mb-3">
                    <div class="text-muted small"><?= Yii::t('app', 'Charter ratio') ?></div>
                    <div class="fs-4 fw-semibold">
                        <?= $stats['charter_ratio'] ?>%
                    </div>
                </div>

            </div>
        </div>
    </div>

    <h4 class="mb-3"><?=Yii::t('app', 'Recent flights')?></h4>

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
                'urlCreator' => function ($action, $model) {
                    return Url::toRoute(["/flight/view", "id" => $model->id]);
                },
            ],
        ],
        'tableOptions' => ['class' => 'table table-striped table-bordered align-middle'],
        'pager' => [
                'options' => ['class' => 'pagination justify-content-center'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'linkOptions' => ['class' => 'page-link'],
                'disabledListItemSubTagOptions' => ['class' => 'page-link'],
                'hideOnSinglePage' => true,
            ],
        'summaryOptions' => ['class' => 'text-muted']
    ]) ?>

</div>
