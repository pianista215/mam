<?php

use app\rbac\constants\Permissions;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\PilotCredential $model */
/** @var bool $canRenew */
/** @var bool $canRevoke */
/** @var string[] $cascadeNames */

$pilot          = $model->pilot;
$credentialType = $model->credentialType;

$this->title = $credentialType->code . ' — ' . $pilot->fullName;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pilots'), 'url' => ['/pilot/index']];
$this->params['breadcrumbs'][] = ['label' => $pilot->fullName, 'url' => ['/pilot/view', 'id' => $pilot->id]];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

if ($model->isStudent()) {
    $badgeClass = 'bg-info';
    $badgeLabel = Yii::t('app', 'Student');
} elseif ($model->expiry_date !== null && $model->expiry_date < date('Y-m-d')) {
    $badgeClass = 'bg-danger';
    $badgeLabel = Yii::t('app', 'Expired');
} else {
    $badgeClass = 'bg-success';
    $badgeLabel = Yii::t('app', 'Active');
}
?>
<div class="pilot-credential-view container py-3">

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
        <h1 class="mb-0">
            <?= Html::encode($credentialType->code) ?>
            <small class="text-muted fs-5">— <?= Html::encode($credentialType->name) ?></small>
        </h1>

        <?php if (Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)): ?>
        <div class="flex-shrink-0">
            <?php if ($canRenew): ?>
            <?= Html::a(
                $model->isStudent() ? Yii::t('app', 'Issue') : Yii::t('app', 'Renew'),
                ['renew', 'id' => $model->id],
                ['class' => 'btn btn-primary me-2']
            ) ?>
            <?php endif; ?>
            <?php if ($canRevoke): ?>
            <?php
            $revokeConfirm = empty($cascadeNames)
                ? Yii::t('app', 'Are you sure you want to revoke this credential? This action cannot be undone.')
                : Yii::t('app', 'Are you sure you want to revoke this credential? The following credentials will also be revoked: {names}. This action cannot be undone.', ['names' => implode(', ', $cascadeNames)]);
            ?>
            <?= Html::a(Yii::t('app', 'Revoke'), ['revoke', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data'  => [
                    'confirm' => $revokeConfirm,
                    'method'  => 'post',
                ],
            ]) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3"><?= Yii::t('app', 'Pilot') ?></dt>
                <dd class="col-sm-9">
                    <?= Html::a(Html::encode($pilot->fullName), ['/pilot/view', 'id' => $pilot->id]) ?>
                </dd>

                <dt class="col-sm-3"><?= Yii::t('app', 'Credential Type') ?></dt>
                <dd class="col-sm-9">
                    <?= Html::a(
                        Html::encode($credentialType->code . ' — ' . $credentialType->name),
                        ['/credential-type/view', 'id' => $credentialType->id]
                    ) ?>
                    <span class="badge bg-secondary ms-1"><?= Html::encode($credentialType->getTypeLabel()) ?></span>
                </dd>

                <dt class="col-sm-3"><?= Yii::t('app', 'Status') ?></dt>
                <dd class="col-sm-9"><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></dd>

                <dt class="col-sm-3"><?= Html::encode($model->getIssuedDateLabel()) ?></dt>
                <dd class="col-sm-9"><?= Html::encode($model->issued_date) ?></dd>

                <dt class="col-sm-3"><?= Html::encode($model->getExpiryDateLabel()) ?></dt>
                <dd class="col-sm-9">
                    <?= $model->expiry_date ? Html::encode($model->expiry_date) : '<span class="text-muted">—</span>' ?>
                </dd>

                <?php if ($model->issuer): ?>
                <dt class="col-sm-3"><?= Yii::t('app', 'Issued By') ?></dt>
                <dd class="col-sm-9">
                    <?= Html::a(Html::encode($model->issuer->fullName), ['/pilot/view', 'id' => $model->issued_by]) ?>
                </dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>

</div>
