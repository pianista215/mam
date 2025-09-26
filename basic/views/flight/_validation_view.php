<?php
use yii\helpers\Html;

/** @var $model app\models\TuModelo */
?>

<div class="container">

    <div class="row mb-3">
        <div class="col-md-12">
            <div>Validator Comments</div>
            <?= Html::textarea('route', $model->validator_comments, [
                'rows' => 3,
                'class' => 'form-control',
                'readonly' => true
            ])?>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <div>Validator</div>
            <?= Html::input('text', 'validator', $model->validator->fullname, [
                'disabled' => true,
                'class' => 'form-control'
            ])?>
        </div>
        <div class="col-md-3">
            <div>Validation date</div>
            <?= Html::input('text', 'validation_date', $model->validation_date, [
                'disabled' => true,
                'class' => 'form-control'
            ])?>
        </div>
    </div>

</div>
