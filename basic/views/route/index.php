<?php

use app\helpers\ImageMam;
use app\models\Route;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\RouteSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Routes';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="route-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php if(Yii::$app->user->can('routeCrud')) : ?>
    <p>
        <?= Html::a('Create Route', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php endif; ?>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'contentOptions' => ['style' => 'vertical-align:middle'],
                ],

            [
                'attribute' => 'code',
                'contentOptions' => ['style' => 'vertical-align:middle'],
            ],

            [
                'attribute' => 'departure',
                'label' => 'Departure',
                'format' => 'raw',
                'value' => function($model) {
                    return Html::tag('div',
                        Html::tag('span', Html::encode($model->departure), [
                            'style'=>'display:inline-block; width:44px; text-align:left;'
                        ]) .
                        Html::tag('span', ImageMam::render('country_icon', $model->departure0->country->id), [
                            'style' => 'display:inline-block; vertical-align:middle; margin-left:5px;'
                        ]),
                        ['style'=>'white-space:nowrap;']
                    );
                },
                'contentOptions' => ['style' => 'vertical-align:middle; text-align:left;'],
            ],
            [
                'attribute' => 'arrival',
                'label' => 'Arrival',
                'format' => 'raw',
                'value' => function($model) {
                     return Html::tag('div',
                         Html::tag('span', Html::encode($model->arrival), [
                             'style'=>'display:inline-block; width:44px; text-align:left;'
                         ]) .
                         Html::tag('span', ImageMam::render('country_icon', $model->arrival0->country->id), [
                             'style' => 'display:inline-block; vertical-align:middle; margin-left:5px;'
                         ]),
                         ['style'=>'white-space:nowrap;']
                     );
                },
                'contentOptions' => ['style' => 'vertical-align:middle; text-align:left;'],
            ],

            [
                'attribute' => 'distance_nm',
                'contentOptions' => ['style' => 'vertical-align:middle'],
            ],

            [
                'class' => ActionColumn::className(),
                'contentOptions' => ['style' => 'vertical-align:middle'],
                'visibleButtons'=>[
                    'delete'=> function($model){
                        return Yii::$app->user->can('routeCrud');
                    },
                    'update'=> function($model){
                        return Yii::$app->user->can('routeCrud');
                    },
                ],
                'urlCreator' => function ($action, Route $model, $key, $index, $column) {
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
