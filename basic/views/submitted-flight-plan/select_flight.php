<?php

use app\helpers\ImageMam;
use app\models\Route;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlanSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Select flight';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="submitted-flight-plan-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (!empty($tourStages)): ?>
        <h3>Tour Stages</h3>
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Tour Stage</th>
                    <th>Departure</th>
                    <th>Arrival</th>
                    <th>Distance Nm</th>
                    <th>Description</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tourStages as $stage): ?>
                    <tr>
                        <td><?= Html::encode($stage->tour->name.' #'.$stage->sequence) ?></td>
                        <td>
                            <div style="display:flex; align-items:center; white-space:nowrap;">
                                <span style="display:inline-block; width:44px;">
                                    <?= Html::encode($stage->departure) ?>
                                </span>
                                <span style="margin-left:5px; display:flex; align-items:center;">
                                    <?= ImageMam::render('country_icon', $stage->departure0->country_id) ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; white-space:nowrap;">
                                <span style="display:inline-block; width:44px;">
                                    <?= Html::encode($stage->arrival) ?>
                                </span>
                                <span style="margin-left:5px; display:flex; align-items:center;">
                                    <?= ImageMam::render('country_icon', $stage->arrival0->country_id) ?>
                                </span>
                            </div>
                        </td>
                        <td><?= Html::encode($stage->distance_nm) ?></td>
                        <td><?= Html::encode($stage->description) ?></td>
                        <td>
                            <?= Html::a('<span class="glyphicon glyphicon-euro" aria-hidden="true">✈︎</span>', [
                                'select-aircraft-tour',
                                'tour_stage_id' => $stage->id
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <h3>Routes</h3>
    <?= GridView::widget([
        'dataProvider' => $routeDataProvider,
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
                'template' => '{select-aircraft-route}',
                'buttons' => [
                    'select-aircraft-route' => function ($url, $model, $key) { // <--- here you can override or create template for a button of a given name
                        return Html::a('<span class="glyphicon glyphicon-euro" aria-hidden="true">✈︎</span>', $url);
                     }
                 ],
                'urlCreator' => function ($action, Route $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'route_id' => $model->id]);
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
