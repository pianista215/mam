<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\AircraftType $model */

$this->title = 'Create Aircraft Type';
$this->params['breadcrumbs'][] = ['label' => 'Aircraft Types', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aircraft-type-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
