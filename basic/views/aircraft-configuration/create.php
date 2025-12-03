<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\AircraftConfiguration $model */

$this->title = Yii::t('app', 'Create Aircraft Configuration');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aircraft Configurations'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aircraft-configuration-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'aircraftTypes' => $aircraftTypes,
    ]) ?>

</div>
