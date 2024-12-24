<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\AircraftConfiguration $model */

$this->title = 'Update Aircraft Configuration: ' . $model->fullname;
$this->params['breadcrumbs'][] = ['label' => 'Aircraft Configurations', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="aircraft-configuration-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'aircraftTypes' => $aircraftTypes,
    ]) ?>

</div>
