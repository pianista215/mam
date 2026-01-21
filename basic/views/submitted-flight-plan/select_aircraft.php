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

$titleLabel = Yii::t('app', 'Select aircraft for').' '.$entity->fplDescription;

$this->title = $titleLabel;
$this->params['breadcrumbs'][] = $this->title;

// Needed for url-creator
if($type === 'route') {
    $this->params['entity_param'] = ['route_id' => $entity->id];
    $this->params['action'] = 'prepare-fpl-route';
} else if($type === 'stage') {
    $this->params['entity_param'] = ['tour_stage_id' => $entity->id];
    $this->params['action'] = 'prepare-fpl-tour';
} else {
    $this->params['entity_param'] = ['arrival' => $entity->arrival];
    $this->params['action'] = 'prepare-fpl-charter';
}

$this->params['entity_type'] = $type;
?>

<div class="submitted-flight-plan-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'options' => ['class' => 'table-responsive'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'aircraftConfiguration.fullname',
                'label' => Yii::t('app', 'Aircraft'),
            ],
            'registration',
            'name',
            [
                'class' => ActionColumn::class,
                'template' => '{prepare-fpl}',
                'buttons' => [
                    'prepare-fpl' => fn($url, $model) =>
                        Html::a('<span class="glyphicon" aria-hidden="true">✈︎</span>', $url),
                ],
                'urlCreator' => function ($action, Aircraft $model) {
                    $view = Yii::$app->view;
                    $entityParam = $view->params['entity_param'];
                    $actionName = $view->params['action'];

                    return Url::toRoute(array_merge(
                        [$actionName],
                        $entityParam,
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
