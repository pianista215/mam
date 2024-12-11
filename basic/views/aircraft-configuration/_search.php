<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\AircraftConfigurationSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="aircraft-configuration-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'aircraft_type_id') ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'pax_capacity') ?>

    <?= $form->field($model, 'cargo_capacity') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
