<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="pilot-form">

    <!-- TODO: REMOVE FROM PAGE-->
    <?= print_r($model->errors); ?>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'surname')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'password')->passwordInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'country_id')->dropdownList(
        $countries,
        ['prompt'=>'Select Country']
    ); ?>

    <?= $form->field($model, 'city')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'date_of_birth')->input('date') ?>

    <?= $form->field($model, 'vatsim_id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'ivao_id')->textInput(['maxlength' => true]) ?>


    TODO: CAPTCHA TO PREVENT A LOT OF REQUESTS

    <div class="form-group">
        <?= Html::submitButton('Register', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
