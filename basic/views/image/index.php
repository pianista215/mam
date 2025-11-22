<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\ImageSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $types */

$this->title = 'Images';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="image-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <!-- Dropdown filter -->
    <div class="mb-3">
        <form method="get" action="">
            <label><strong>Filter by type:</strong></label>
            <select name="ImageSearch[type]" onchange="this.form.submit()" class="form-control" style="width:250px; display:inline-block;">
                <option value="">-- All types --</option>
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
                'label' => 'Preview',
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
                        // getUploadUrl() ya devuelve la URL generada con Url::to(...)
                        return Html::a('Replace', $model->getUploadUrl(), ['class' => 'btn btn-primary btn-sm']);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('Delete', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-danger btn-sm',
                            'data-confirm' => 'Are you sure you want to delete this image?',
                            'data-method' => 'post',
                        ]);
                    },
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    return Url::to([$action, 'id' => $model->id]);
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
        'summaryOptions' => ['class' => 'text-muted']
    ]); ?>

</div>
