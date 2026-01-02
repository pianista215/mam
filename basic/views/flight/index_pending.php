<?php

use app\helpers\ImageMam;
use app\models\Flight;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\FlightSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Pending Flights for validation');
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
            [
                'attribute' => 'departure',
                'label' => Yii::t('app', 'Departure'),
                'format' => 'raw',
                'value' => function($model) {

                    $airport = $model->departure0;

                    return Html::tag('div',
                        Html::tag('div',
                            Html::tag('span', Html::encode($model->departure), [
                                'style'=>'display:inline-block; width:44px; text-align:left;'
                            ]) .
                            Html::tag('span', ImageMam::render('country_icon', $airport->country->id), [
                                'style' => 'display:inline-block; vertical-align:middle; margin-left:5px;'
                            ]),
                            ['style'=>'white-space:nowrap;']
                        )
                        .
                        Html::tag('div',
                            Html::encode($airport->name),
                            [
                                'style' => '
                                    font-size:0.75em;
                                    color:#777;
                                    max-width:160px;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                ',
                                'title' => $airport->name
                            ]
                        )
                    );
                },
                'contentOptions' => ['style' => 'vertical-align:middle; text-align:left;'],
            ],
            [
                'attribute' => 'arrival',
                'label' => Yii::t('app', 'Arrival'),
                'format' => 'raw',
                'value' => function($model) {

                    $airport = $model->arrival0;

                    return Html::tag('div',
                        Html::tag('div',
                            Html::tag('span', Html::encode($model->arrival), [
                                'style'=>'display:inline-block; width:44px; text-align:left;'
                            ]) .
                            Html::tag('span', ImageMam::render('country_icon', $airport->country->id), [
                                'style' => 'display:inline-block; vertical-align:middle; margin-left:5px;'
                            ]),
                            ['style'=>'white-space:nowrap;']
                        )
                        .
                        Html::tag('div',
                            Html::encode($airport->name),
                            [
                                'style' => '
                                    font-size:0.75em;
                                    color:#777;
                                    max-width:160px;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                ',
                                'title' => $airport->name
                            ]
                        )
                    );
                },
                'contentOptions' => ['style' => 'vertical-align:middle; text-align:left;'],
            ],
            'creation_date',
            'fullStatus',
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
