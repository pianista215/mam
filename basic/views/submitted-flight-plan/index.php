<?php

use app\models\SubmittedFlightPlan;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlanSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Submitted Flight Plans';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="submitted-flight-plan-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Submitted Flight Plan', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'aircraft_id',
            'flight_rules',
            'alternative1_icao',
            //'alternative2_icao',
            //'cruise_speed',
            //'flight_level',
            //'route',
            //'estimated_time',
            //'other_information',
            //'endurance_time',
            //'route_id',
            //'pilot_id',
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, SubmittedFlightPlan $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>


</div>
