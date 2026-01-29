<?php

/** @var yii\web\View $this */
/** @var app\models\StatisticPeriod $period */
/** @var array $aggregates */
/** @var array $rankings */
/** @var array $records */

use app\helpers\TimeHelper;
use app\models\StatisticAggregateType;
use app\models\StatisticRankingType;
use app\models\StatisticRecordType;
use yii\helpers\Html;

?>
<div class="statistics-content">
    <!-- Aggregates -->
    <div class="row mb-4">
        <div class="col-12">
            <h3><?= Yii::t('app', 'Summary') ?></h3>
        </div>
        <?php foreach ($aggregates as $code => $aggregate): ?>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted">
                            <?= Html::encode($aggregate->aggregateType->lang->name ?? $aggregate->aggregateType->code) ?>
                        </h5>
                        <p class="display-6 mb-1">
                            <?php
                            if ($code === StatisticAggregateType::CODE_TOTAL_FLIGHT_HOURS) {
                                echo TimeHelper::formatHoursMinutes($aggregate->value);
                            } else {
                                echo number_format($aggregate->value);
                            }
                            ?>
                        </p>
                        <?php if ($aggregate->variation_percent !== null): ?>
                            <?php
                            $variationClass = $aggregate->variation_percent >= 0 ? 'text-success' : 'text-danger';
                            $variationIcon = $aggregate->variation_percent >= 0 ? '+' : '';
                            ?>
                            <small class="<?= $variationClass ?>">
                                <?= $variationIcon . number_format($aggregate->variation_percent, 1) ?>%
                                <?= Yii::t('app', 'vs previous period') ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Rankings -->
    <?php if (!empty($rankings)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h3><?= Yii::t('app', 'Rankings') ?></h3>
            </div>
            <?php foreach ($rankings as $code => $data): ?>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><?= Html::encode($data['type']->lang->name ?? $data['type']->code) ?></strong>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th><?= Yii::t('app', 'Name') ?></th>
                                        <th class="text-end"><?= Yii::t('app', 'Value') ?></th>
                                        <th style="width: 60px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['entries'] as $ranking): ?>
                                        <?php
                                        $entity = $data['entities'][$ranking->entity_id] ?? null;
                                        $entityName = '';
                                        $entityValue = '';

                                        if ($data['type']->entity_type === StatisticRankingType::ENTITY_PILOT && $entity) {
                                            $entityName = $entity->fullname;
                                        } elseif ($data['type']->entity_type === StatisticRankingType::ENTITY_AIRCRAFT_TYPE && $entity) {
                                            $entityName = $entity->icao_type_code . ' - ' . $entity->name;
                                        } elseif ($data['type']->entity_type === StatisticRankingType::ENTITY_FLIGHT && $entity) {
                                            $entityName = $entity->pilot->fullname . ' (' . $entity->departure . '-' . $entity->arrival . ')';
                                        }

                                        // Format value based on ranking type
                                        if ($code === StatisticRankingType::CODE_TOP_PILOTS_BY_HOURS) {
                                            $entityValue = TimeHelper::formatHoursMinutes($ranking->value);
                                        } elseif ($code === StatisticRankingType::CODE_SMOOTHEST_LANDINGS) {
                                            $entityValue = number_format($ranking->value, 0) . ' fpm';
                                        } else {
                                            $entityValue = number_format($ranking->value);
                                        }

                                        // Position change indicator
                                        $positionChange = '';
                                        if ($ranking->previous_position !== null) {
                                            $diff = $ranking->previous_position - $ranking->position;
                                            if ($diff > 0) {
                                                $positionChange = '<span class="text-success">&#9650;' . $diff . '</span>';
                                            } elseif ($diff < 0) {
                                                $positionChange = '<span class="text-danger">&#9660;' . abs($diff) . '</span>';
                                            } else {
                                                $positionChange = '<span class="text-muted">-</span>';
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if ($ranking->position === 1): ?>
                                                    <span class="badge bg-warning text-dark">1</span>
                                                <?php elseif ($ranking->position === 2): ?>
                                                    <span class="badge bg-secondary">2</span>
                                                <?php elseif ($ranking->position === 3): ?>
                                                    <span class="badge bg-dark">3</span>
                                                <?php else: ?>
                                                    <?= $ranking->position ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= Html::encode($entityName ?: Yii::t('app', 'Unknown')) ?></td>
                                            <td class="text-end"><?= $entityValue ?></td>
                                            <td class="text-center"><?= $positionChange ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Records -->
    <?php if (!empty($records)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h3><?= Yii::t('app', 'Records') ?></h3>
            </div>
            <?php foreach ($records as $code => $data): ?>
                <?php
                $record = $data['record'];
                $flight = $data['flight'];

                // Format value based on record type
                if ($code === StatisticRecordType::CODE_LONGEST_FLIGHT_TIME) {
                    // value is in minutes, convert to hours for TimeHelper
                    $formattedValue = TimeHelper::formatHoursMinutes($record->value / 60);
                } elseif ($code === StatisticRecordType::CODE_LONGEST_FLIGHT_DISTANCE) {
                    $formattedValue = number_format($record->value) . ' Nm';
                } else {
                    $formattedValue = number_format($record->value);
                }
                ?>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><?= Html::encode($record->recordType->lang->name ?? $record->recordType->code) ?></strong>
                        </div>
                        <div class="card-body">
                            <p class="display-6 mb-2"><?= $formattedValue ?></p>
                            <?php if ($flight): ?>
                                <p class="mb-0 text-muted">
                                    <?= Html::encode($flight->pilot->fullname) ?><br>
                                    <?= Html::encode($flight->departure) ?> - <?= Html::encode($flight->arrival) ?><br>
                                    <?= Yii::$app->formatter->asDate($flight->creation_date, 'medium') ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Period info -->
    <div class="row">
        <div class="col-12">
            <small class="text-muted">
                <?= Yii::t('app', 'Last updated') ?>: <?= Yii::$app->formatter->asDatetime($period->calculated_at, 'medium') ?>
            </small>
        </div>
    </div>
</div>
