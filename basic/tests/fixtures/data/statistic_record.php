<?php

/**
 * Statistic record fixtures.
 *
 * Records based on flight fixtures:
 *
 * January 2025:
 * - longest_flight_time: flight 102 (120 min) - ALL-TIME RECORD
 * - longest_flight_distance: flight 102 (350 nm) - ALL-TIME RECORD
 *
 * December 2024:
 * - longest_flight_time: flight 108 (95 min)
 * - longest_flight_distance: flight 108 (280 nm)
 */

return [
    // === December 2024 (period_id=1) ===

    // longest_flight_time (record_type_id=1)
    [
        'id' => 1,
        'period_id' => 1,
        'record_type_id' => 1,
        'entity_id' => 108, // flight 108
        'value' => 95,
        'is_all_time_record' => 0, // Will be surpassed in January
    ],
    // longest_flight_distance (record_type_id=2)
    [
        'id' => 2,
        'period_id' => 1,
        'record_type_id' => 2,
        'entity_id' => 108,
        'value' => 280,
        'is_all_time_record' => 0,
    ],

    // === January 2025 (period_id=2) ===

    // longest_flight_time
    [
        'id' => 3,
        'period_id' => 2,
        'record_type_id' => 1,
        'entity_id' => 102, // flight 102
        'value' => 120,
        'is_all_time_record' => 1, // New all-time record!
    ],
    // longest_flight_distance
    [
        'id' => 4,
        'period_id' => 2,
        'record_type_id' => 2,
        'entity_id' => 102,
        'value' => 350,
        'is_all_time_record' => 1,
    ],

    // === Year 2024 (period_id=3) ===

    // longest_flight_time
    [
        'id' => 7,
        'period_id' => 3,
        'record_type_id' => 1,
        'entity_id' => 108,
        'value' => 95,
        'is_all_time_record' => 0,
    ],
    // longest_flight_distance
    [
        'id' => 8,
        'period_id' => 3,
        'record_type_id' => 2,
        'entity_id' => 108,
        'value' => 280,
        'is_all_time_record' => 0,
    ],

    // === Year 2025 (period_id=4) ===

    // longest_flight_time
    [
        'id' => 9,
        'period_id' => 4,
        'record_type_id' => 1,
        'entity_id' => 102,
        'value' => 120,
        'is_all_time_record' => 1,
    ],
    // longest_flight_distance
    [
        'id' => 10,
        'period_id' => 4,
        'record_type_id' => 2,
        'entity_id' => 102,
        'value' => 350,
        'is_all_time_record' => 1,
    ],

    // === All-Time (period_id=5) ===

    // longest_flight_time (same as January 2025 record)
    [
        'id' => 5,
        'period_id' => 5,
        'record_type_id' => 1,
        'entity_id' => 102,
        'value' => 120,
        'is_all_time_record' => 1,
    ],
    // longest_flight_distance (same as January 2025 record)
    [
        'id' => 6,
        'period_id' => 5,
        'record_type_id' => 2,
        'entity_id' => 102,
        'value' => 350,
        'is_all_time_record' => 1,
    ],
];
