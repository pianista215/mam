<?php

use app\models\CredentialType;
use app\rbac\constants\Permissions;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\CredentialType $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Credential Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="credential-type-view container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>

        <?php if (Yii::$app->user->can(Permissions::CREDENTIAL_CRUD)): ?>
        <div>
            <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary me-2']) ?>
            <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data'  => [
                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    'method'  => 'post',
                ],
            ]) ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3"><?= Yii::t('app', 'Code') ?></dt>
                <dd class="col-sm-9"><code><?= Html::encode($model->code) ?></code></dd>

                <dt class="col-sm-3"><?= Yii::t('app', 'Type') ?></dt>
                <dd class="col-sm-9"><?= Html::encode($model->getTypeLabel()) ?></dd>

                <?php if ($model->description): ?>
                <dt class="col-sm-3"><?= Yii::t('app', 'Description') ?></dt>
                <dd class="col-sm-9"><?= nl2br(Html::encode($model->description)) ?></dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><?= Yii::t('app', 'Prerequisites') ?></div>
                <div class="card-body">
                    <?php $parents = $model->parentCredentialTypes; ?>
                    <?php if (empty($parents)): ?>
                        <p class="text-muted mb-0"><?= Yii::t('app', 'None') ?></p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($parents as $parent): ?>
                                <li>
                                    <?= Html::a(Html::encode($parent->name), ['view', 'id' => $parent->id]) ?>
                                    <span class="badge bg-secondary ms-1"><?= Html::encode($parent->getTypeLabel()) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><?= Yii::t('app', 'Unlocked Credentials') ?></div>
                <div class="card-body">
                    <?php $children = $model->childCredentialTypes; ?>
                    <?php if (empty($children)): ?>
                        <p class="text-muted mb-0"><?= Yii::t('app', 'None') ?></p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($children as $child): ?>
                                <li>
                                    <?= Html::a(Html::encode($child->name), ['view', 'id' => $child->id]) ?>
                                    <span class="badge bg-secondary ms-1"><?= Html::encode($child->getTypeLabel()) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><?= Yii::t('app', 'Unlocked Aircraft Types') ?></div>
        <div class="card-body">
            <?php $aircraftTypes = $model->aircraftTypes; ?>
            <?php if (empty($aircraftTypes)): ?>
                <p class="text-muted mb-0"><?= Yii::t('app', 'None') ?></p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($aircraftTypes as $aircraftType): ?>
                        <li><?= Html::encode($aircraftType->name) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

</div>
