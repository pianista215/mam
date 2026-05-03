<?php

use app\helpers\ImageMam;
use app\models\AircraftConfiguration;
use app\models\AircraftTypeResource;
use app\models\Image;
use app\rbac\constants\Permissions;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\AircraftType $model */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var AircraftTypeResource[] $resources */
/** @var bool $canViewResources */
/** @var bool $isResourceManager */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aircraft Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="aircraft-type-view container mt-4">

    <div class="text-center mb-4">
        <?= ImageMam::render(Image::TYPE_AIRCRAFT_TYPE_IMAGE, $model->id, 0, [
            'class' => 'img-fluid rounded shadow-sm mx-auto d-block',
            'style' => 'max-height:400px; object-fit:cover;'
        ]) ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>

        <?php if (Yii::$app->user->can(Permissions::AIRCRAFT_TYPE_CRUD)): ?>
            <div>
                <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary me-2']) ?>
                <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                        'method' => 'post',
                    ],
                ]) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3"><?=Yii::t('app', 'ICAO Code')?></dt>
                <dd class="col-sm-9"><?= Html::encode($model->icao_type_code) ?></dd>

                <dt class="col-sm-3"><?=Yii::t('app', 'Name')?></dt>
                <dd class="col-sm-9"><?= Html::encode($model->name) ?></dd>

                <dt class="col-sm-3"><?=Yii::t('app', 'Max Range (NM)')?></dt>
                <dd class="col-sm-9"><?= Html::encode($model->max_nm_range) ?></dd>
            </dl>
        </div>
    </div>

    <h4><?=Yii::t('app', 'Aircraft Configurations')?></h4>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'options' => ['class' => 'table-responsive'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
            'pax_capacity',
            'cargo_capacity',
            [
                'class' => ActionColumn::className(),
                'visibleButtons'=>[
                    'delete'=> fn($model) => Yii::$app->user->can(Permissions::AIRCRAFT_TYPE_CRUD),
                    'update'=> fn($model) => Yii::$app->user->can(Permissions::AIRCRAFT_TYPE_CRUD),
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

    <?php if ($canViewResources): ?>
    <div class="card shadow-sm mb-4 mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><?= Yii::t('app', 'Resources') ?></h4>
            <?php if ($isResourceManager): ?>
            <form action="<?= Url::to(['aircraft-type-resource/upload', 'aircraftTypeId' => $model->id]) ?>"
                  method="post" enctype="multipart/form-data" class="d-flex gap-2">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <input type="file" name="file" class="form-control form-control-sm"
                       accept=".png,.jpg,.jpeg,.pdf,.rar,.zip,.7z" required>
                <button type="submit" class="btn btn-sm btn-success">
                    <?= Yii::t('app', 'Upload') ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($resources)): ?>
                <p class="text-muted"><?= Yii::t('app', 'No resources uploaded yet.') ?></p>
            <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($resources as $resource): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <?= Html::a(Html::encode($resource->original_name),
                            ['aircraft-type-resource/download', 'id' => $resource->id]) ?>
                        <small class="text-muted ms-2">
                            (<?= Yii::$app->formatter->asShortSize($resource->size_bytes, 1) ?>)
                        </small>
                    </span>
                    <?php if ($isResourceManager): ?>
                    <?= Html::a(Yii::t('app', 'Delete'),
                        ['aircraft-type-resource/delete', 'id' => $resource->id],
                        ['class' => 'btn btn-sm btn-danger',
                         'data-method' => 'post',
                         'data-confirm' => Yii::t('app', 'Are you sure you want to delete this item?')]) ?>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
