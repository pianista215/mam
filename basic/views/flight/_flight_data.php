<?php

use app\helpers\TimeHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>

<div class="container">

    <div class="row mb-3">
        <div class="col-md-3">
            <div>Flight start</div>
            <div><?= Html::input('text', 'start_time', $model->flightReport->start_time, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div>Flight end</div>
            <div><?= Html::input('text', 'end_time', $model->flightReport->end_time, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div>Landing Airport</div>
            <?php
            $landingAirport = $model->flightReport->landing_airport ?? 'Landed outside any known airport';
            ?>
            <div><?= Html::input('text', 'end_time', $landingAirport, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div>Network</div>
            <div><?= Html::input('text', 'network', $model->network, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <div>Block time</div>
            <div class="input-group">
                <div><?= Html::input('text', 'block_time_minutes', TimeHelper::formatHoursMinutes($model->flightReport->block_time_minutes / 60.0), ['disabled' => true, 'class' => 'form-control'])?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div>Flight Time</div>
                <div><?= Html::input('text', 'flight_time_minutes', TimeHelper::formatHoursMinutes($model->flightReport->flight_time_minutes / 60.0), ['disabled' => true, 'class' => 'form-control']) ?></div>
        </div>
        <div class="col-md-3">
            <div>Fuel consumption (Kg)</div>
            <div class="input-group">
                <div><?= Html::input('text', 'cruise_speed', $model->flightReport->total_fuel_burn_kg, ['disabled' => true, 'class' => 'form-control'])?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div>Distance (Nm)</div>
            <div class="input-group">
                <div><?= Html::input('text', 'distance_nm', $model->flightReport->distance_nm, ['disabled' => true, 'class' => 'form-control'])?></div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <div>Sim Aircraft Name</div>
            <div class="input-group">
                <div><?= Html::input('text', 'sim_aircraft_name', $model->flightReport->sim_aircraft_name, ['disabled' => true, 'class' => 'form-control'])?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div>Initial Fuel On Board (Kg)</div>
            <div class="input-group">
                <div><?= Html::input('text', 'initial_fuel_on_board', $model->flightReport->initial_fuel_on_board, ['disabled' => true, 'class' => 'form-control'])?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div>Zero Fuel Weight</div>
            <div class="input-group">
                <div><?= Html::input('text', 'zero_fuel_weight', $model->flightReport->zero_fuel_weight, ['disabled' => true, 'class' => 'form-control'])?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div>Status</div>
            <div class="input-group">
                <div><?= Html::input('text', 'status', $model->fullStatus, ['disabled' => true, 'class' => 'form-control'])?></div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <div>Report Tool</div>
            <div><?= Html::input('text', 'report_tool', $model->report_tool, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-12">
            <div>Pilot Comments</div>
            <div><?= Html::textarea('route', $model->flightReport->pilot_comments, ['rows' => 3, 'class' => 'form-control', 'readonly' => true])?></div>
        </div>
    </div>

</div>
