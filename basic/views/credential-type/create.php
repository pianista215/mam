<?php

/** @var yii\web\View $this */
/** @var app\models\CredentialType $model */

$this->title = Yii::t('app', 'Create Credential Type');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Credential Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="credential-type-create">

    <?= $this->render('_form', ['model' => $model]) ?>

</div>
