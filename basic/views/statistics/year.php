<?php

/** @var yii\web\View $this */
/** @var app\models\StatisticPeriod|null $period */
/** @var int $year */
/** @var array $aggregates */
/** @var array $rankings */
/** @var array $records */
/** @var app\models\StatisticPeriod[] $availableYears */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Yearly Statistics') . ' - ' . $year;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Statistics'), 'url' => ['month']];
$this->params['breadcrumbs'][] = $year;
?>
<div class="statistics-year">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mb-4">
        <div class="col-md-4">
            <?= $this->render('_period_selector', [
                'availablePeriods' => $availableYears,
                'currentYear' => $year,
                'currentMonth' => null,
                'action' => 'year',
                'monthNames' => [],
            ]) ?>
        </div>
        <div class="col-md-8 d-flex flex-wrap align-items-center justify-content-center justify-content-md-end gap-2 mt-3 mt-md-0">
            <?= Html::a(Yii::t('app', 'View Monthly Statistics'), ['month'], ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::a(Yii::t('app', 'View All-Time Statistics'), ['all-time'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <?php if (!$period): ?>
        <div class="alert alert-info">
            <?= Yii::t('app', 'No statistics available for this period.') ?>
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
