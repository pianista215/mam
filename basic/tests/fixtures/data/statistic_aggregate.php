<?php

/**
 * Statistic aggregate fixtures.
 *
 * Pre-computed aggregates matching the flight fixtures:
 *
 * December 2024: 1 flight (108), 95 min = 1.5833 hours
 * January 2025: 3 flights (101,102,103), 265 min = 4.4167 hours
 * Year 2024: 1 flight, 1.5833 hours
 * Year 2025: 3 flights, 4.4167 hours
 */

return [
    // December 2024 - total_flights
    [
        'id' => 1,
        'period_id' => 1,
        'aggregate_type_id' => 1, // total_flights
        'value' => 1,
        'variation_percent' => null, // No previous period
    ],
    // December 2024 - total_flight_hours
    [
        'id' => 2,
        'period_id' => 1,
        'aggregate_type_id' => 2, // total_flight_hours
        'value' => 1.5833,
        'variation_percent' => null,
    ],
    // January 2025 - total_flights
    [
        'id' => 3,
        'period_id' => 2,
        'aggregate_type_id' => 1, // total_flights
        'value' => 3,
        'variation_percent' => 200.00, // (3-1)/1 * 100
    ],
    // January 2025 - total_flight_hours
    [
        'id' => 4,
        'period_id' => 2,
        'aggregate_type_id' => 2, // total_flight_hours
        'value' => 4.4167,
        'variation_percent' => 178.95, // (4.4167-1.5833)/1.5833 * 100
    ],
    // Year 2024 - total_flights
    [
        'id' => 5,
        'period_id' => 3,
        'aggregate_type_id' => 1, // total_flights
        'value' => 1,
        'variation_percent' => null,
    ],
    // Year 2024 - total_flight_hours
    [
        'id' => 6,
        'period_id' => 3,
        'aggregate_type_id' => 2, // total_flight_hours
        'value' => 1.5833,
        'variation_percent' => null,
    ],
    // Year 2025 - total_flights
    [
        'id' => 7,
        'period_id' => 4,
        'aggregate_type_id' => 1, // total_flights
        'value' => 3,
        'variation_percent' => 200.00,
    ],
    // Year 2025 - total_flight_hours
    [
        'id' => 8,
        'period_id' => 4,
        'aggregate_type_id' => 2, // total_flight_hours
        'value' => 4.4167,
        'variation_percent' => 178.95,
    ],
];
