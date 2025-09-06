<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="container">

    <div class="row mb-3">
        <div class="col-md-4">
            <div>Aircraft identification</div>
            <div><?= Html::input('text', 'aircraftRegistration', $aircraft->registration, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div>Aircraft type</div>
            <div><?= Html::input('text', 'aircraftType', $aircraft->aircraftConfiguration->aircraftType->icao_type_code, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div>Flight Rules</div>
            <div><?= Html::input('text', 'flight_rules', $model->flight_rules, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div>Departure Aerodrome</div>
            <div><?= Html::input('text', 'departure', $model->departure, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div>Cruising speed</div>
            <div class="input-group">
                <div><?= Html::input('text', 'cruise_speed', $model->cruise_speed_unit.$model->cruise_speed_value, ['disabled' => true, 'class' => 'form-control'])?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div>Level</div>
            <div class="input-group">
                <div><?= Html::input('text', 'cruise_speed', $model->flight_level_unit.$model->flight_level_value, ['disabled' => true, 'class' => 'form-control'])?></div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-12">
            <div>Route</div>
            <div><?= Html::textarea('route', $model->route, ['rows' => 3, 'class' => 'form-control', 'readonly' => true])?></div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <div>Destination Aerodrome</div>
            <div><?= Html::input('text', 'destination', $model->arrival, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div>Estimated Time</div>
            <div><?= Html::input('text', 'estimated_time', $model->estimated_time, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div>Alternative 1</div>
            <div><?= Html::input('text', 'alternative1_icao', $model->alternative1_icao, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div>Alternative 2</div>
            <div><?= Html::input('text', 'alternative2_icao', $model->alternative2_icao, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div>Other Information</div>
            <div><?= Html::textarea('other_information', $model->other_information, ['rows' => 3, 'class' => 'form-control', 'readonly' => true])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div>Endurance Time</div>
            <div><?= Html::input('text', 'endurance_time', $model->endurance_time, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div>People on board</div>
            <div><?= Html::input('text', 'people', 'X', ['disabled' => true, 'maxlength' => 3, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div>Pilot in command</div>
            <div><?= Html::input('text', 'pilot', $pilotName, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
    </div>

</div>
