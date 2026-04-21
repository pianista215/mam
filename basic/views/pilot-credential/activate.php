<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\PilotCredential $model */

$pilot          = $model->pilot;
$credentialType = $model->credentialType;

$this->title = Yii::t('app', 'Issue Credential: {code} — {name}', ['code' => $credentialType->code, 'name' => $pilot->fullName]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pilots'), 'url' => ['/pilot/index']];
$this->params['breadcrumbs'][] = ['label' => $pilot->fullName, 'url' => ['/pilot/view', 'id' => $pilot->id]];
$this->params['breadcrumbs'][] = ['label' => $credentialType->code, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Issue');
?>
<div class="pilot-credential-activate container py-3">

    <h1><?= Html::encode($this->title) ?>
        <span class="badge bg-info ms-1" style="font-size:0.5em; vertical-align:middle"><?= Yii::t('app', 'Student') ?></span>
        <span class="badge bg-secondary ms-1" style="font-size:0.5em; vertical-align:middle"><?= Html::encode($credentialType->getTypeLabel()) ?></span>
    </h1>

    <?= $this->render('_form', [
        'model'         => $model,
        'showTypeField' => false,
    ]) ?>

</div>
