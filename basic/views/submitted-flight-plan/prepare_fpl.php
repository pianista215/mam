<?php

use app\models\Aircraft;
use app\models\Route;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlanSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var yii\widgets\ActiveForm $form */

$this->title = 'Flight Plan Submission';
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<div class="container">

    <?php $form = ActiveForm::begin([
            'options' => ['class' => 'row'], // Agrega la clase 'row' para usar el sistema de grillas de Bootstrap
        ]); ?>

    <div class="row mb-3">
        <div class="col-md-4">
            <div>Aircraft identification</div>
            <div><?= Html::input('text', 'aircraftRegistration', $aircraft->registration, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div>Aircraft type</div>
            <div><?= Html::input('text', 'aircraftType', $aircraft->aircraftType->icao_type_code, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div><?= $form->field($model, 'flight_rules')->dropDownList($model->flightRulesTypes, ['class' => 'form-control'])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div>Departure Aerodrome</div>
            <div><?= Html::input('text', 'departure', $route->departure, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
    <!-- TODO: REVIEW HOW TO MAKE THIS SELECT AND INPUT APPEAR TOGETHER USING FORM FIELD-->
        <div class="col-md-4">
            <div>Cruising speed</div>
            <div class="input-group">
                <?= $form->field($model, 'cruise_speed_unit', ['options' => ['tag' => false]])->dropDownList(array_combine($model->validSpeedUnits, $model->validSpeedUnits), ['class' => 'form-select flex-grow-0 w-auto'])->label(false)?>
                <?= $form->field($model, 'cruise_speed_value', ['options' => ['tag' => false]])->textInput(['maxlength' => true, 'class' => 'form-control'])->label(false)?>
            </div>
            <div class="invalid-feedback d-block">
                <?= Html::error($model, 'cruise_speed_unit', ['class' => 'd-block']) ?>
                <?= Html::error($model, 'cruise_speed_value', ['class' => 'd-block']) ?>
            </div>
        </div>
        <div class="col-md-4">
            <div>Level</div>
            <div class="input-group">
                <?= $form->field($model, 'flight_level_unit', ['options' => ['tag' => false]])->dropDownList(array_combine($model->validFlightLevelUnits, $model->validFlightLevelUnits), ['id' => 'flight_level_unit', 'class' => 'form-select flex-grow-0 w-auto'])->label(false)?>
                <?= $form->field($model, 'flight_level_value', ['options' => ['tag' => false]])->textInput(['maxlength' => true, 'disabled' => ($model->flight_level_unit == 'VFR'), 'id' => 'flight_level_value', 'class' => 'form-control'])->label(false)?>
            </div>
            <div class="invalid-feedback d-block">
                <?= Html::error($model, 'flight_level_unit', ['class' => 'd-block']) ?>
                <?= Html::error($model, 'flight_level_value', ['class' => 'd-block']) ?>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-12">
            <div><?= $form->field($model, 'route')->textarea(['rows' => 3, 'class' => 'form-control'])?></div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <div>Destination Aerodrome</div>
            <div><?= Html::input('text', 'destination', $route->arrival, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div><?= $form->field($model, 'estimated_time')->textInput(['maxlength' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div><?= $form->field($model, 'alternative1_icao')->textInput(['maxlength' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div><?= $form->field($model, 'alternative2_icao')->textInput(['maxlength' => true, 'class' => 'form-control'])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div><?= $form->field($model, 'other_information')->textarea(['rows' => 3, 'class' => 'form-control'])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div><?= $form->field($model, 'endurance_time')->textInput(['maxlength' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div>People on board</div>
            <div><?= Html::input('text', 'people', 'X', ['maxlength' => 3, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div>Pilot in command</div>
            <div><?= Html::input('text', 'pilot', $pilotName, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
    </div>
    <div class="row align-items-center">
        <div class="col-12 text-center">
            <?= Html::submitButton('Submit FPL', ['class' => 'btn btn-success w-100']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

</div>

<?php
$this->registerJs(<<<JS
    $('#flight_level_unit').on('change', function() {
        const input = $('#flight_level_value');
        if ($(this).val() === 'VFR') {
            input.prop('value', "");
            input.prop('disabled', true);
        } else {
            input.prop('disabled', false);
        }
    });
JS);
?>