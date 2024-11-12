<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Aircraft $model */

$this->title = 'Create Aircraft';
$this->params['breadcrumbs'][] = ['label' => 'Aircrafts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aircraft-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
