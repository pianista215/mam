<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Configuración del sitio';
?>

<h1><?= $this->title ?></h1>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="alert alert-success">
        <?= Yii::$app->session->getFlash('success') ?>
    </div>
<?php endif; ?>

<?php $form = ActiveForm::begin(); ?>

<h3>Registro</h3>
<?= $form->field($model, 'registration_start')->input('date') ?>
<?= $form->field($model, 'registration_end')->input('date') ?>
<?= $form->field($model, 'registration_start_location') ?>

<h3>Almacenamiento</h3>
<?= $form->field($model, 'chunks_storage_path') ?>
<?= $form->field($model, 'images_storage_path') ?>

<h3>Seguridad</h3>
<?= $form->field($model, 'token_life_h')->input('number') ?>
<?= $form->field($model, 'charter_ratio')->input('number', ['step' => '0.01']) ?>

<h3>Global</h3>
<?= $form->field($model, 'airline_name') ?>
<?= $form->field($model, 'no_reply_mail') ?>
<?= $form->field($model, 'support_mail') ?>

<h3>Footer</h3>
<?= $form->field($model, 'x_url') ?>
<?= $form->field($model, 'instagram_url') ?>
<?= $form->field($model, 'facebook_url') ?>

<div class="form-group">
    <?= Html::submitButton('Guardar cambios', ['class' => 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end(); ?>
