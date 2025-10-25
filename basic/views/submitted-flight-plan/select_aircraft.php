<?php
use app\models\Aircraft;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var app\models\Route|app\models\TourStage $entity */
/** @var string $type */

$titleLabel = ($type === 'route')
    ? "Select aircraft ({$entity->departure}-{$entity->arrival})"
    : "Select aircraft for stage ({$entity->departure}-{$entity->arrival})";

$this->title = $titleLabel;
$this->params['breadcrumbs'][] = $this->title;

// Needed for urlCreator:
$this->params['entity_param'] = ($type === 'route')
    ? ['route_id' => $entity->id]
    : ['tour_stage_id' => $entity->id];
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
                    'prepare-fpl' => fn($url, $model) =>
                        Html::a('<span class="glyphicon" aria-hidden="true">✈︎</span>', $url),
                ],
                'urlCreator' => function ($action, Aircraft $model) {
                    return Url::toRoute(array_merge(
                        [$action],
                        Yii::$app->view->params['entity_param'],
                        ['aircraft_id' => $model->id]
                    ));
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
        'summaryOptions' => ['class' => 'text-muted'],
    ]); ?>

</div>
