<?php

use app\models\Aircraft;
use app\models\Route;
use yii\helpers\Html;
use yii\widgets\ActiveForm;


/** @var app\models\SubmittedFlightPlan $model */
/** @var yii\web\View $this */
/** @var yii\widgets\ActiveForm $form */
/** @var string $mode */

?>

<div class="container">

    <?php $form = ActiveForm::begin([
            'options' => ['class' => 'row'],
        ]); ?>

    <div class="row mb-3">
        <div class="col-md-4">
            <div><?=Yii::t('app', 'Aircraft identification')?></div>
            <div><?= Html::input('text', 'aircraftRegistration', $aircraft->registration, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div><?=Yii::t('app', 'Aircraft type')?></div>
            <div><?= Html::input('text', 'aircraftType', $aircraft->aircraftConfiguration->aircraftType->icao_type_code, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div><?= $form->field($model, 'flight_rules')->dropDownList($model->flightRulesTypes, ['class' => 'form-control', 'disabled' => ($mode == 'view')])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div><?=Yii::t('app', 'Departure Aerodrome')?></div>
            <div><?= Html::input('text', 'departure', $entity->departure, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div><?=Yii::t('app', 'Cruising speed')?></div>
            <div class="input-group">
                <?= $form->field($model, 'cruise_speed_unit', ['enableClientValidation'=> false, 'options' => ['tag' => false]])->dropDownList(array_combine($model->validSpeedUnits, $model->validSpeedUnits), ['class' => 'form-select flex-grow-0 w-auto', 'disabled' => ($mode == 'view')])->label(false)?>
                <?= $form->field($model, 'cruise_speed_value', ['enableClientValidation'=> false, 'options' => ['tag' => false]])->textInput(['maxlength' => true, 'class' => 'form-control', 'readonly' => ($mode == 'view')])->label(false)?>
            </div>
        </div>
        <div class="col-md-4">
            <div><?=Yii::t('app', 'Level')?></div>
            <div class="input-group">
                <?= $form->field($model, 'flight_level_unit', ['enableClientValidation'=> false, 'options' => ['tag' => false]])->dropDownList(array_combine($model->validFlightLevelUnits, $model->validFlightLevelUnits), ['id' => 'flight_level_unit', 'class' => 'form-select flex-grow-0 w-auto', 'disabled' => ($mode == 'view')])->label(false)?>
                <?= $form->field($model, 'flight_level_value', ['enableClientValidation'=> false, 'options' => ['tag' => false]])->textInput(['maxlength' => true, 'readonly' => ($model->flight_level_unit == 'VFR' || $mode == 'view'), 'id' => 'flight_level_value', 'class' => 'form-control'])->label(false)?>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-12">
            <div><?= $form->field($model, 'route')->textarea(['rows' => 3, 'class' => 'form-control', 'readonly' => ($mode == 'view')])?></div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <div><?=Yii::t('app', 'Destination Aerodrome')?></div>
            <div><?= Html::input('text', 'destination', $entity->arrival, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div><?= $form->field($model, 'estimated_time')->textInput(['maxlength' => true, 'class' => 'form-control', 'readonly' => ($mode == 'view')])?></div>
        </div>
        <div class="col-md-3">
            <div><?= $form->field($model, 'alternative1_icao')->textInput(['maxlength' => true, 'class' => 'form-control', 'readonly' => ($mode == 'view')])?></div>
        </div>
        <div class="col-md-3">
            <div><?= $form->field($model, 'alternative2_icao')->textInput(['maxlength' => true, 'class' => 'form-control', 'readonly' => ($mode == 'view')])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div><?= $form->field($model, 'other_information')->textarea(['rows' => 3, 'class' => 'form-control', 'readonly' => ($mode == 'view')])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div><?= $form->field($model, 'endurance_time')->textInput(['maxlength' => true, 'class' => 'form-control', 'readonly' => ($mode == 'view')])?></div>
        </div>
        <div class="col-md-4">
            <div><?=Yii::t('app', 'People on board')?></div>
            <div><?= Html::input('text', 'people', 'X', ['disabled' => true, 'maxlength' => 3, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div><?=Yii::t('app', 'Pilot in command')?></div>
            <div><?= Html::input('text', 'pilot', $pilotName, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
    </div>
    <div class="row align-items-center">
        <div class="col-12 text-center">
            <?php if ($mode != 'view'): ?>
                <?= Html::submitButton(Yii::t('app', 'Submit FPL'), ['class' => 'btn btn-success w-100']) ?>
            <?php endif; ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

</div>

<?php
if ($mode != 'view'){
$this->registerJs(<<<JS
    $('#flight_level_unit').on('change', function() {
        const input = $('#flight_level_value');
        if ($(this).val() === 'VFR') {
            input.prop('value', "");
            input.prop('readonly', true);
        } else {
            input.prop('readonly', false);
        }
    });
JS);
}
?>