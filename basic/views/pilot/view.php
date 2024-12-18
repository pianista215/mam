<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Pilots', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="pilot-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if(Yii::$app->user->can('userCrud')) : ?>
    <p>
        <?php if(isset($model->license)) : ?>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php else : ?>
        <?= Html::a('Activate', ['activate', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>

        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <?php endif; ?>

    <?php if(isset($model->license)) : ?>
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'license',
            'email:email',
            'name',
            'surname',
            'registration_date',
            [
                'attribute' =>'country.name',
                'label' => 'Country Name',
            ],
            'city',
            'date_of_birth',
            'vatsim_id',
            'ivao_id',
            'hours_flown',
            'location',
        ],
    ]) ?>
    <?php else : ?>
    <?= DetailView::widget([
            'model' => $model,
            'attributes' => [
                'email:email',
                'name',
                'surname',
                'registration_date',
                [
                    'attribute' =>'country.name',
                    'label' => 'Country Name',
                ],
                'city',
                'date_of_birth',
                'vatsim_id',
                'ivao_id',
            ],
        ]) ?>
    <?php endif; ?>

</div>
