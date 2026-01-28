<?php

/**
 * Statistic ranking fixtures.
 *
 * Rankings based on flight fixtures:
 *
 * January 2025:
 * - top_pilots_by_hours: pilot 5 (3.5h), pilot 7 (0.917h)
 * - top_pilots_by_flights: pilot 5 (2), pilot 7 (1)
 * - top_aircraft_by_flights: aircraft 4 (2), aircraft 6 (1)
 *
 * December 2024:
 * - top_pilots_by_hours: pilot 5 (1.583h)
 * - top_pilots_by_flights: pilot 5 (1)
 * - top_aircraft_by_flights: aircraft 6 (1)
 */

return [
    // === January 2025 (period_id=2) ===

    // top_pilots_by_hours (ranking_type_id=1)
    [
        'id' => 1,
        'period_id' => 2,
        'ranking_type_id' => 1,
        'position' => 1,
        'entity_id' => 5, // pilot 5
        'value' => 3.5000,
        'previous_position' => 1, // was also #1 in December
    ],
    [
        'id' => 2,
        'period_id' => 2,
        'ranking_type_id' => 1,
        'position' => 2,
        'entity_id' => 7, // pilot 7
        'value' => 0.9167,
        'previous_position' => null, // new entry
    ],

    // top_pilots_by_flights (ranking_type_id=2)
    [
        'id' => 3,
        'period_id' => 2,
        'ranking_type_id' => 2,
        'position' => 1,
        'entity_id' => 5,
        'value' => 2,
        'previous_position' => 1,
    ],
    [
        'id' => 4,
        'period_id' => 2,
        'ranking_type_id' => 2,
        'position' => 2,
        'entity_id' => 7,
        'value' => 1,
        'previous_position' => null,
    ],

    // top_aircraft_by_flights (ranking_type_id=3)
    [
        'id' => 5,
        'period_id' => 2,
        'ranking_type_id' => 3,
        'position' => 1,
        'entity_id' => 4, // aircraft 4
        'value' => 2,
        'previous_position' => null, // wasn't in December ranking
    ],
    [
        'id' => 6,
        'period_id' => 2,
        'ranking_type_id' => 3,
        'position' => 2,
        'entity_id' => 6, // aircraft 6
        'value' => 1,
        'previous_position' => 1, // was #1 in December
    ],

    // === December 2024 (period_id=1) ===

    // top_pilots_by_hours
    [
        'id' => 7,
        'period_id' => 1,
        'ranking_type_id' => 1,
        'position' => 1,
        'entity_id' => 5,
        'value' => 1.5833,
        'previous_position' => null,
    ],

    // top_pilots_by_flights
    [
        'id' => 8,
        'period_id' => 1,
        'ranking_type_id' => 2,
        'position' => 1,
        'entity_id' => 5,
        'value' => 1,
        'previous_position' => null,
    ],

    // top_aircraft_by_flights
    [
        'id' => 9,
        'period_id' => 1,
        'ranking_type_id' => 3,
        'position' => 1,
        'entity_id' => 6,
        'value' => 1,
        'previous_position' => null,
    ],
];
