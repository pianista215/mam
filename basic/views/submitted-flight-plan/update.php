<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlan $model */

$this->title = Yii::t('app', 'Updating Flight Plan');
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="submitted-flight-plan-update">

    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_fpl_header', [
            'entity' => $entity,
    ]) ?>

    <?= $this->render('_form', [
        'model' => $model,
        'aircraft' => $model->aircraft,
        'entity' => $entity,
        'pilotName' => $model->pilot->fullname,
        'mode' => 'update',
    ]) ?>

</div>
