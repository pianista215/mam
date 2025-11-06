<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Rank $model */

$this->title = 'Create Rank';
$this->params['breadcrumbs'][] = ['label' => 'Ranks', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="rank-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
