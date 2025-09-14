<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Aircraft $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Aircrafts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="aircraft-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if(Yii::$app->user->can('aircraftCrud')) : ?>
    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Move Aircraft', ['move', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'aircraftConfiguration.fullname',
            'registration',
            'name',
            'location',
            'hours_flown',
        ],
    ]) ?>

</div>
