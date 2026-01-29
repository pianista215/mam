<?php

/** @var yii\web\View $this */
/** @var app\models\StatisticPeriod|null $period */
/** @var int $year */
/** @var int $month */
/** @var array $aggregates */
/** @var array $rankings */
/** @var array $records */
/** @var app\models\StatisticPeriod[] $availableMonths */

use app\models\StatisticAggregateType;
use app\models\StatisticRankingType;
use app\models\StatisticRecordType;
use yii\helpers\Html;
use yii\helpers\Url;

// Generate month names using ICU (automatically translated based on app language)
$monthNames = [];
for ($m = 1; $m <= 12; $m++) {
    $date = new DateTimeImmutable("2024-{$m}-01");
    $monthNames[$m] = ucfirst(Yii::$app->formatter->asDate($date, 'LLLL')); // Standalone month name
}

$this->title = Yii::t('app', 'Monthly Statistics') . ' - ' . $monthNames[$month] . ' ' . $year;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Statistics'), 'url' => ['month']];
$this->params['breadcrumbs'][] = $monthNames[$month] . ' ' . $year;
?>
<div class="statistics-month">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mb-4">
        <div class="col-md-6">
            <?= $this->render('_period_selector', [
                'availablePeriods' => $availableMonths,
                'currentYear' => $year,
                'currentMonth' => $month,
                'action' => 'month',
                'monthNames' => $monthNames,
            ]) ?>
        </div>
        <div class="col-md-6 text-end">
            <?= Html::a(Yii::t('app', 'View Yearly Statistics'), ['year', 'year' => $year], ['class' => 'btn btn-outline-primary me-2']) ?>
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
