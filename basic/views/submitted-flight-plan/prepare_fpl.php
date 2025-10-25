<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlan $model */

$this->title = 'Flight Plan Submission';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="submitted-flight-plan-create">

    <h1><?= Html::encode($this->title) ?></h1>
    <h3><?= Html::encode($entity->fplDescription) ?></h3>
    <br/>

    <?= $this->render('_form', [
        'model' => $model,
        'aircraft' => $aircraft,
        'entity' => $entity,
        'pilotName' => $pilotName,
        'mode' => 'prepare',
    ]) ?>

</div>
