<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\FlightSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="flight-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'pilot_id') ?>

    <?= $form->field($model, 'aircraft_id') ?>

    <?= $form->field($model, 'code') ?>

    <?= $form->field($model, 'departure') ?>

    <?php // echo $form->field($model, 'arrival') ?>

    <?php // echo $form->field($model, 'alternative1_icao') ?>

    <?php // echo $form->field($model, 'alternative2_icao') ?>

    <?php // echo $form->field($model, 'cruise_speed_value') ?>

    <?php // echo $form->field($model, 'cruise_speed_unit') ?>

    <?php // echo $form->field($model, 'flight_level_value') ?>

    <?php // echo $form->field($model, 'flight_level_unit') ?>

    <?php // echo $form->field($model, 'route') ?>

    <?php // echo $form->field($model, 'estimated_time') ?>

    <?php // echo $form->field($model, 'other_information') ?>

    <?php // echo $form->field($model, 'endurance_time') ?>

    <?php // echo $form->field($model, 'report_tool') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'creation_date') ?>

    <?php // echo $form->field($model, 'network') ?>

    <?php // echo $form->field($model, 'flight_rules') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
