<?php

use app\models\CredentialType;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\CredentialType $model */
/** @var array $credentialTypes  id => label map of available credential types */
/** @var array $aircraftTypes    id => name map of available aircraft types */
/** @var yii\widgets\ActiveForm $form */
?>
<div class="credential-type-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'type')->dropDownList(
        CredentialType::typeLabels(),
        ['prompt' => '']
    ) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>

    <hr>

    <?= $form->field($model, 'prerequisiteIds')->checkboxList(
        $credentialTypes,
        ['separator' => '<br>']
    )->label(Yii::t('app', 'Prerequisites')) ?>

    <hr>

    <?= $form->field($model, 'aircraftTypeIds')->checkboxList(
        $aircraftTypes,
        ['separator' => '<br>']
    )->label(Yii::t('app', 'Unlocked Aircraft Types')) ?>

    <div class="form-group mt-3">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
