<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\ForgotPasswordForm $model */

$this->title = 'Reset Password';
?>

<h1><?= Html::encode($this->title) ?></h1>

<p>Enter your email address. If an account exists, you will receive a link to reset your password.</p>

<div class="forgot-password-form">

    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

        <div class="form-group">
            <?= Html::submitButton('Send Link', ['class' => 'btn btn-primary']) ?>
        </div>

    <?php ActiveForm::end(); ?>

</div>
