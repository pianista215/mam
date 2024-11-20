<?php

use app\models\AircraftType;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\AircraftTypeSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Aircraft Types';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aircraft-type-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php if(Yii::$app->user->can('aircraftTypeCrud')) : ?>
    <p>
        <?= Html::a('Create Aircraft Type', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php endif; ?>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'icao_type_code',
            'name',
            'max_nm_range',
            'pax_capacity',
            //'cargo_capacity',
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
                'urlCreator' => function ($action, AircraftType $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>


</div>
