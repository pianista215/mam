<?php

/**
 * Statistic ranking fixtures.
 *
 * Rankings based on flight fixtures:
 *
 * January 2025:
 * - top_pilots_by_hours: pilot 5 (3.5h), pilot 7 (0.917h)
 * - top_pilots_by_flights: pilot 5 (2), pilot 7 (1)
 * - top_aircraft_types_by_flights: B738 (2), C172 (1)
 * - smoothest_landings: flight 103 (120 fpm), 101 (150), 102 (280)
 *
 * December 2024:
 * - top_pilots_by_hours: pilot 5 (1.583h)
 * - top_pilots_by_flights: pilot 5 (1)
 * - top_aircraft_types_by_flights: B738 (1)
 * - smoothest_landings: flight 108 (200 fpm)
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

    // top_aircraft_types_by_flights (ranking_type_id=3): B738 (2), C172 (1)
    [
        'id' => 5,
        'period_id' => 2,
        'ranking_type_id' => 3,
        'position' => 1,
        'entity_id' => 2, // aircraft_type B738
        'value' => 2,
        'previous_position' => 1,
    ],
    [
        'id' => 6,
        'period_id' => 2,
        'ranking_type_id' => 3,
        'position' => 2,
        'entity_id' => 4, // aircraft_type C172
        'value' => 1,
        'previous_position' => null, // new entry
    ],

    // smoothest_landings (ranking_type_id=4): 103 (120), 101 (150), 102 (280)
    [
        'id' => 25,
        'period_id' => 2,
        'ranking_type_id' => 4,
        'position' => 1,
        'entity_id' => 103, // flight 103 - 120 fpm
        'value' => 120,
        'previous_position' => null,
    ],
    [
        'id' => 26,
        'period_id' => 2,
        'ranking_type_id' => 4,
        'position' => 2,
        'entity_id' => 101, // flight 101 - 150 fpm
        'value' => 150,
        'previous_position' => null,
    ],
    [
        'id' => 27,
        'period_id' => 2,
        'ranking_type_id' => 4,
        'position' => 3,
        'entity_id' => 102, // flight 102 - 280 fpm
        'value' => 280,
        'previous_position' => null,
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

    // top_aircraft_types_by_flights: B738 (1)
    [
        'id' => 9,
        'period_id' => 1,
        'ranking_type_id' => 3,
        'position' => 1,
        'entity_id' => 2, // aircraft_type B738
        'value' => 1,
        'previous_position' => null,
    ],

    // smoothest_landings: 108 (200 fpm)
    [
        'id' => 28,
        'period_id' => 1,
        'ranking_type_id' => 4,
        'position' => 1,
        'entity_id' => 108,
        'value' => 200,
        'previous_position' => null,
    ],

    // === Year 2024 (period_id=3) ===

    // top_pilots_by_hours
    [
        'id' => 16,
        'period_id' => 3,
        'ranking_type_id' => 1,
        'position' => 1,
        'entity_id' => 5,
        'value' => 1.5833,
        'previous_position' => null,
    ],

    // top_pilots_by_flights
    [
        'id' => 17,
        'period_id' => 3,
        'ranking_type_id' => 2,
        'position' => 1,
        'entity_id' => 5,
        'value' => 1,
        'previous_position' => null,
    ],

    // top_aircraft_types_by_flights: B738 (1)
    [
        'id' => 18,
        'period_id' => 3,
        'ranking_type_id' => 3,
        'position' => 1,
        'entity_id' => 2, // aircraft_type B738
        'value' => 1,
        'previous_position' => null,
    ],

    // smoothest_landings: 108 (200 fpm)
    [
        'id' => 29,
        'period_id' => 3,
        'ranking_type_id' => 4,
        'position' => 1,
        'entity_id' => 108,
        'value' => 200,
        'previous_position' => null,
    ],

    // === Year 2025 (period_id=4) ===

    // top_pilots_by_hours: pilot 5 (3.5h), pilot 7 (0.917h)
    [
        'id' => 19,
        'period_id' => 4,
        'ranking_type_id' => 1,
        'position' => 1,
        'entity_id' => 5,
        'value' => 3.5000,
        'previous_position' => 1,
    ],
    [
        'id' => 20,
        'period_id' => 4,
        'ranking_type_id' => 1,
        'position' => 2,
        'entity_id' => 7,
        'value' => 0.9167,
        'previous_position' => null,
    ],

    // top_pilots_by_flights: pilot 5 (2), pilot 7 (1)
    [
        'id' => 21,
        'period_id' => 4,
        'ranking_type_id' => 2,
        'position' => 1,
        'entity_id' => 5,
        'value' => 2,
        'previous_position' => 1,
    ],
    [
        'id' => 22,
        'period_id' => 4,
        'ranking_type_id' => 2,
        'position' => 2,
        'entity_id' => 7,
        'value' => 1,
        'previous_position' => null,
    ],

    // top_aircraft_types_by_flights: B738 (2), C172 (1)
    [
        'id' => 23,
        'period_id' => 4,
        'ranking_type_id' => 3,
        'position' => 1,
        'entity_id' => 2, // aircraft_type B738
        'value' => 2,
        'previous_position' => 1,
    ],
    [
        'id' => 24,
        'period_id' => 4,
        'ranking_type_id' => 3,
        'position' => 2,
        'entity_id' => 4, // aircraft_type C172
        'value' => 1,
        'previous_position' => null,
    ],

    // smoothest_landings: 103 (120), 101 (150), 102 (280)
    [
        'id' => 30,
        'period_id' => 4,
        'ranking_type_id' => 4,
        'position' => 1,
        'entity_id' => 103,
        'value' => 120,
        'previous_position' => null,
    ],
    [
        'id' => 31,
        'period_id' => 4,
        'ranking_type_id' => 4,
        'position' => 2,
        'entity_id' => 101,
        'value' => 150,
        'previous_position' => null,
    ],
    [
        'id' => 32,
        'period_id' => 4,
        'ranking_type_id' => 4,
        'position' => 3,
        'entity_id' => 102,
        'value' => 280,
        'previous_position' => null,
    ],

    // === All-Time (period_id=5) ===

    // top_pilots_by_hours: pilot 5 (5.0833h), pilot 7 (0.917h)
    [
        'id' => 10,
        'period_id' => 5,
        'ranking_type_id' => 1,
        'position' => 1,
        'entity_id' => 5,
        'value' => 5.0833,
        'previous_position' => null, // no previous for all-time
    ],
    [
        'id' => 11,
        'period_id' => 5,
        'ranking_type_id' => 1,
        'position' => 2,
        'entity_id' => 7,
        'value' => 0.9167,
        'previous_position' => null,
    ],

    // top_pilots_by_flights: pilot 5 (3), pilot 7 (1)
    [
        'id' => 12,
        'period_id' => 5,
        'ranking_type_id' => 2,
        'position' => 1,
        'entity_id' => 5,
        'value' => 3,
        'previous_position' => null,
    ],
    [
        'id' => 13,
        'period_id' => 5,
        'ranking_type_id' => 2,
        'position' => 2,
        'entity_id' => 7,
        'value' => 1,
        'previous_position' => null,
    ],

    // top_aircraft_types_by_flights: B738 (3), C172 (1)
    [
        'id' => 14,
        'period_id' => 5,
        'ranking_type_id' => 3,
        'position' => 1,
        'entity_id' => 2, // aircraft_type B738
        'value' => 3,
        'previous_position' => null,
    ],
    [
        'id' => 15,
        'period_id' => 5,
        'ranking_type_id' => 3,
        'position' => 2,
        'entity_id' => 4, // aircraft_type C172
        'value' => 1,
        'previous_position' => null,
    ],

    // smoothest_landings: 103 (120), 101 (150), 108 (200)
    [
        'id' => 33,
        'period_id' => 5,
        'ranking_type_id' => 4,
        'position' => 1,
        'entity_id' => 103,
        'value' => 120,
        'previous_position' => null,
    ],
    [
        'id' => 34,
        'period_id' => 5,
        'ranking_type_id' => 4,
        'position' => 2,
        'entity_id' => 101,
        'value' => 150,
        'previous_position' => null,
    ],
    [
        'id' => 35,
        'period_id' => 5,
        'ranking_type_id' => 4,
        'position' => 3,
        'entity_id' => 108,
        'value' => 200,
        'previous_position' => null,
    ],
];
