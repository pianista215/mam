<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Aircraft $model */

$this->title = 'Update Aircraft: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Aircrafts', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="aircraft-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'aircraftTypes' => $aircraftTypes,
    ]) ?>

</div>
