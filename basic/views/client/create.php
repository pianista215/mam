<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Client $model */
/** @var string[] $hubIcaos */

$this->title = Yii::t('app', 'Create Client');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Clients'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="client-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'hubIcaos' => $hubIcaos,
    ]) ?>

</div>
