<?php

use app\models\Tour;
use app\rbac\constants\Permissions;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\TourSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Tours');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tour-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php if(Yii::$app->user->can(Permissions::TOUR_CRUD)) : ?>
    <p>
        <?= Html::a(Yii::t('app', 'Create Tour'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php endif; ?>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'table-responsive'],
        'rowOptions' => function ($model) {
                $now = new \DateTime();
                $start = new \DateTime($model->start);
                $end = new \DateTime($model->end);
                if ($now < $start || $now > $end) {
                    return ['class' => 'table-secondary text-muted'];
                }
                return [];
            },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'name',
            'description',
            'start',
            'end',
            [
                'class' => ActionColumn::className(),
                'visibleButtons'=>[
                    'delete'=> function($model){
                        return Yii::$app->user->can(Permissions::TOUR_CRUD) && !$model->getFlights()->exists();
                    },
                    'update'=> function($model){
                        return Yii::$app->user->can(Permissions::TOUR_CRUD);
                    },
                ],
                'urlCreator' => function ($action, Tour $model, $key, $index, $column) {
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
