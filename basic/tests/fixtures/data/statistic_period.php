<?php

/**
 * Statistic period fixtures.
 *
 * Pre-computed periods for testing web views:
 * - December 2024 monthly (closed)
 * - January 2025 monthly (open)
 * - 2024 yearly (closed)
 * - 2025 yearly (open)
 * - All-time (always open)
 */

return [
    // December 2024 - monthly, closed
    [
        'id' => 1,
        'period_type_id' => 1, // monthly
        'year' => 2024,
        'month' => 12,
        'status' => 'C',
        'calculated_at' => '2025-01-01 00:30:00',
    ],
    // January 2025 - monthly, open
    [
        'id' => 2,
        'period_type_id' => 1, // monthly
        'year' => 2025,
        'month' => 1,
        'status' => 'O',
        'calculated_at' => '2025-01-28 00:30:00',
    ],
    // 2024 - yearly, closed
    [
        'id' => 3,
        'period_type_id' => 2, // yearly
        'year' => 2024,
        'month' => null,
        'status' => 'C',
        'calculated_at' => '2025-01-01 00:30:00',
    ],
    // 2025 - yearly, open
    [
        'id' => 4,
        'period_type_id' => 2, // yearly
        'year' => 2025,
        'month' => null,
        'status' => 'O',
        'calculated_at' => '2025-01-28 00:30:00',
    ],
    // All-time - always open
    [
        'id' => 5,
        'period_type_id' => 3, // all_time
        'year' => null,
        'month' => null,
        'status' => 'O',
        'calculated_at' => '2025-01-28 00:30:00',
    ],
];
