<?php

use app\helpers\TimeHelper;
use app\models\Pilot;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\PilotSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Pilots');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pilot-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php if(Yii::$app->user->can('userCrud')) : ?>
    <p>
        <?= Html::a(Yii::t('app', 'Create Pilot'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php endif; ?>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'license',
            'name',
            'surname',
            [
                'label' => Yii::t('app', 'Rank'),
                'value' => function ($model) {
                    $rank = $model->rank->name ?? null;
                    return $rank !== null
                        ? $rank
                        : '-';
                },
                'format' => 'text',
            ],
            'location',
            [
                'attribute' => 'hours_flown',
                'filter' => false,
                'value' => function ($model) {
                    return TimeHelper::formatHoursMinutes($model->hours_flown);
                },
                'format' => 'text',
            ],
            [
                'class' => ActionColumn::className(),
                'visibleButtons'=>[
                    'delete'=> function($model){
                        return Yii::$app->user->can('userCrud');
                    },
                    'update'=> function($model){
                        return Yii::$app->user->can('userCrud');
                    },
                ],
                'urlCreator' => function ($action, Pilot $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 },
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
