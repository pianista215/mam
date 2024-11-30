<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlan $model */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Submitted Flight Plans', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="submitted-flight-plan-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'aircraft_id',
            'flight_rules',
            'alternative1_icao',
            'alternative2_icao',
            'cruise_speed_value',
            'flight_level_value',
            'route',
            'estimated_time',
            'other_information',
            'endurance_time',
            'route_id',
            'pilot_id',
            'cruise_speed_unit',
            'flight_level_unit',
        ],
    ]) ?>

</div>
