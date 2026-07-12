<?php

namespace app\helpers;

use app\models\StatisticAggregateType;
use app\models\StatisticRankingType;
use app\models\StatisticRecordType;
use Yii;

class DiscordStatisticsFormatter
{
    private const RANKING_EMOJI = [
        StatisticRankingType::CODE_TOP_PILOTS_BY_HOURS => "🧑‍✈️",
        StatisticRankingType::CODE_TOP_PILOTS_BY_FLIGHTS => '🏅',
        StatisticRankingType::CODE_TOP_AIRCRAFT_TYPES_BY_FLIGHTS => '🛩️',
        StatisticRankingType::CODE_SMOOTHEST_LANDINGS => '🛬',
    ];

    private const RECORD_EMOJI = [
        StatisticRecordType::CODE_LONGEST_FLIGHT_TIME => '⏳',
        StatisticRecordType::CODE_LONGEST_FLIGHT_DISTANCE => '🌍',
    ];

    /**
     * Build a Discord webhook payload for a statistics period, mirroring the content of
     * basic/mail/monthlyStatistics.php (and yearlyStatistics.php).
     *
     * Sent as plain message content (Discord markdown headers/bold) rather than an embed:
     * text inside Discord embeds always renders at a smaller font size than normal message
     * content, regardless of styling options.
     *
     * @param string $airlineName
     * @param string $periodTitle
     * @param array $aggregates Indexed by StatisticAggregateType code, as returned by
     *                          StatisticsController::getAggregatesForPeriod()
     * @param array $rankings Indexed by StatisticRankingType code, as returned by
     *                        StatisticsController::getRankingsForPeriod()
     * @param array $records Indexed by StatisticRecordType code, as returned by
     *                       StatisticsController::getRecordsForPeriod()
     * @return array Discord webhook payload (['content' => string])
     */
    public static function format(
        string $airlineName,
        string $periodTitle,
        array $aggregates,
        array $rankings,
        array $records
    ): array {
        $sections = [];

        $sections[] = '# 📊 ' . $airlineName . ' — ' . $periodTitle;
        $sections[] = self::formatAggregatesSummary($aggregates);

        foreach ($rankings as $code => $data) {
            $emoji = self::RANKING_EMOJI[$code] ?? '📌';
            $label = $data['type']->lang->name ?? $data['type']->code;
            $sections[] = "### {$emoji} {$label}\n" . self::formatRankingEntries($code, $data);
        }

        foreach ($records as $code => $data) {
            $emoji = self::RECORD_EMOJI[$code] ?? '🏆';
            $label = $data['record']->recordType->lang->name ?? $data['record']->recordType->code;
            $sections[] = "### {$emoji} {$label}\n" . self::formatRecordValue($code, $data);
        }

        return [
            'content' => implode("\n\n", $sections),
        ];
    }

    private static function formatAggregatesSummary(array $aggregates): string
    {
        $parts = [];
        foreach ($aggregates as $code => $aggregate) {
            $emoji = $code === StatisticAggregateType::CODE_TOTAL_FLIGHT_HOURS ? '⏱️' : '✈️';
            $label = $aggregate->aggregateType->lang->name ?? $aggregate->aggregateType->code;
            $parts[] = sprintf('%s **%s** %s', $emoji, self::formatAggregateValue($code, $aggregate), $label);
        }

        return implode('   ', $parts);
    }

    private static function formatAggregateValue(string $code, $aggregate): string
    {
        $value = $code === StatisticAggregateType::CODE_TOTAL_FLIGHT_HOURS
            ? TimeHelper::formatHoursMinutes($aggregate->value)
            : number_format($aggregate->value);

        if ($aggregate->variation_percent !== null) {
            $sign = $aggregate->variation_percent >= 0 ? '+' : '';
            $value .= sprintf(' (%s%s%%)', $sign, number_format($aggregate->variation_percent, 1));
        }

        return $value;
    }

    private static function formatRankingEntries(string $code, array $data): string
    {
        $lines = [];
        foreach ($data['entries'] as $ranking) {
            $entity = $data['entities'][$ranking->entity_id] ?? null;
            $entityName = self::resolveRankingEntityName($data['type']->entity_type, $entity);
            $entityValue = self::formatRankingValue($code, $ranking->value);

            $lines[] = sprintf('%d. %s — **%s**', $ranking->position, $entityName ?: Yii::t('app', 'Unknown'), $entityValue);
        }

        return implode("\n", $lines);
    }

    private static function resolveRankingEntityName(string $entityType, $entity): string
    {
        if (!$entity) {
            return '';
        }

        if ($entityType === StatisticRankingType::ENTITY_PILOT) {
            return $entity->fullname;
        }

        if ($entityType === StatisticRankingType::ENTITY_AIRCRAFT_TYPE) {
            return $entity->icao_type_code . ' - ' . $entity->name;
        }

        if ($entityType === StatisticRankingType::ENTITY_FLIGHT) {
            return $entity->pilot->fullname . ' (' . $entity->departure . '-' . $entity->arrival . ')';
        }

        return '';
    }

    private static function formatRankingValue(string $code, $value): string
    {
        if ($code === StatisticRankingType::CODE_TOP_PILOTS_BY_HOURS) {
            return TimeHelper::formatHoursMinutes($value);
        }

        if ($code === StatisticRankingType::CODE_SMOOTHEST_LANDINGS) {
            return number_format($value, 0) . ' fpm';
        }

        return number_format($value);
    }

    private static function formatRecordValue(string $code, array $data): string
    {
        $record = $data['record'];
        $flight = $data['flight'];

        if ($code === StatisticRecordType::CODE_LONGEST_FLIGHT_TIME) {
            $formattedValue = TimeHelper::formatHoursMinutes($record->value / 60);
        } elseif ($code === StatisticRecordType::CODE_LONGEST_FLIGHT_DISTANCE) {
            $formattedValue = number_format($record->value) . ' Nm';
        } else {
            $formattedValue = number_format($record->value);
        }

        $line = "**{$formattedValue}**";

        if ($flight) {
            $line .= sprintf(' — %s (%s-%s)', $flight->pilot->fullname, $flight->departure, $flight->arrival);
        }

        return $line;
    }
}
