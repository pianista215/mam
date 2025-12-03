<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Tour $model */

$this->title = Yii::t('app', 'Create Tour');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Tours'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tour-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
