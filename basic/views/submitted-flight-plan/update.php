<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlan $model */

$this->title = 'Updating Flight Plan';
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="submitted-flight-plan-update">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3><?= Html::encode($entity->fplDescription) ?></h3>

    <?= $this->render('_form', [
        'model' => $model,
        'aircraft' => $model->aircraft,
        'entity' => $entity,
        'pilotName' => $model->pilot->fullname,
        'mode' => 'update',
    ]) ?>

</div>
