<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var $model app\models\Flight */

$form = ActiveForm::begin(['action' => ['validate', 'id' => $model->id]]); ?>

<div class="container">

    <div class="row mb-3">
            <div class="col-md-12">
                <?= $form->field($model, 'validator_comments')->textarea(['rows' => 3]) ?>
            </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Validate'), [
            'class' => 'btn btn-success',
            'name' => 'action',
            'value' => 'approve'
        ]) ?>
        <?= Html::submitButton(Yii::t('app', 'Reject'), [
            'class' => 'btn btn-danger',
            'name' => 'action',
            'value' => 'reject'
        ]) ?>
    </div>

</div>

<?php ActiveForm::end(); ?>
