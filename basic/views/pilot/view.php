<?php

use app\helpers\TimeHelper;
use app\helpers\ImageMam;
use app\models\Image;
use app\rbac\constants\Permissions;
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

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>

        <?php if (Yii::$app->user->can(Permissions::USER_CRUD)): ?>
            <div class="flex-shrink-0">
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
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-auto text-center mb-3 mb-md-0">
                    <?= ImageMam::render(Image::TYPE_PILOT_PROFILE, $model->id) ?>
                </div>

                <div class="col-12 col-md">
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

                <div class="col-12 col-md-auto text-center mt-3 mt-md-0">
                    <?php if ($model->rank): ?>
                        <div class="rank-image mb-1">
                            <?= ImageMam::render(Image::TYPE_RANK_ICON, $model->rank->id, 0, ['class' => 'img-fluid']) ?>
                        </div>
                        <div class="rank-name fw-semibold"><?= Html::encode($model->rank->name) ?></div>
                    <?php else: ?>
                        <div class="text-muted fst-italic"><?=Yii::t('app', 'No rank')?></div>
                    <?php endif; ?>
                </div>
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
        'options' => ['class' => 'table-responsive'],
        'summary' => "{count} flights",
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'creation_date',
                'format' => ['datetime', 'php:Y-m-d H:i'],
                'filter' => false,
            ],
            [
                'attribute' => 'departure',
                'label' => Yii::t('app', 'Departure'),
                'format' => 'raw',
                'value' => function($model) {

                    $airport = $model->departure0;

                    return Html::tag('div',
                        Html::tag('div',
                            Html::tag('span', Html::encode($model->departure), [
                                'style'=>'display:inline-block; width:44px; text-align:left;'
                            ]) .
                            Html::tag('span', ImageMam::render(Image::TYPE_COUNTRY_ICON, $airport->country->id), [
                                'style' => 'display:inline-block; vertical-align:middle; margin-left:5px;'
                            ]),
                            ['style'=>'white-space:nowrap;']
                        )
                        .
                        Html::tag('div',
                            Html::encode($airport->name),
                            [
                                'style' => '
                                    font-size:0.75em;
                                    color:#777;
                                    max-width:160px;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                ',
                                'title' => $airport->name
                            ]
                        )
                    );
                },
                'contentOptions' => ['style' => 'vertical-align:middle; text-align:left;'],
            ],
            [
                'attribute' => 'arrival',
                'label' => Yii::t('app', 'Arrival'),
                'format' => 'raw',
                'value' => function($model) {

                    $airport = $model->arrival0;

                    return Html::tag('div',
                        Html::tag('div',
                            Html::tag('span', Html::encode($model->arrival), [
                                'style'=>'display:inline-block; width:44px; text-align:left;'
                            ]) .
                            Html::tag('span', ImageMam::render(Image::TYPE_COUNTRY_ICON, $airport->country->id), [
                                'style' => 'display:inline-block; vertical-align:middle; margin-left:5px;'
                            ]),
                            ['style'=>'white-space:nowrap;']
                        )
                        .
                        Html::tag('div',
                            Html::encode($airport->name),
                            [
                                'style' => '
                                    font-size:0.75em;
                                    color:#777;
                                    max-width:160px;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                ',
                                'title' => $airport->name
                            ]
                        )
                    );
                },
                'contentOptions' => ['style' => 'vertical-align:middle; text-align:left;'],
            ],
            'aircraft.aircraftConfiguration.aircraftType.icao_type_code',
            [
                'label' => Yii::t('app', 'Flight Time'),
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
