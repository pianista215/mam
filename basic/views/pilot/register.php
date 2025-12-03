<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Pilot $model */

$this->title = Yii::t('app', 'Register Pilot');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pilots'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pilot-register">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_register_form', [
        'model' => $model,
        'countries' => $countries,
    ]) ?>

</div>
