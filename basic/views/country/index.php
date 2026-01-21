<?php

use app\helpers\ImageMam;
use app\models\Country;
use app\models\Image;
use app\rbac\constants\Permissions;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\CountrySearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Countries');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="country-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if(Yii::$app->user->can(Permissions::COUNTRY_CRUD)) : ?>
        <p>
            <?= Html::a(Yii::t('app', 'Create Country'), ['create'], ['class' => 'btn btn-success']) ?>
        </p>
    <?php endif; ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'table-responsive'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'label' => Yii::t('app', 'Flag'),
                'format' => 'raw',
                'value' => function ($model) {
                    $img = ImageMam::render(Image::TYPE_COUNTRY_ICON, $model->id);
                    return Html::tag('div', $img, ['style' => 'text-align:center;']);
                },
                'contentOptions' => ['style' => 'width:70px; text-align:center; vertical-align:middle;'],
                'headerOptions'  => ['style' => 'text-align:center;'],
            ],

            'name',
            'iso2_code',

            [
                'class' => ActionColumn::className(),
                'visibleButtons'=>[
                    'delete'=> function($model){
                        return Yii::$app->user->can(Permissions::COUNTRY_CRUD);
                    },
                    'update'=> function($model){
                        return Yii::$app->user->can(Permissions::COUNTRY_CRUD);
                    },
                ],
                'urlCreator' => function ($action, Country $model, $key, $index, $column) {
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
