<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlan $model */

$this->title = 'Create Submitted Flight Plan';
$this->params['breadcrumbs'][] = ['label' => 'Submitted Flight Plans', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="submitted-flight-plan-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
