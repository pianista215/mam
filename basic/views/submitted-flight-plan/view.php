<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\SubmittedFlightPlan $model */

$this->title = 'Current Flight Plan';
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="submitted-flight-plan-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->user->can('crudOwnFpl', ['submittedFlightPlan' => $model])) : ?>
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
    <?php endif; ?>

    <?= $this->render('_fpl_header', [
            'entity' => $entity,
    ]) ?>

    <?= $this->render('_form', [
        'model' => $model,
        'aircraft' => $model->aircraft,
        'entity' => $entity,
        'pilotName' => $model->pilot->fullname,
        'mode' => 'view',
    ]) ?>

</div>
