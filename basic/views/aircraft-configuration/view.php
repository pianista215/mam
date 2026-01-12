<?php

use app\models\Aircraft;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\AircraftConfiguration $model */

$this->title = $model->fullname;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aircraft Configurations'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="aircraft-configuration-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if(Yii::$app->user->can('aircraftConfigurationCrud')) : ?>
    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' =>'aircraftType.name',
                'label' => Yii::t('app', 'Aircraft Type'),
            ],
            'name',
            'pax_capacity',
            'cargo_capacity',
        ],
    ]) ?>

    <h4><?=Yii::t('app', 'Aircrafts') ?></h4>

    <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                'registration',
                'name',
                'location',
                [
                    'class' => ActionColumn::className(),
                    'visibleButtons'=>[
                        'delete'=> function($model){
                            return Yii::$app->user->can('aircraftCrud');
                        },
                        'update'=> function($model){
                            return Yii::$app->user->can('aircraftCrud');
                        },
                    ],
                    'urlCreator' => function ($action, Aircraft $model, $key, $index, $column) {
                        return Url::toRoute(['aircraft/'.$action, 'id' => $model->id]);
                     }
                ],
            ],
            'tableOptions' => ['class' => 'table table-striped table-bordered'],
            'pager' => [
                    'options' => ['class' => 'pagination justify-content-center'],
                    'linkContainerOptions' => ['class' => 'page-item'],
                    'linkOptions' => ['class' => 'page-link'],
                    'disabledListItemSubTagOptions' => ['class' => 'page-link'],
                    'hideOnSinglePage' => true,
                ],
            'summaryOptions' => ['class' => 'text-muted']
        ]); ?>

</div>
