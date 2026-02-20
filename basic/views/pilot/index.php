<?php

use app\helpers\ImageMam;
use app\helpers\TimeHelper;
use app\models\Image;
use app\models\Pilot;
use app\rbac\constants\Permissions;
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
    <?php if(Yii::$app->user->can(Permissions::USER_CRUD)) : ?>
    <p>
        <?= Html::a(Yii::t('app', 'Create Pilot'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php endif; ?>

    <?php $this->registerCss('.pilot-index th { vertical-align: middle; }'); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => null,
        'options' => ['class' => 'table-responsive'],
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'contentOptions' => ['style' => 'width:5%; vertical-align:middle;']
            ],
            [
                'attribute' => 'license',
                'contentOptions' => ['style' => 'width:10%; vertical-align:middle;']
            ],
            [
                'attribute' => 'fullname',
                'contentOptions' => ['style' => 'vertical-align:middle;']
            ],
            [
                'label' => Yii::t('app', 'Rank'),
                'attribute' => 'rank_name',
                'value' => function ($model) {
                    if ($model->rank === null) {
                        return '-';
                    }
                    return Html::tag('div',
                        ImageMam::render(Image::TYPE_RANK_ICON, $model->rank->id, 0, [
                            'title' => $model->rank->name,
                            'style' => 'max-height:50px; width:auto;',
                        ]),
                        ['title' => $model->rank->name]
                    );
                },
                'enableSorting' => true,
                'format' => 'raw',
                'contentOptions' => ['style' => 'vertical-align:middle; text-align:center;'],
            ],
            [
                'attribute' => 'location',
                'label' => Yii::t('app', 'Location'),
                'format' => 'raw',
                'value' => function($model) {

                    $airport = $model->location0;

                    return Html::tag('div',
                        Html::tag('div',
                            Html::tag('span', Html::encode($model->location), [
                                'style'=>'display:inline-block; width:44px; text-align:left;'
                            ]) .
                            Html::tag('span', ImageMam::render(Image::TYPE_COUNTRY_ICON, $airport->country->id), [
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
                'contentOptions' => ['style' => 'vertical-align:middle; text-align:left; width:10%;'],
            ],
            [
                'attribute' => 'hours_flown',
                'filter' => false,
                'value' => function ($model) {
                    return TimeHelper::formatHoursMinutes($model->hours_flown);
                },
                'format' => 'text',
                'contentOptions' => ['style' => 'width:10%; vertical-align:middle;']
            ],
            [
                'class' => ActionColumn::className(),
                'visibleButtons'=>[
                    'delete'=> function($model){
                        return Yii::$app->user->can(Permissions::USER_CRUD);
                    },
                    'update'=> function($model){
                        return Yii::$app->user->can(Permissions::USER_CRUD);
                    },
                ],
                'urlCreator' => function ($action, Pilot $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 },
                 'contentOptions' => ['style' => 'width:10%; text-align:center; vertical-align:middle;']
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
