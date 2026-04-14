<?php

use app\models\PilotCredential;
use app\rbac\constants\Permissions;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\PilotCredential $model */
/** @var app\models\PilotCredential[] $history */

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

        <?php if (Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL) && $model->superseded_at === null): ?>
        <div class="flex-shrink-0">
            <?= Html::a(
                $model->isStudent() ? Yii::t('app', 'Issue') : Yii::t('app', 'Renew'),
                ['renew', 'id' => $model->id],
                ['class' => 'btn btn-primary me-2']
            ) ?>
            <?= Html::a(Yii::t('app', 'Revoke'), ['revoke', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data'  => [
                    'confirm' => Yii::t('app', 'This action will close the current credential record. Are you sure?'),
                    'method'  => 'post',
                ],
            ]) ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-4">
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
                <dd class="col-sm-9">
                    <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                    <?php if ($model->superseded_at !== null): ?>
                        <span class="badge bg-secondary ms-1"><?= Yii::t('app', 'Superseded') ?></span>
                    <?php endif; ?>
                </dd>

                <dt class="col-sm-3"><?= Yii::t('app', 'Issued Date') ?></dt>
                <dd class="col-sm-9"><?= Html::encode($model->issued_date) ?></dd>

                <dt class="col-sm-3"><?= Yii::t('app', 'Expiry Date') ?></dt>
                <dd class="col-sm-9">
                    <?= $model->expiry_date ? Html::encode($model->expiry_date) : '<span class="text-muted">—</span>' ?>
                </dd>

                <?php if ($model->issuer): ?>
                <dt class="col-sm-3"><?= Yii::t('app', 'Issued By') ?></dt>
                <dd class="col-sm-9">
                    <?= Html::a(Html::encode($model->issuer->fullName), ['/pilot/view', 'id' => $model->issued_by]) ?>
                </dd>
                <?php endif; ?>

                <?php if ($model->notes): ?>
                <dt class="col-sm-3"><?= Yii::t('app', 'Notes') ?></dt>
                <dd class="col-sm-9"><?= nl2br(Html::encode($model->notes)) ?></dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><?= Yii::t('app', 'Previous Records') ?></div>
        <div class="card-body">
            <?php if (empty($history)): ?>
                <p class="text-muted mb-0"><?= Yii::t('app', 'No previous records.') ?></p>
            <?php else: ?>
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= Yii::t('app', 'Status') ?></th>
                        <th><?= Yii::t('app', 'Issued Date') ?></th>
                        <th><?= Yii::t('app', 'Expiry Date') ?></th>
                        <th><?= Yii::t('app', 'Superseded At') ?></th>
                        <th><?= Yii::t('app', 'Notes') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $record): ?>
                    <?php
                        if ($record->isStudent()) {
                            $rBadge = '<span class="badge bg-info">' . Yii::t('app', 'Student') . '</span>';
                        } elseif ($record->expiry_date !== null && $record->expiry_date < date('Y-m-d')) {
                            $rBadge = '<span class="badge bg-danger">' . Yii::t('app', 'Expired') . '</span>';
                        } else {
                            $rBadge = '<span class="badge bg-success">' . Yii::t('app', 'Active') . '</span>';
                        }
                    ?>
                    <tr>
                        <td><?= $rBadge ?></td>
                        <td><?= Html::encode($record->issued_date) ?></td>
                        <td><?= $record->expiry_date ? Html::encode($record->expiry_date) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= Html::encode($record->superseded_at) ?></td>
                        <td><?= $record->notes ? Html::encode($record->notes) : '<span class="text-muted">—</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>
