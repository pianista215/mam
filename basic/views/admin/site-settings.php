<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = Yii::t('app', 'Site Settings');
?>

<h1><?= $this->title ?></h1>

<?php $form = ActiveForm::begin(); ?>

<h3><?=Yii::t('app', 'Registration Settings')?></h3>
<?= $form->field($model, 'registration_start')->input('date') ?>
<?= $form->field($model, 'registration_end')->input('date') ?>
<?= $form->field($model, 'registration_start_location')->textInput(['maxlength' => true]) ?>

<h3><?=Yii::t('app', 'Storage')?></h3>
<?= $form->field($model, 'chunks_storage_path') ?>
<?= $form->field($model, 'images_storage_path') ?>

<h3><?=Yii::t('app', 'Security')?></h3>
<?= $form->field($model, 'token_life_h')->input('number') ?>
<?= $form->field($model, 'charter_ratio')->input('number', ['step' => '0.01']) ?>

<h3><?=Yii::t('app', 'Global')?></h3>
<?= $form->field($model, 'airline_name') ?>
<?= $form->field($model, 'no_reply_mail') ?>
<?= $form->field($model, 'support_mail') ?>

<h3><?=Yii::t('app', 'Footer')?></h3>
<?= $form->field($model, 'x_url') ?>
<?= $form->field($model, 'instagram_url') ?>
<?= $form->field($model, 'facebook_url') ?>

<div class="form-group">
    <?= Html::submitButton(Yii::t('app','Save'), ['class' => 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end(); ?>
