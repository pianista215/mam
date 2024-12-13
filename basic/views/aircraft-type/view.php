<?php

use app\models\AircraftConfiguration;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\AircraftType $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Aircraft Types', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="aircraft-type-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if(Yii::$app->user->can('aircraftTypeCrud')) : ?>
    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
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
            'icao_type_code',
            'name',
            'max_nm_range',
        ],
    ]) ?>

    <h4>Configurations</h4>

    <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                'name',
                'pax_capacity',
                'cargo_capacity',
                [
                    'class' => ActionColumn::className(),
                    'visibleButtons'=>[
                        'delete'=> function($model){
                            return Yii::$app->user->can('aircraftTypeCrud');
                        },
                        'update'=> function($model){
                            return Yii::$app->user->can('aircraftTypeCrud');
                        },
                    ],
                    'urlCreator' => function ($action, AircraftConfiguration $model, $key, $index, $column) {
                        return Url::toRoute(['aircraft-configuration/'.$action, 'id' => $model->id]);
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
