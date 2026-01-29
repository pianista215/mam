<?php

/** @var yii\web\View $this */
/** @var app\models\StatisticPeriod|null $period */
/** @var array $aggregates */
/** @var array $rankings */
/** @var array $records */

use yii\helpers\Html;

$this->title = Yii::t('app', 'All-Time Statistics');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Statistics'), 'url' => ['month']];
$this->params['breadcrumbs'][] = Yii::t('app', 'All-Time');
?>
<div class="statistics-all-time">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mb-4">
        <div class="col-12 text-end">
            <?= Html::a(Yii::t('app', 'View Monthly Statistics'), ['month'], ['class' => 'btn btn-outline-primary me-2']) ?>
            <?= Html::a(Yii::t('app', 'View Yearly Statistics'), ['year'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <?php if (!$period): ?>
        <div class="alert alert-info">
            <?= Yii::t('app', 'No statistics available yet.') ?>
        </div>
    <?php else: ?>
        <?= $this->render('_statistics_content', [
            'period' => $period,
            'aggregates' => $aggregates,
            'rankings' => $rankings,
            'records' => $records,
        ]) ?>
    <?php endif; ?>
</div>
