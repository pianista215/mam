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
            <div>Flight Rules</div>
            <div><?= Html::dropDownList('flightRules', null, $flightRulesTypes, ['class' => 'form-control'])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div>Departure Aerodrome</div>
            <div><?= Html::input('text', 'departure', $route->departure, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-4">
            <div>Cruising speed</div>
            <div class="input-group">
                <?= Html::dropDownList('cruiseSpeedUnit', null, ['N', 'M', 'K'], ['class' => 'form-select flex-grow-0 w-auto'])?>
                <?= Html::input('text', 'cruiseSpeedValue', null, ['maxlength' => 4, 'class' => 'form-control'])?>
            </div>
        </div>
        <div class="col-md-4">
            <div>Level</div>
            <div class="input-group">
                <?= Html::dropDownList('levelUnit', null, ['F', 'A', 'S', 'M', 'VFR'], ['class' => 'form-select flex-grow-0 w-auto'])?>
                <?= Html::input('text', 'levelValue', null, ['maxlength' => 4, 'class' => 'form-control'])?>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-12">
            <div>Route</div>
            <div><?= Html::textarea('route', null, ['rows' => 3, 'class' => 'form-control'])?></div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <div>Destination Aerodrome</div>
            <div><?= Html::input('text', 'destination', $route->arrival, ['disabled' => true, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div>Total EET</div>
            <div><?= Html::input('text', 'eet', null, ['maxlength' => 4, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div>Altn Aerodrome</div>
            <div><?= Html::input('text', 'altAerodrome', null, ['maxlength' => 4, 'class' => 'form-control'])?></div>
        </div>
        <div class="col-md-3">
            <div>2nd Altn Aerodrome</div>
            <div><?= Html::input('text', '2ndaltAerodrome', null, ['maxlength' => 4, 'class' => 'form-control'])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div>Other Information</div>
            <div><?= Html::textarea('otherInfo', null, ['rows' => 3, 'class' => 'form-control'])?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div>Endurance</div>
            <div><?= Html::input('text', 'endurance', null, ['maxlength' => 4, 'class' => 'form-control'])?></div>
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
