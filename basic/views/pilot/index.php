<?php

use app\helpers\TimeHelper;
use app\models\Pilot;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
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

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => null,
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'contentOptions' => ['style' => 'width:5%;']
            ],
            [
                'attribute' => 'license',
                'contentOptions' => ['style' => 'width:10%;']
            ],
            'fullname',
            [
                'label' => Yii::t('app', 'Rank'),
                'attribute' => 'rank_name',
                'value' => fn($model) => $model->rank->name ?? '-',
                'enableSorting' => true,
                'format' => 'text',
            ],
            [
                'attribute' => 'location',
                'contentOptions' => ['style' => 'width:10%;']
            ],
            [
                'attribute' => 'hours_flown',
                'filter' => false,
                'value' => function ($model) {
                    return TimeHelper::formatHoursMinutes($model->hours_flown);
                },
                'format' => 'text',
                'contentOptions' => ['style' => 'width:10%;']
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
                 'contentOptions' => ['style' => 'width:10%; text-align:center;']
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
