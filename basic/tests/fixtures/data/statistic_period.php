<?php

/**
 * Statistic period fixtures with dynamic dates.
 *
 * Uses current month/year and previous month/year for realistic testing.
 */

$now = new DateTimeImmutable();
$currentYear = (int) $now->format('Y');
$currentMonth = (int) $now->format('n');

// Calculate previous month
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$calculatedAt = $now->format('Y-m-d H:i:s');
$prevCalculatedAt = $now->modify('-1 month')->format('Y-m-d H:i:s');

return [
    // Previous month - monthly, closed
    [
        'id' => 1,
        'period_type_id' => 1, // monthly
        'year' => $prevYear,
        'month' => $prevMonth,
        'status' => 'C',
        'calculated_at' => $prevCalculatedAt,
    ],
    // Current month - monthly, open
    [
        'id' => 2,
        'period_type_id' => 1, // monthly
        'year' => $currentYear,
        'month' => $currentMonth,
        'status' => 'O',
        'calculated_at' => $calculatedAt,
    ],
    // Previous year - yearly, closed (only if we're in a new year)
    [
        'id' => 3,
        'period_type_id' => 2, // yearly
        'year' => $prevYear,
        'month' => null,
        'status' => $prevYear < $currentYear ? 'C' : 'O',
        'calculated_at' => $prevCalculatedAt,
    ],
    // Current year - yearly, open
    [
        'id' => 4,
        'period_type_id' => 2, // yearly
        'year' => $currentYear,
        'month' => null,
        'status' => 'O',
        'calculated_at' => $calculatedAt,
    ],
    // All-time - always open
    [
        'id' => 5,
        'period_type_id' => 3, // all_time
        'year' => null,
        'month' => null,
        'status' => 'O',
        'calculated_at' => $calculatedAt,
    ],
];
