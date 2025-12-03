<?php

use app\helpers\TimeHelper;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Aircraft $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aircrafts'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="aircraft-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if(Yii::$app->user->can('aircraftCrud') || Yii::$app->user->can('moveAircraft')) : ?>
    <p>
        <?php if(Yii::$app->user->can('aircraftCrud')) : ?>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                        'method' => 'post',
                    ],
                ]) ?>
        <?php endif; ?>
        <?php if(Yii::$app->user->can('moveAircraft')) : ?>
        <?= Html::a(Yii::t('app', 'Move Aircraft'), ['move', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
        <?php endif; ?>
    </p>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' =>'aircraftConfiguration.fullname',
                'label' => Yii::t('app', 'Aircraft Configuration'),
            ],
            'registration',
            'name',
            'location',
            [
                'attribute' => 'hours_flown',
                'value' => function ($model) {
                    return TimeHelper::formatHoursMinutes($model->hours_flown);
                },
                'format' => 'text',
            ],
        ],
    ]) ?>

</div>
