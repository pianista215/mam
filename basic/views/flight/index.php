<?php

use app\models\Flight;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\FlightSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Flights';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="flight-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'pilot.fullname',
            'aircraft.aircraftConfiguration.aircraftType.icao_type_code',
            'departure',
            'arrival',
            'creation_date',
            //'alternative1_icao',
            //'alternative2_icao',
            //'cruise_speed_value',
            //'cruise_speed_unit',
            //'flight_level_value',
            //'flight_level_unit',
            //'route',
            //'estimated_time',
            //'other_information',
            //'endurance_time',
            //'report_tool',
            //'status',
            //'creation_date',
            //'network',
            //'flight_rules',
            [
                'class' => ActionColumn::className(),
                'visibleButtons'=>[
                    'delete'=> false,
                    'update'=> false,
                ],
                'urlCreator' => function ($action, Flight $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>


</div>
