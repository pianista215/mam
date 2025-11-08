<?php

use app\models\Rank;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\RankSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Ranks';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="rank-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php if(Yii::$app->user->can('rankCrud')) : ?>
    <p>
        <?= Html::a('Create Rank', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php endif; ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => null,
        'columns' => [
            'name',
            [
                'class' => ActionColumn::className(),
                'visibleButtons'=>[
                    'delete'=> function($model){
                        return Yii::$app->user->can('rankCrud');
                    },
                    'update'=> function($model){
                        return Yii::$app->user->can('rankCrud');
                    },
                ],
                'urlCreator' => function ($action, Rank $model, $key, $index, $column) {
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
