<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\ChangePasswordForm $model */
/** @var int $id */
/** @var string $token */

$this->title = 'Reset Password';
?>

<h1><?= Html::encode($this->title) ?></h1>

<p>Please enter your new password:</p>

<?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'password')->passwordInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save New Password', ['class' => 'btn btn-primary']) ?>
    </div>

<?php ActiveForm::end(); ?>
