<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\AircraftConfiguration $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="aircraft-configuration-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'aircraft_type_id')->textInput() ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'pax_capacity')->textInput() ?>

    <?= $form->field($model, 'cargo_capacity')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
