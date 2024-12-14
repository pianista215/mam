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

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'aircraftConfiguration.fullname',
                'label' => 'Type',
            ],
            'registration',
            'name',
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
