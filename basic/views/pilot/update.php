<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */

$this->title = 'Update Pilot: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Pilots', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="pilot-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
