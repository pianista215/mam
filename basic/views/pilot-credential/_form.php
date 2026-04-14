<?php

use app\models\PilotCredential;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\PilotCredential $model */
/** @var array $credentialTypes  id => label map, only present on issue */
/** @var bool $showTypeField */
?>
<div class="pilot-credential-form">
    <?php $form = ActiveForm::begin(); ?>

    <?php if (!empty($showTypeField)): ?>
        <?= $form->field($model, 'credential_type_id')->dropDownList(
            $credentialTypes ?? [],
            ['prompt' => Yii::t('app', 'Select credential type...')]
        ) ?>
    <?php endif; ?>

    <?= $form->field($model, 'status')->radioList(PilotCredential::statusLabels()) ?>

    <?= $form->field($model, 'issued_date')->input('date', ['value' => $model->issued_date ?: date('Y-m-d')]) ?>

    <?= $form->field($model, 'expiry_date')->input('date')->hint(Yii::t('app', 'Leave empty if the credential does not expire.')) ?>

    <?= $form->field($model, 'notes')->textarea(['rows' => 3]) ?>

    <?= Html::hiddenInput('PilotCredential[pilot_id]', $model->pilot_id) ?>
    <?= Html::hiddenInput('PilotCredential[issued_by]', Yii::$app->user->id) ?>

    <div class="form-group mt-3">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
