<?php

use app\models\Route;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlanSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Select route';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="submitted-flight-plan-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'code',
            'departure',
            'arrival',
            'distance_nm',
            [
                'class' => ActionColumn::className(),
                'template' => '{select-aircraft}',
                'buttons' => [
                    'select-aircraft' => function ($url, $model, $key) { // <--- here you can override or create template for a button of a given name
                        return Html::a('<span class="glyphicon glyphicon-euro" aria-hidden="true">✈︎</span>', $url);
                     }
                 ],
                'urlCreator' => function ($action, Route $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'route_id' => $model->id]);
                }
            ],
        ],
    ]); ?>


</div>