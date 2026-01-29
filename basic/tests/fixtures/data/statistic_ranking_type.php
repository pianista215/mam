<?php

/**
 * Statistic ranking type fixtures.
 */

return [
    [
        'id' => 1,
        'code' => 'top_pilots_by_hours',
        'entity_type' => 'pilot',
        'max_positions' => 5,
        'sort_order' => 'DESC',
    ],
    [
        'id' => 2,
        'code' => 'top_pilots_by_flights',
        'entity_type' => 'pilot',
        'max_positions' => 5,
        'sort_order' => 'DESC',
    ],
    [
        'id' => 3,
        'code' => 'top_aircraft_types_by_flights',
        'entity_type' => 'aircraft_type',
        'max_positions' => 3,
        'sort_order' => 'DESC',
    ],
    [
        'id' => 4,
        'code' => 'smoothest_landings',
        'entity_type' => 'flight',
        'max_positions' => 3,
        'sort_order' => 'ASC',
    ],
];
