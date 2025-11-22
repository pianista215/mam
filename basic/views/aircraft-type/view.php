<?php

use app\helpers\ImageMam;
use app\models\AircraftConfiguration;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\AircraftType $model */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Aircraft Types', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="aircraft-type-view container mt-4">

    <div class="text-center mb-4">
        <?= ImageMam::render('aircraftType_image', $model->id, 0, [
            'class' => 'img-fluid rounded shadow-sm mx-auto d-block',
            'style' => 'max-height:400px; object-fit:cover;'
        ]) ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>

        <?php if (Yii::$app->user->can('aircraftTypeCrud')): ?>
            <div>
                <?= Html::a('<i class="fa fa-edit"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary me-2']) ?>
                <?= Html::a('<i class="fa fa-trash"></i> Delete', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Are you sure you want to delete this aircraft type?',
                        'method' => 'post',
                    ],
                ]) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">ICAO Code</dt>
                <dd class="col-sm-9"><?= Html::encode($model->icao_type_code) ?></dd>

                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9"><?= Html::encode($model->name) ?></dd>

                <dt class="col-sm-3">Max Range (Nm)</dt>
                <dd class="col-sm-9"><?= Html::encode($model->max_nm_range) ?></dd>
            </dl>
        </div>
    </div>

    <h4>Configurations</h4>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
            'pax_capacity',
            'cargo_capacity',
            [
                'class' => ActionColumn::className(),
                'visibleButtons'=>[
                    'delete'=> fn($model) => Yii::$app->user->can('aircraftTypeCrud'),
                    'update'=> fn($model) => Yii::$app->user->can('aircraftTypeCrud'),
                ],
                'urlCreator' => fn($action, AircraftConfiguration $model, $key, $index, $column) =>
                    Url::toRoute(['aircraft-configuration/'.$action, 'id' => $model->id]),
            ],
        ],
        'tableOptions' => ['class' => 'table table-striped table-bordered align-middle'],
        'pager' => [
            'options' => ['class' => 'pagination justify-content-center'],
            'linkContainerOptions' => ['class' => 'page-item'],
            'linkOptions' => ['class' => 'page-link'],
            'disabledListItemSubTagOptions' => ['class' => 'page-link'],
            'hideOnSinglePage' => true,
        ],
        'summaryOptions' => ['class' => 'text-muted']
    ]) ?>

</div>
