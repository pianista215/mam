<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */

$this->title = 'Create Pilot';
$this->params['breadcrumbs'][] = ['label' => 'Pilots', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pilot-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'countries' => $countries,
        'ranks' => $ranks
    ]) ?>

</div>
