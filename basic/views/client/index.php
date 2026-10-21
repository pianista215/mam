<?php

use app\helpers\ImageMam;
use app\models\Client;
use app\models\Image;
use app\rbac\constants\Permissions;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\ClientSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Clients');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="client-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php if (Yii::$app->user->can(Permissions::CLIENT_CRUD)) : ?>
    <p>
        <?= Html::a(Yii::t('app', 'Create Client'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php endif; ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'table-responsive'],
        'columns' => [
            [
                'label' => '',
                'format' => 'raw',
                // Logos are stored at 300x150; shown at half size to keep the 2:1 ratio
                'value' => fn($model) => ImageMam::render(Image::TYPE_CLIENT_LOGO, $model->id, 0, ['style' => 'width:150px; height:75px;']),
                'contentOptions' => ['style' => 'width: 150px;'],
            ],
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => fn($model) => Html::a(Html::encode($model->name), ['view', 'id' => $model->id], ['class' => 'text-decoration-none']),
            ],
            [
                'attribute' => 'headquarters_icao',
                'value' => fn($model) => $model->headquarters
                    ? $model->headquarters_icao . ' - ' . $model->headquarters->name
                    : $model->headquarters_icao,
            ],
            [
                'label' => Yii::t('app', 'Hubs'),
                'format' => 'raw',
                'value' => fn($model) => implode(' ', array_map(
                    fn($hub) => Html::tag('span', Html::encode($hub->airport_icao), ['class' => 'badge bg-secondary']),
                    $model->clientHubs
                )),
            ],
            [
                'class' => ActionColumn::className(),
                'visibleButtons' => [
                    'delete' => function ($model) {
                        return Yii::$app->user->can(Permissions::CLIENT_CRUD);
                    },
                    'update' => function ($model) {
                        return Yii::$app->user->can(Permissions::CLIENT_CRUD);
                    },
                ],
                'urlCreator' => function ($action, Client $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                }
            ],
        ],
        'headerRowOptions' => ['style' => 'vertical-align: middle'],
        'tableOptions' => ['class' => 'table table-striped table-bordered align-middle'],
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
