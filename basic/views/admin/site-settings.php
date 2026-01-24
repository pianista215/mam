<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = Yii::t('app', 'Site Settings');
?>

<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(); ?>

<?php if ($model->hasErrors()): ?>
    <div class="alert alert-danger">
        <strong><?= Yii::t('app', 'Please fix the following errors:') ?></strong>
        <ul>
            <?php foreach ($model->getErrors() as $attrErrors): ?>
                <?php foreach ($attrErrors as $error): ?>
                    <li><?= Html::encode($error) ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<h3><?= Yii::t('app', 'Global') ?></h3>
<?= $form->field($model, 'airline_name') ?>
<?= $form->field($model, 'no_reply_mail') ?>
<?= $form->field($model, 'support_mail') ?>

<h3><?= Yii::t('app', 'Registration Settings') ?></h3>
<?= $form->field($model, 'registration_start')->input('date') ?>
<?= $form->field($model, 'registration_end')->input('date') ?>
<?= $form->field($model, 'registration_start_location')->textInput(['maxlength' => true]) ?>

<h3><?= Yii::t('app', 'RRSS') ?></h3>
<?= $form->field($model, 'x_url') ?>
<?= $form->field($model, 'instagram_url') ?>
<?= $form->field($model, 'facebook_url') ?>

<h3><?= Yii::t('app', 'Other') ?></h3>
<?= $form->field($model, 'chunks_storage_path') ?>
<?= $form->field($model, 'images_storage_path') ?>
<?= $form->field($model, 'acars_releases_path') ?>
<?= $form->field($model, 'acars_installer_name') ?>
<?= $form->field($model, 'token_life_h')->input('number') ?>
<?= $form->field($model, 'charter_ratio')->input('number', ['step' => '0.01']) ?>

<div class="form-group">
    <?= Html::submitButton(Yii::t('app','Save'), ['class' => 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end(); ?>
