<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlan $model */

$this->title = 'Update Submitted Flight Plan: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Submitted Flight Plans', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="submitted-flight-plan-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
