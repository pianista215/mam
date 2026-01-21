<?php

use app\helpers\ImageMam;
use app\models\Image;
use app\models\Rank;
use app\rbac\constants\Permissions;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\RankSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Ranks');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="rank-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php if(Yii::$app->user->can(Permissions::RANK_CRUD)) : ?>
    <p>
        <?= Html::a(Yii::t('app', 'Create Rank'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php endif; ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => null,
        'options' => ['class' => 'table-responsive'],
        'columns' => [
            [
                'label' => '',
                'format' => 'raw',
                'value' => fn($model) => ImageMam::render(Image::TYPE_RANK_ICON, $model->id, 0),
                'contentOptions' => ['style' => 'width: '.Image::getAllowedTypes()[Image::TYPE_RANK_ICON]['width'].'px;'],
            ],
            'name',
            [
                'class' => ActionColumn::className(),
                'visibleButtons'=>[
                    'delete'=> function($model){
                        return Yii::$app->user->can(Permissions::RANK_CRUD);
                    },
                    'update'=> function($model){
                        return Yii::$app->user->can(Permissions::RANK_CRUD);
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
