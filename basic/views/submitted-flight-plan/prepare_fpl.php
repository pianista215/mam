<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlan $model */

$this->title = 'Flight Plan Submission';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="submitted-flight-plan-create">

    <h1><?= Html::encode($this->title) ?></h1>
    <div class="container mb-3">
        <div class="row">
            <div class="col-md-12">
                <div class="p-3 border rounded bg-light-subtle">
                    <div class="fw-semibold fs-5 text-dark">
                        <?= Html::encode($entity->fplDescription) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'aircraft' => $aircraft,
        'entity' => $entity,
        'pilotName' => $pilotName,
        'mode' => 'prepare',
    ]) ?>

</div>
