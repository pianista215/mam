<?php

use app\helpers\TimeHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

/** @var yii\web\View $this */
/** @var app\models\Rank $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ranks'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="rank-view container py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="card-body d-flex align-items-center">

            <div class="me-3">
                <?= \app\helpers\ImageMam::render('rank_icon', $model->id, 0) ?>
            </div>

            <div>
                <h4 class="card-title mb-0"><?= Html::encode($model->name) ?></h4>
            </div>

        </div>

        <?php if (Yii::$app->user->can('rankCrud')): ?>
            <div>
                <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary me-2']) ?>
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

    <h4><?=Yii::t('app', 'Pilots with this rank')?></h4>

    <?php if (empty($model->pilots)): ?>
        <p class="text-muted fst-italic"><?=Yii::t('app', 'No pilots currently have this rank.')?></p>
    <?php else: ?>
        <?= GridView::widget([
            'dataProvider' => new ArrayDataProvider([
                'allModels' => $model->pilots,
                'pagination' => false,
            ]),
            'summary' => false,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                'license',
                [
                    'attribute' => 'name',
                    'label' => Yii::t('app', 'Name'),
                    'value' => fn($pilot) => Html::a(
                        Html::encode($pilot->fullname),
                        ['pilot/view', 'id' => $pilot->id],
                        ['class' => 'text-decoration-none']
                    ),
                    'format' => 'raw',
                ],
                [
                    'label' => Yii::t('app', 'Hours Flown'),
                    'value' => function ($model) {
                        return TimeHelper::formatHoursMinutes($model->hours_flown);
                    },
                ],
            ],
            'tableOptions' => ['class' => 'table table-striped table-bordered align-middle mb-0'],
        ]) ?>
    <?php endif; ?>

</div>
