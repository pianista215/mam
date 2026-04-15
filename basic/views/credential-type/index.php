<?php

use app\models\CredentialType;
use app\rbac\constants\Permissions;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\CredentialTypeSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $mermaidGraph */

$this->title = Yii::t('app', 'Credential Types');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="credential-type-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->user->can(Permissions::CREDENTIAL_CRUD)): ?>
    <p>
        <?= Html::a(Yii::t('app', 'Create Credential Type'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php endif; ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'options'      => ['class' => 'table-responsive'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'code',
            'name',
            [
                'attribute' => 'type',
                'value'     => fn(CredentialType $model) => $model->getTypeLabel(),
                'filter'    => CredentialType::typeLabels(),
            ],
            [
                'class' => ActionColumn::class,
                'visibleButtons' => [
                    'delete' => fn($model) => Yii::$app->user->can(Permissions::CREDENTIAL_CRUD),
                    'update' => fn($model) => Yii::$app->user->can(Permissions::CREDENTIAL_CRUD),
                ],
                'urlCreator' => fn($action, CredentialType $model, $key, $index, $column) =>
                    Url::toRoute([$action, 'id' => $model->id]),
            ],
        ],
        'tableOptions' => ['class' => 'table table-striped table-bordered'],
        'pager' => [
            'options'                       => ['class' => 'pagination justify-content-center'],
            'linkContainerOptions'          => ['class' => 'page-item'],
            'linkOptions'                   => ['class' => 'page-link'],
            'disabledListItemSubTagOptions' => ['class' => 'page-link'],
            'hideOnSinglePage'              => true,
        ],
        'summaryOptions' => ['class' => 'text-muted'],
    ]); ?>

    <?php if ($mermaidGraph): ?>
    <div class="card mt-4 mb-4">
        <div class="card-header fw-semibold"><?= Yii::t('app', 'Career Graph') ?></div>
        <div class="card-body overflow-auto text-center">
            <pre class="mermaid d-inline-block text-start"><?= $mermaidGraph ?></pre>
        </div>
    </div>
    <?php $this->registerJsFile(
        'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js',
        ['position' => \yii\web\View::POS_END]
    ); ?>
    <?php $this->registerJs('mermaid.initialize({ startOnLoad: true, theme: "default" });'); ?>
    <?php $this->registerJs('
        mermaid.initialize({ startOnLoad: false, theme: "default" });
        mermaid.run({ querySelector: ".mermaid" }).then(function() {
            var svg = document.querySelector(".mermaid svg");
            if (svg) {
                var vb = svg.viewBox.baseVal;
                if (vb && vb.width > 0) {
                    var cardBody = svg.closest(".card-body");
                    var style = window.getComputedStyle(cardBody);
                    var padding = parseFloat(style.paddingLeft) + parseFloat(style.paddingRight);
                    var containerWidth = cardBody.clientWidth - padding;
                    var scale = containerWidth / vb.width;
                    svg.setAttribute("width",  containerWidth);
                    svg.setAttribute("height", vb.height * scale);
                    svg.style.maxWidth = "none";
                }
            }
        });
    '); ?>
    <?php endif; ?>

</div>
