<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\TourStage $model */
/** @var app\models\Tour $tour */

?>

<div class="tour-stage-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'departure')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'arrival')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save Stage', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Cancel', ['tour/view', 'id' => $tour->id], ['class' => 'btn btn-secondary ms-2']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
