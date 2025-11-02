<?php

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
        <table class="table table-striped table-bordered">
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
                        <td><?= Html::encode($stage->departure) ?></td>
                        <td><?= Html::encode($stage->arrival) ?></td>
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
            ['class' => 'yii\grid\SerialColumn'],
            'code',
            'departure',
            'arrival',
            'distance_nm',
            [
                'class' => ActionColumn::className(),
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
