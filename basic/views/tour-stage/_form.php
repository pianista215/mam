<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\TourStage $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="tour-stage-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'tour_id')->textInput() ?>

    <?= $form->field($model, 'departure')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'arrival')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'distance_nm')->textInput() ?>

    <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'sequence')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
