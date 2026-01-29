<?php

use app\helpers\TimeHelper;
use app\models\StatisticAggregateType;
use app\models\StatisticRankingType;
use app\models\StatisticRecordType;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $airlineName */
/** @var string $periodTitle */
/** @var app\models\StatisticPeriod $period */
/** @var array $aggregates */
/** @var array $rankings */
/** @var array $records */

$cardStyle = 'background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 15px; text-align: center;';
$headerStyle = 'color: #6c757d; font-size: 14px; margin-bottom: 10px;';
$valueStyle = 'font-size: 32px; font-weight: bold; color: #212529; margin-bottom: 5px;';
$variationPositiveStyle = 'color: #198754; font-size: 12px;';
$variationNegativeStyle = 'color: #dc3545; font-size: 12px;';
$sectionTitleStyle = 'color: #212529; font-size: 20px; margin: 30px 0 15px 0; border-bottom: 2px solid #dee2e6; padding-bottom: 10px;';
$tableStyle = 'width: 100%; border-collapse: collapse; margin-bottom: 20px;';
$thStyle = 'background-color: #f8f9fa; padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;';
$tdStyle = 'padding: 10px; border-bottom: 1px solid #dee2e6;';
$badgeGold = 'background-color: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
$badgeSilver = 'background-color: #6c757d; color: #fff; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
$badgeBronze = 'background-color: #343a40; color: #fff; padding: 4px 8px; border-radius: 4px; font-weight: bold;';
?>

<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #212529;">
    <div style="background-color: #0d6efd; color: #fff; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 24px;"><?= Html::encode($airlineName) ?></h1>
        <p style="margin: 10px 0 0 0; font-size: 16px;"><?= Yii::t('app', 'Monthly Statistics') ?></p>
        <p style="margin: 5px 0 0 0; font-size: 20px; font-weight: bold;"><?= Html::encode($periodTitle) ?></p>
    </div>

    <div style="padding: 30px; background-color: #ffffff;">
        <!-- Summary -->
        <h2 style="<?= $sectionTitleStyle ?>"><?= Yii::t('app', 'Summary') ?></h2>
        <table style="width: 100%;">
            <tr>
                <?php foreach ($aggregates as $code => $aggregate): ?>
                    <td style="width: 50%; vertical-align: top;">
                        <div style="<?= $cardStyle ?>">
                            <div style="<?= $headerStyle ?>">
                                <?= Html::encode($aggregate->aggregateType->lang->name ?? $aggregate->aggregateType->code) ?>
                            </div>
                            <div style="<?= $valueStyle ?>">
                                <?php
                                if ($code === StatisticAggregateType::CODE_TOTAL_FLIGHT_HOURS) {
                                    echo TimeHelper::formatHoursMinutes($aggregate->value);
                                } else {
                                    echo number_format($aggregate->value);
                                }
                                ?>
                            </div>
                            <?php if ($aggregate->variation_percent !== null): ?>
                                <?php
                                $varStyle = $aggregate->variation_percent >= 0 ? $variationPositiveStyle : $variationNegativeStyle;
                                $varIcon = $aggregate->variation_percent >= 0 ? '+' : '';
                                ?>
                                <div style="<?= $varStyle ?>">
                                    <?= $varIcon . number_format($aggregate->variation_percent, 1) ?>%
                                    <?= Yii::t('app', 'vs previous period') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                <?php endforeach; ?>
            </tr>
        </table>

        <!-- Rankings -->
        <?php if (!empty($rankings)): ?>
            <h2 style="<?= $sectionTitleStyle ?>"><?= Yii::t('app', 'Rankings') ?></h2>
            <?php foreach ($rankings as $code => $data): ?>
                <h3 style="color: #495057; font-size: 16px; margin: 20px 0 10px 0;">
                    <?= Html::encode($data['type']->lang->name ?? $data['type']->code) ?>
                </h3>
                <table style="<?= $tableStyle ?>">
                    <thead>
                        <tr>
                            <th style="<?= $thStyle ?> width: 50px;">#</th>
                            <th style="<?= $thStyle ?>"><?= Yii::t('app', 'Name') ?></th>
                            <th style="<?= $thStyle ?> text-align: right;">
                                <?php
                                if ($code === StatisticRankingType::CODE_TOP_PILOTS_BY_HOURS) {
                                    echo Yii::t('app', 'Hours');
                                } elseif ($code === StatisticRankingType::CODE_SMOOTHEST_LANDINGS) {
                                    echo Yii::t('app', 'Landing Rate');
                                } else {
                                    echo Yii::t('app', 'Flights');
                                }
                                ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['entries'] as $ranking): ?>
                            <?php
                            $entity = $data['entities'][$ranking->entity_id] ?? null;
                            $entityName = '';

                            if ($data['type']->entity_type === StatisticRankingType::ENTITY_PILOT && $entity) {
                                $entityName = $entity->fullname;
                            } elseif ($data['type']->entity_type === StatisticRankingType::ENTITY_AIRCRAFT_TYPE && $entity) {
                                $entityName = $entity->icao_type_code . ' - ' . $entity->name;
                            } elseif ($data['type']->entity_type === StatisticRankingType::ENTITY_FLIGHT && $entity) {
                                $entityName = $entity->pilot->fullname . ' (' . $entity->departure . '-' . $entity->arrival . ')';
                            }

                            if ($code === StatisticRankingType::CODE_TOP_PILOTS_BY_HOURS) {
                                $entityValue = TimeHelper::formatHoursMinutes($ranking->value);
                            } elseif ($code === StatisticRankingType::CODE_SMOOTHEST_LANDINGS) {
                                $entityValue = number_format($ranking->value, 0) . ' fpm';
                            } else {
                                $entityValue = number_format($ranking->value);
                            }

                            $positionBadge = '';
                            if ($ranking->position === 1) {
                                $positionBadge = '<span style="' . $badgeGold . '">1</span>';
                            } elseif ($ranking->position === 2) {
                                $positionBadge = '<span style="' . $badgeSilver . '">2</span>';
                            } elseif ($ranking->position === 3) {
                                $positionBadge = '<span style="' . $badgeBronze . '">3</span>';
                            } else {
                                $positionBadge = $ranking->position;
                            }
                            ?>
                            <tr>
                                <td style="<?= $tdStyle ?>"><?= $positionBadge ?></td>
                                <td style="<?= $tdStyle ?>"><?= Html::encode($entityName ?: Yii::t('app', 'Unknown')) ?></td>
                                <td style="<?= $tdStyle ?> text-align: right;"><?= $entityValue ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Records -->
        <?php if (!empty($records)): ?>
            <h2 style="<?= $sectionTitleStyle ?>"><?= Yii::t('app', 'Records') ?></h2>
            <table style="width: 100%;">
                <tr>
                    <?php foreach ($records as $code => $data): ?>
                        <?php
                        $record = $data['record'];
                        $flight = $data['flight'];

                        if ($code === StatisticRecordType::CODE_LONGEST_FLIGHT_TIME) {
                            $formattedValue = TimeHelper::formatHoursMinutes($record->value / 60);
                        } elseif ($code === StatisticRecordType::CODE_LONGEST_FLIGHT_DISTANCE) {
                            $formattedValue = number_format($record->value) . ' Nm';
                        } else {
                            $formattedValue = number_format($record->value);
                        }
                        ?>
                        <td style="width: 50%; vertical-align: top;">
                            <div style="<?= $cardStyle ?>">
                                <div style="<?= $headerStyle ?>">
                                    <?= Html::encode($record->recordType->lang->name ?? $record->recordType->code) ?>
                                </div>
                                <div style="<?= $valueStyle ?>"><?= $formattedValue ?></div>
                                <?php if ($flight): ?>
                                    <div style="color: #6c757d; font-size: 12px;">
                                        <?= Html::encode($flight->pilot->fullname) ?><br>
                                        <?= Html::encode($flight->departure) ?> - <?= Html::encode($flight->arrival) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </table>
        <?php endif; ?>
    </div>

    <div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; color: #6c757d; font-size: 12px;">
        <p style="margin: 0;"><?= Html::encode($airlineName) ?> - <?= Yii::t('app', 'Monthly Statistics') ?></p>
        <p style="margin: 5px 0 0 0;"><?= Yii::t('app', 'Last updated') ?>: <?= Yii::$app->formatter->asDatetime($period->calculated_at, 'medium') ?></p>
    </div>
</div>
