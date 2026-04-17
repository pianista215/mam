<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\PilotCredential $model */

$pilot          = $model->pilot;
$credentialType = $model->credentialType;
$isStudent      = $model->isStudent();
$actionLabel    = $isStudent ? Yii::t('app', 'Issue') : Yii::t('app', 'Renew');

$this->title = $isStudent
    ? Yii::t('app', 'Issue Credential: {code} — {name}', ['code' => $credentialType->code, 'name' => $pilot->fullName])
    : Yii::t('app', 'Renew Credential: {code} — {name}', ['code' => $credentialType->code, 'name' => $pilot->fullName]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pilots'), 'url' => ['/pilot/index']];
$this->params['breadcrumbs'][] = ['label' => $pilot->fullName, 'url' => ['/pilot/view', 'id' => $pilot->id]];
$this->params['breadcrumbs'][] = ['label' => $credentialType->code, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = $actionLabel;
?>
<div class="pilot-credential-renew container py-3">

    <h1><?= Html::encode($this->title) ?>
        <span class="badge bg-secondary ms-1" style="font-size:0.5em; vertical-align:middle"><?= Html::encode($credentialType->getTypeLabel()) ?></span>
    </h1>

    <?= $this->render('_form', [
        'model'         => $model,
        'showTypeField' => false,
        'isRenew'       => true,
    ]) ?>

</div>
