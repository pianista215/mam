<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\PilotCredential $model    The new (unsaved) record pre-filled from $current */
/** @var app\models\PilotCredential $current  The record being superseded */

$pilot          = $current->pilot;
$credentialType = $current->credentialType;

$isStudent = $current->isStudent();
$actionLabel = $isStudent ? Yii::t('app', 'Issue') : Yii::t('app', 'Renew');

$this->title = $isStudent
    ? Yii::t('app', 'Issue Credential: {code} — {name}', ['code' => $credentialType->code, 'name' => $pilot->fullName])
    : Yii::t('app', 'Renew Credential: {code} — {name}', ['code' => $credentialType->code, 'name' => $pilot->fullName]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pilots'), 'url' => ['/pilot/index']];
$this->params['breadcrumbs'][] = ['label' => $pilot->fullName, 'url' => ['/pilot/view', 'id' => $pilot->id]];
$this->params['breadcrumbs'][] = ['label' => $credentialType->code, 'url' => ['view', 'id' => $current->id]];
$this->params['breadcrumbs'][] = $actionLabel;
?>
<div class="pilot-credential-renew container py-3">

    <h1><?= Html::encode($this->title) ?> <span class="badge bg-secondary ms-1" style="font-size:0.5em; vertical-align:middle"><?= Html::encode($credentialType->getTypeLabel()) ?></span></h1>

    <div class="alert alert-info mb-4">
        <strong><?= Yii::t('app', 'Current record will be closed:') ?></strong>
        <?= Yii::t('app', 'Status') ?>: <strong><?= Html::encode($current->getStatusLabel()) ?></strong> &nbsp;|&nbsp;
        <?= Yii::t('app', 'Issued Date') ?>: <strong><?= Html::encode($current->issued_date) ?></strong> &nbsp;|&nbsp;
        <?= Yii::t('app', 'Expiry Date') ?>: <strong><?= $current->expiry_date ? Html::encode($current->expiry_date) : '—' ?></strong>
    </div>

    <?= $this->render('_form', [
        'model'         => $model,
        'showTypeField' => false,
    ]) ?>

</div>
