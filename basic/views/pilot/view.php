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
/** @var app\models\PilotCredential|null $highestLicense */
/** @var app\models\PilotCredential[] $lowerLicenses */
/** @var app\models\PilotCredential[] $earnedOther */
/** @var app\models\PilotCredential[] $studentCredentials */
/** @var app\models\AircraftType[] $authorizedAircraft */

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

    <?php
    $hasAnyCredential = $highestLicense !== null || !empty($earnedOther) || !empty($studentCredentials);

    $makeBadge = function($pc) {
        if ($pc->isStudent()) {
            return '<span class="badge bg-info">' . Yii::t('app', 'Student') . '</span>';
        } elseif ($pc->expiry_date !== null && $pc->expiry_date < date('Y-m-d')) {
            return '<span class="badge bg-danger">' . Yii::t('app', 'Expired') . '</span>';
        }
        return '<span class="badge bg-success">' . Yii::t('app', 'Active') . '</span>';
    };
    ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><?= Yii::t('app', 'Credentials') ?></h5>
                <?php if (Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)): ?>
                    <?= Html::a(
                        '+ ' . Yii::t('app', 'Issue Credential'),
                        ['/pilot-credential/issue', 'pilotId' => $model->id],
                        ['class' => 'btn btn-sm btn-outline-primary']
                    ) ?>
                <?php endif; ?>
            </div>

            <?php if (!$hasAnyCredential): ?>
                <p class="text-muted mb-0"><?= Yii::t('app', 'No credentials issued yet.') ?></p>
            <?php else: ?>

            <?php if ($highestLicense !== null || !empty($earnedOther)): ?>
            <table class="table table-sm table-bordered mb-3">
                <thead class="table-light">
                    <tr>
                        <th><?= Yii::t('app', 'Credential Type') ?></th>
                        <th><?= Yii::t('app', 'Status') ?></th>
                        <th><?= Yii::t('app', 'Issued Date') ?></th>
                        <th><?= Yii::t('app', 'Expiry Date') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($highestLicense !== null): ?>
                    <tr>
                        <td>
                            <?= Html::encode($highestLicense->credentialType->name) ?>
                            <span class="badge bg-secondary ms-1"><?= Html::encode($highestLicense->credentialType->getTypeLabel()) ?></span>
                            <?php if (!empty($lowerLicenses)): ?>
                                <a data-bs-toggle="collapse" href="#lowerLicenses" role="button"
                                   class="ms-1 text-muted small text-decoration-none">
                                    &#9662; <?= count($lowerLicenses) ?> <?= Yii::t('app', 'previous') ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?= $makeBadge($highestLicense) ?></td>
                        <td><?= Html::encode($highestLicense->issued_date) ?></td>
                        <td><?= $highestLicense->expiry_date ? Html::encode($highestLicense->expiry_date) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= Html::a(Yii::t('app', 'View'), ['/pilot-credential/view', 'id' => $highestLicense->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?></td>
                    </tr>
                    <?php if (!empty($lowerLicenses)): ?>
                    <tr class="collapse" id="lowerLicenses">
                        <td colspan="5" class="p-0 border-0">
                            <table class="table table-sm mb-0 bg-light">
                                <tbody>
                                    <?php foreach ($lowerLicenses as $h): ?>
                                    <tr>
                                        <td>
                                            <?= Html::encode($h->credentialType->name) ?>
                                            <span class="badge bg-secondary ms-1"><?= Html::encode($h->credentialType->getTypeLabel()) ?></span>
                                        </td>
                                        <td><?= $makeBadge($h) ?></td>
                                        <td><?= Html::encode($h->issued_date) ?></td>
                                        <td><?= $h->expiry_date ? Html::encode($h->expiry_date) : '<span class="text-muted">—</span>' ?></td>
                                        <td><?= Html::a(Yii::t('app', 'View'), ['/pilot-credential/view', 'id' => $h->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php foreach ($earnedOther as $pc): ?>
                    <tr>
                        <td>
                            <?= Html::encode($pc->credentialType->name) ?>
                            <span class="badge bg-secondary ms-1"><?= Html::encode($pc->credentialType->getTypeLabel()) ?></span>
                        </td>
                        <td><?= $makeBadge($pc) ?></td>
                        <td><?= Html::encode($pc->issued_date) ?></td>
                        <td><?= $pc->expiry_date ? Html::encode($pc->expiry_date) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= Html::a(Yii::t('app', 'View'), ['/pilot-credential/view', 'id' => $pc->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php if (!empty($studentCredentials)): ?>
            <h6 class="text-muted mt-3 mb-2"><?= Yii::t('app', 'In Training') ?></h6>
            <table class="table table-sm table-bordered mb-3">
                <thead class="table-light">
                    <tr>
                        <th><?= Yii::t('app', 'Credential Type') ?></th>
                        <th><?= Yii::t('app', 'Status') ?></th>
                        <th><?= Yii::t('app', 'Issued Date') ?></th>
                        <th><?= Yii::t('app', 'Expiry Date') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentCredentials as $pc): ?>
                    <tr>
                        <td>
                            <?= Html::encode($pc->credentialType->name) ?>
                            <span class="badge bg-secondary ms-1"><?= Html::encode($pc->credentialType->getTypeLabel()) ?></span>
                        </td>
                        <td><?= $makeBadge($pc) ?></td>
                        <td><?= Html::encode($pc->issued_date) ?></td>
                        <td><?= $pc->expiry_date ? Html::encode($pc->expiry_date) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= Html::a(Yii::t('app', 'View'), ['/pilot-credential/view', 'id' => $pc->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php endif; ?>

            <?php if (!empty($authorizedAircraft)): ?>
            <div>
                <span class="text-muted small fw-semibold"><?= Yii::t('app', 'Authorized Aircraft Types') ?>:</span>
                <?php foreach ($authorizedAircraft as $at): ?>
                    <span class="badge bg-light text-dark border ms-1"><?= Html::encode($at->name) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3"><?= Yii::t('app', 'Completed Tours') ?></h5>

            <?php if (!empty($model->pilotTourCompletions)): ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 150px)); gap:1rem;"><?php foreach ($model->pilotTourCompletions as $completion) {
                    echo Html::tag('div', Html::a(
                        ImageMam::render(Image::TYPE_TOUR_BADGE, $completion->tour_id, 0, [
                            'class' => 'rounded',
                            'style' => 'width:150px; height:150px; object-fit:contain;',
                            'title' => $completion->tour->name,
                        ]),
                        ['/tour/view', 'id' => $completion->tour_id]
                    ));
                } ?></div>
            <?php else: ?>
                <p class="text-muted mb-0"><?= Yii::t('app', 'No tours completed yet') ?></p>
            <?php endif; ?>
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
