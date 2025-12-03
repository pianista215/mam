<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Route $model */

$this->title = Yii::t('app', 'Create Route');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Routes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="route-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
