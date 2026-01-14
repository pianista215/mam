<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\forms\ForgotPasswordForm $model */

$this->title = Yii::t('app', 'Reset Password');
?>

<h1><?= Html::encode($this->title) ?></h1>

<p><?=Yii::t('app', 'Enter your email address. If an account exists, you will receive a link to reset your password.')?></p>

<div class="forgot-password-form">

    <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', 'Send Link'), ['class' => 'btn btn-primary']) ?>
        </div>

    <?php ActiveForm::end(); ?>

</div>
