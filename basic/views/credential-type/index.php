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

</div>
