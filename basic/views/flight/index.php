<?php

use app\models\Flight;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\FlightSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Flights');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="flight-index">

    <h1><?= Html::encode($this->title) ?></h1>

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
            [
                'attribute' => 'status',
                'format' => 'raw', //Important for allow html
                'value' => function ($model) {
                    $icons = [
                        'C' => '<i class="fa-solid fa-arrow-up" style="color: #6c757d;"></i>',
                        'S' => '<i class="fa-regular fa-clock" style="color: #0d6efd;"></i>',
                        'V' => '<i class="fa-regular fa-eye" style="color: orange;"</i>',
                        'F' => '<i class="fa-regular fa-circle-check" style="color: green;"></i>',
                        'R' => '<i class="fa-regular fa-circle-xmark" style="color: red;"></i>',
                    ];

                    $icon = $icons[$model->status] ?? '<i class="fa-regular fa-question-circle"></i>';
                    return '<span title="' . htmlspecialchars($model->fullStatus) . '">' . $icon . '</span>';
                },
                'contentOptions' => ['style' => 'text-align:center; font-size: 18px;'], // Opcional
            ],
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
