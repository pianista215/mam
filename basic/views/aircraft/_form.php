<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Aircraft $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="aircraft-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'aircraft_type_id')->dropdownList(
        $aircraftTypes,
        ['prompt'=>'Select Aircraft Type']
        ); ?>

    <?= $form->field($model, 'registration')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'location')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'hours_flown')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
