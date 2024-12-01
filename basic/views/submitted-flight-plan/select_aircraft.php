<?php

use app\models\Aircraft;
use app\models\Route;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlanSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Select aircraft';
$this->params['breadcrumbs'][] = $this->title;
$this->params['route_id'] = $route->id;
?>
<div class="submitted-flight-plan-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <h1><?= Html::encode($route->id) ?></h1>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'aircraftType.name',
            'name',
            'registration',
            [
                'class' => ActionColumn::className(),
                'template' => '{prepare-fpl}',
                'buttons' => [
                    'prepare-fpl' => function ($url, $model, $key) { // <--- here you can override or create template for a button of a given name
                        return Html::a('<span class="glyphicon glyphicon-euro" aria-hidden="true">✈︎</span>', $url);
                     }
                 ],
                'urlCreator' => function ($action, Aircraft $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'route_id' => $this->params['route_id'], 'aircraft_id' => $model->id]);
                }
            ],
        ],
    ]); ?>


</div>
