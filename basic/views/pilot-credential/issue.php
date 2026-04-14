<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\PilotCredential $model */
/** @var app\models\Pilot $pilot */
/** @var array $credentialTypes */

$this->title = Yii::t('app', 'Issue Credential to {name}', ['name' => $pilot->fullName]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pilots'), 'url' => ['/pilot/index']];
$this->params['breadcrumbs'][] = ['label' => $pilot->fullName, 'url' => ['/pilot/view', 'id' => $pilot->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Issue Credential');
?>
<div class="pilot-credential-issue container py-3">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model'           => $model,
        'credentialTypes' => $credentialTypes,
        'showTypeField'   => true,
    ]) ?>

</div>
