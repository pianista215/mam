<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\AircraftType $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="aircraft-type-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'icao_type_code')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'max_nm_range')->textInput() ?>

    <?= $form->field($model, 'pax_capacity')->textInput() ?>

    <?= $form->field($model, 'cargo_capacity')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
