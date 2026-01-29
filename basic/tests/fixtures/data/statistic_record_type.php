<?php

/**
 * Statistic record type fixtures.
 */

return [
    [
        'id' => 1,
        'code' => 'longest_flight_time',
        'entity_type' => 'flight',
        'comparison' => 'MAX',
        'unit' => 'minutes',
    ],
    [
        'id' => 2,
        'code' => 'longest_flight_distance',
        'entity_type' => 'flight',
        'comparison' => 'MAX',
        'unit' => 'nm',
    ],
];
