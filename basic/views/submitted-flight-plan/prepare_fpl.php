<?php

use app\models\Aircraft;
use app\models\Route;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlanSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Flight Plan Submission';
$this->params['breadcrumbs'][] = $this->title;
//$this->params['route_id'] = $route->id;
?>
<div class="submitted-flight-plan-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>Aircraft identification: <?= Html::encode($aircraft->registration) ?></p>
    <p>Flight Rules: I/V/Z...</p>
    <p>Aircraft type: <?= Html::encode($aircraft->aircraftType->icao_type_code) ?></p>
    <p>Departure Aerodrome: <?= Html::encode($route->departure) ?></p>
    <p>Cruising speed: N/M/... XXXX </p>
    <p>Level: F/VFR/... XXXX </p>
    <p>Route:..... </p>
    <p>Destination Aerodrome: <?= Html::encode($route->arrival) ?></p>
    <p>Total EET: 0128</p>
    <p>Alternative Aerodrome: ...</p>
    <p>2nd Alternative Aerodrome: ...</p>
    <p>Other Information: ...</p>
    <p>Endurance: 4000</p>
    <p>People on board: X</p>
    <p>Pilot in command: XXXX</p>

    <p>SUBMIT</p>


</div>
