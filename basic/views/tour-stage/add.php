<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\TourStage $model */
/** @var app\models\Tour $tour */

$this->title = "Add Stage {$model->sequence} to Tour: " . Html::encode($tour->name);
$this->params['breadcrumbs'][] = ['label' => 'Tours', 'url' => ['index']];
$this->params['breadcrumbs'][] = [
    'label' => $tour->name,
    'url' => ['tour/view', 'id' => $tour->id],
];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tour-stage-create container mt-4">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <?= $this->render('_form', [
                'model' => $model,
                'tour' => $tour,
            ]) ?>
        </div>
    </div>
</div>
