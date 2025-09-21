<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var $model app\models\TuModelo */

$form = ActiveForm::begin(); ?>

<div class="container">

    <div class="row mb-3">
            <div class="col-md-12">
                <?= $form->field($model, 'validator_comments')->textarea(['rows' => 3]) ?>
            </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Save validation', ['class' => 'btn btn-primary']) ?>
    </div>

</div>

<?php ActiveForm::end(); ?>
