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

<div class="prepare-fpl-form">
    <?php $form = ActiveForm::begin(); ?>
    <div class="row g-3 align-items-center">
        <div class="col-auto">
            <div>Aircraft identification</div>
            <div><?= Html::input('text', 'aircraftRegistration', $aircraft->registration, ['disabled' => true])?></div>
        </div>
        <div class="col-auto">
            <div>Aircraft type</div>
            <div><?= Html::input('text', 'aircraftType', $aircraft->aircraftType->icao_type_code, ['disabled' => true])?></div>
        </div>
        <div class="col-auto">
            <div>Flight Rules</div>
            <div><?= Html::dropDownList('flightRules', null, $flightRulesTypes)?></div>
        </div>
    </div>

    <div class="row g-3 align-items-center">
        <div class="col-auto">
            <div>Departure Aerodrome</div>
            <div><?= Html::input('text', 'departure', $route->departure, ['disabled' => true])?></div>
        </div>
        <div class="col-auto">
            <div>Cruising speed</div>
            <div>
                <?= Html::dropDownList('cruiseSpeedUnit', null, ['N', 'M', 'K'])?>
                <?= Html::input('text', 'cruiseSpeedValue', null, ['maxlength' => 4])?>
            </div>
        </div>
        <div class="col-auto">
            <div>Level</div>
            <div>
                <?= Html::dropDownList('levelUnit', null, ['F', 'A', 'S', 'M', 'VFR'])?>
                <?= Html::input('text', 'levelValue', null, ['maxlength' => 4])?>
            </div>
        </div>
    </div>
    <div class="row align-items-center">
        <div class="col-auto">
            <div>Route</div>
            <div><?= Html::input('textarea', 'route', null, ['rows' => 3])?></div>
        </div>
    </div>
    <div class="row g-2 align-items-center">
        <div class="col-auto">
            <div>Destination Aerodrome</div>
            <div><?= Html::input('text', 'destination', $route->arrival, ['disabled' => true])?></div>
        </div>
        <div class="col-auto">
            <div>Total EET</div>
            <div><?= Html::input('text', 'eet', null, ['maxlength' => 4])?></div>
        </div>
        <div class="col-auto">
            <div>Altn Aerodrome</div>
            <div><?= Html::input('text', 'altAerodrome', null, ['maxlength' => 4])?></div>
        </div>
        <div class="col-auto">
            <div>2nd Altn Aerodrome</div>
            <div><?= Html::input('text', '2ndaltAerodrome', null, ['maxlength' => 4])?></div>
        </div>
    </div>

    <div class="row">
        <div class="col-auto">
            <div>Other Information</div>
            <div><?= Html::input('textarea', 'otherInfo', null, ['rows' => 3])?></div>
        </div>
    </div>

    <div class="row g-3 align-items-center">
        <div class="col-auto">
            <div>Endurance</div>
            <div><?= Html::input('text', 'endurance', null, ['maxlength' => 4])?></div>
        </div>
        <div class="col-auto">
            <div>People on board</div>
            <div><?= Html::input('text', 'people', 'X', null)?></div>
        </div>
        <div class="col-auto">
            <div>Pilot in command</div>
            <div><?= Html::input('text', 'pilot', $pilotName, ['disabled' => true])?></div>
        </div>
    </div>
    <div class="row align-items-center">
        <div class="form-group col-auto">
            <?= Html::submitButton('Submit FPL', ['class' => 'btn btn-success']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

</div>
