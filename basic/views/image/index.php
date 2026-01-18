<?php

use app\controllers\ImageController;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\ImageSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $types */

$this->title = Yii::t('app', 'Images');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="image-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <!-- Dropdown filter -->
    <div class="mb-3">
        <form method="get" action="">
            <label><strong><?=Yii::t('app', 'Filter by type')?>:</strong></label>
            <select name="ImageSearch[type]" onchange="this.form.submit()" class="form-control" style="width:250px; display:inline-block;">
                <option value="">-- <?=Yii::t('app', 'All types')?> --</option>
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= Html::encode($key) ?>" <?= $searchModel->type === $key ? 'selected' : '' ?>>
                        <?= Html::encode($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => null, // we use our own dropdown instead
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',

            [
                'attribute' => 'type',
                'value' => function($model) use ($types) {
                    return $types[$model->type] ?? $model->type;
                },
            ],

            // Preview column
            [
                'label' => Yii::t('app', 'Preview'),
                'format' => 'raw',
                'value' => function($model) {
                    $url = method_exists($model, 'getUrl') ? $model->getUrl() : null;
                    if ($url) {
                        return Html::tag('div',
                            Html::img($url, [
                                'style' => 'max-width:120px; max-height:120px; object-fit:contain;',
                                'alt' => 'preview'
                            ])
                        );
                    }
                    return '<span class="text-muted">No image</span>';
                },
            ],

            'related_id',
            'element',
            'filename',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{replace} {delete}',
                'buttons' => [
                    'replace' => function ($url, $model, $key) {
                        $uploadUrl = Url::to([
                            'image/upload',
                            'type' => $model->type,
                            'related_id' => $model->related_id,
                            'element' => $model->element,
                            'redirect' => ImageController::REDIRECT_IMAGE_MANAGER,
                        ]);
                        return Html::a(Yii::t('app', 'Replace'), $uploadUrl, ['class' => 'btn btn-primary btn-sm']);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id, 'redirect' => ImageController::REDIRECT_IMAGE_MANAGER], [
                            'class' => 'btn btn-danger btn-sm',
                            'data-confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                            'data-method' => 'post',
                        ]);
                    },
                ],
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
