<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\AircraftType $model */

$this->title = Yii::t('app', 'Update Aircraft Type'). ': ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aircraft Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="aircraft-type-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
