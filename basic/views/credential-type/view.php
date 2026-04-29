<?php

use app\models\CredentialType;
use app\models\PilotCredential;
use app\rbac\constants\Permissions;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\CredentialType $model */
/** @var app\models\PilotCredential[] $currentCredentials */
/** @var array<int, array{canRenew: bool, canRevoke: bool, cascadeNames: string[]}> $credentialMeta */

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
            <?php if ($model->canDelete()): ?>
            <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data'  => [
                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    'method'  => 'post',
                ],
            ]) ?>
            <?php endif; ?>
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
            <?php $unlockedAircraftTypes = $model->aircraftTypes; ?>
            <?php if (empty($unlockedAircraftTypes)): ?>
                <p class="text-muted mb-0"><?= Yii::t('app', 'None') ?></p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($unlockedAircraftTypes as $aircraftType): ?>
                        <li><?= Html::encode($aircraftType->name) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php $restrictions = $model->airportAircraftRestrictions; ?>
    <?php if (!empty($restrictions)): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><?= Yii::t('app', 'Affected Airports') ?></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php
                        $seenAirports = [];
                        foreach ($restrictions as $r):
                            if (isset($seenAirports[$r->airport_icao])) continue;
                            $seenAirports[$r->airport_icao] = true;
                        ?>
                        <li>
                            <code><?= Html::encode($r->airport_icao) ?></code>
                            <?php if ($r->airport): ?>
                                — <?= Html::encode($r->airport->name) ?>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><?= Yii::t('app', 'Restricted Aircraft Types') ?></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php
                        $seenAircraft = [];
                        foreach ($restrictions as $r):
                            if (isset($seenAircraft[$r->aircraft_type_id])) continue;
                            $seenAircraft[$r->aircraft_type_id] = true;
                        ?>
                        <li><?= $r->aircraftType ? Html::encode($r->aircraftType->name) : Html::encode($r->aircraft_type_id) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><?= Yii::t('app', 'Pilots') ?></div>
        <div class="card-body">
            <?php if (empty($currentCredentials)): ?>
                <p class="text-muted mb-0"><?= Yii::t('app', 'None') ?></p>
            <?php else: ?>
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= Yii::t('app', 'Pilot') ?></th>
                        <th><?= Yii::t('app', 'Status') ?></th>
                        <th><?= Yii::t('app', 'Issued Date') ?></th>
                        <th><?= Yii::t('app', 'Expiry Date') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currentCredentials as $pc): ?>
                    <?php
                        if ($pc->isStudent()) {
                            $badge = '<span class="badge bg-info">' . Yii::t('app', 'Student') . '</span>';
                        } elseif ($pc->expiry_date !== null && $pc->expiry_date < date('Y-m-d')) {
                            $badge = '<span class="badge bg-danger">' . Yii::t('app', 'Expired') . '</span>';
                        } else {
                            $badge = '<span class="badge bg-success">' . Yii::t('app', 'Active') . '</span>';
                        }
                        $pcMeta       = $credentialMeta[$pc->id] ?? ['canRenew' => true, 'canRevoke' => true, 'cascadeNames' => []];
                        $cascadeNames = $pcMeta['cascadeNames'];
                        $revokeConfirm = empty($cascadeNames)
                            ? Yii::t('app', 'Are you sure you want to revoke this credential? This action cannot be undone.')
                            : Yii::t('app', 'Are you sure you want to revoke this credential? The following credentials will also be revoked: {names}. This action cannot be undone.', ['names' => implode(', ', $cascadeNames)]);
                    ?>
                    <tr>
                        <td><?= Html::a(Html::encode($pc->pilot->getFullName()), ['/pilot/view', 'id' => $pc->pilot_id]) ?></td>
                        <td><?= $badge ?></td>
                        <td><?= Html::encode($pc->issued_date) ?></td>
                        <td><?= $pc->expiry_date ? Html::encode($pc->expiry_date) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-nowrap">
                            <?= Html::a(Yii::t('app', 'View'), ['/pilot-credential/view', 'id' => $pc->id], ['class' => 'btn btn-sm btn-outline-secondary me-1']) ?>
                            <?php if (Yii::$app->user->can(Permissions::ISSUE_CREDENTIAL)): ?>
                                <?php if ($pcMeta['canRenew']): ?>
                                <?= Html::a(
                                    $pc->isStudent() ? Yii::t('app', 'Issue') : Yii::t('app', 'Renew'),
                                    $pc->isStudent() ? ['/pilot-credential/activate', 'id' => $pc->id] : ['/pilot-credential/renew', 'id' => $pc->id],
                                    ['class' => 'btn btn-sm btn-outline-primary me-1']
                                ) ?>
                                <?php endif; ?>
                                <?php if ($pcMeta['canRevoke']): ?>
                                <?= Html::a(Yii::t('app', 'Revoke'), ['/pilot-credential/revoke', 'id' => $pc->id], [
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'data'  => [
                                        'confirm' => $revokeConfirm,
                                        'method'  => 'post',
                                    ],
                                ]) ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>
