<?php

/** @var yii\web\View $this */
/** @var app\models\StatisticPeriod[] $availablePeriods */
/** @var int $currentYear */
/** @var int|null $currentMonth */
/** @var string $action */
/** @var array $monthNames */

use yii\helpers\Html;
use yii\helpers\Url;

?>
<div class="period-selector">
    <label for="period-select" class="form-label"><?= Yii::t('app', 'Select period') ?>:</label>
    <select id="period-select" class="form-select" onchange="window.location.href=this.value;">
        <?php foreach ($availablePeriods as $period): ?>
            <?php
            if ($action === 'month') {
                $url = Url::to(['statistics/month', 'year' => $period->year, 'month' => $period->month]);
                $label = $monthNames[$period->month] . ' ' . $period->year;
                $selected = ($period->year == $currentYear && $period->month == $currentMonth);
            } else {
                $url = Url::to(['statistics/year', 'year' => $period->year]);
                $label = $period->year;
                $selected = ($period->year == $currentYear);
            }
            ?>
            <option value="<?= Html::encode($url) ?>" <?= $selected ? 'selected' : '' ?>>
                <?= Html::encode($label) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
