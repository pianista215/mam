<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Tour $model */
/** @var string|null $pageHtml */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Tours', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);
?>

<div class="tour-view container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
        <?php if (Yii::$app->user->can('tourCrud')): ?>
            <div>
                <?= Html::a('<i class="fa fa-edit"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>

                <?php if (!$model->getFlights()->exists()): ?>
                <?= Html::a('<i class="fa fa-trash"></i> Delete', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Are you sure you want to delete this tour?',
                        'method' => 'post',
                    ],
                ]) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <p class="text-muted mb-1">
                <strong>Start:</strong> <?= Yii::$app->formatter->asDate($model->start) ?> &nbsp; | &nbsp;
                <strong>End:</strong> <?= Yii::$app->formatter->asDate($model->end) ?>
            </p>

            <hr>

            <?php if (!empty($pageHtml)): ?>
                <div class="tour-page-content">
                    <?= $pageHtml ?>
                </div>
            <?php else: ?>
                <p><?= Html::encode($model->description) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <h3 class="mt-4 mb-3">Tour Stages</h3>

    <?php if (!empty($model->tourStages)): ?>
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Departure</th>
                    <th>Arrival</th>
                    <th>Distance Nm</th>
                    <th>Description</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($model->tourStages as $stage): ?>
                    <tr>
                        <td><?= Html::encode($stage->sequence) ?></td>
                        <td><?= Html::encode($stage->departure ?? '-') ?></td>
                        <td><?= Html::encode($stage->arrival ?? '-') ?></td>
                        <td><?= Html::encode($stage->distance_nm ?? '-') ?></td>
                        <td><?= Html::encode($stage->description ?? '-') ?></td>

                        <td class="text-center">
                            <?php if (!empty($stage->myFlightsAccepted)): ?>
                                <i class="fa-regular fa-circle-check" style="color: green;" title="Completed"></i>
                            <?php else: ?>
                                <i class="fa-regular fa-circle" style="color: gray;" title="Pending"></i>
                            <?php endif; ?>
                        </td>

                        <td class="text-center">
                            <?php if ($model->isActive()): ?>
                                <?= Html::a('✈︎', ['submitted-flight-plan/select-aircraft-tour', 'tour_stage_id' => $stage->id], [
                                    'class' => 'text-decoration-none fs-5 me-2',
                                    'title' => 'Fly stage'
                                ]) ?>
                            <?php endif; ?>

                            <?php if (Yii::$app->user->can('tourCrud')): ?>
                                <?= Html::a('<i class="fa fa-pencil"></i>', ['tour-stage/update', 'id' => $stage->id], [
                                    'class' => 'text-decoration-none me-2',
                                    'title' => 'Edit stage'
                                ]) ?>
                                <?php if (!$model->getFlights()->exists()): ?>
                                <?= Html::a('<i class="fa fa-trash"></i>', ['tour-stage/delete', 'id' => $stage->id], [
                                    'class' => 'text-decoration-none text-danger',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to delete this stage?',
                                        'method' => 'post',
                                    ],
                                    'title' => 'Delete stage'
                                ]) ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (Yii::$app->user->can('tourCrud') && !$model->getFlights()->exists()): ?>
            <div class="text-end mt-2">
                <?= Html::a('<i class="fa fa-plus"></i> Add Stage', ['tour-stage/add-stage', 'tour_id' => $model->id], [
                    'class' => 'btn btn-success',
                ]) ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-secondary text-center">
            This tour has no stages yet.
        </div>

        <?php if (Yii::$app->user->can('tourCrud') && !$model->getFlights()->exists()): ?>
            <div class="text-center mt-3">
                <?= Html::a('<i class="fa fa-plus"></i> Add First Stage', ['tour-stage/add-stage', 'tour_id' => $model->id], [
                    'class' => 'btn btn-success',
                ]) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($model->pilotTourCompletions)): ?>
        <div class="card shadow-sm mt-5 mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">🏆 Hall of Fame</h4>

                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Pilot</th>
                            <th>Completion Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($model->pilotTourCompletions as $index => $completion): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= Html::encode($completion->pilot->fullname) ?></td>
                                <td><?= Yii::$app->formatter->asDate($completion->completed_at) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
