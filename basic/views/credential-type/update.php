<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\CredentialType $model */
/** @var array $credentialTypes */
/** @var array $aircraftTypes */

$this->title = Yii::t('app', 'Update Credential Type') . ': ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Credential Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="credential-type-update">

    <?= $this->render('_form', [
        'model'           => $model,
        'credentialTypes' => $credentialTypes,
        'aircraftTypes'   => $aircraftTypes,
    ]) ?>

</div>
