<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlanSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="submitted-flight-plan-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'aircraft_id') ?>

    <?= $form->field($model, 'flight_rules') ?>

    <?= $form->field($model, 'alternative1_icao') ?>

    <?= $form->field($model, 'alternative2_icao') ?>

    <?php // echo $form->field($model, 'cruise_speed_value') ?>

    <?php // echo $form->field($model, 'flight_level_value') ?>

    <?php // echo $form->field($model, 'route') ?>

    <?php // echo $form->field($model, 'estimated_time') ?>

    <?php // echo $form->field($model, 'other_information') ?>

    <?php // echo $form->field($model, 'endurance_time') ?>

    <?php // echo $form->field($model, 'route_id') ?>

    <?php // echo $form->field($model, 'pilot_id') ?>

    <?php // echo $form->field($model, 'cruise_speed_unit') ?>

    <?php // echo $form->field($model, 'flight_level_unit') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
