<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlan $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="submitted-flight-plan-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'aircraft_id')->textInput() ?>

    <?= $form->field($model, 'flight_rules')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'alternative1_icao')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'alternative2_icao')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'cruise_speed')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'flight_level')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'route')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'estimated_time')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'other_information')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'endurance_time')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'route_id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'pilot_id')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
