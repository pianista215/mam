<?php

return [
    // Tour already ended
    [
        'id' => 1,
        'tour_id' => 1,
        'departure' => 'LEBL',
        'arrival' => 'LEMD',
        'distance_nm' => '260',
        'description' => 'Desc',
        'sequence' => 1,
    ],
    // Tour actual reported
    [
        'id' => 2,
        'tour_id' => 3,
        'departure' => 'LEBL',
        'arrival' => 'LEMD',
        'distance_nm' => '260',
        'description' => '',
        'sequence' => 1,
    ],
    [
        'id' => 3,
        'tour_id' => 3,
        'departure' => 'LEMD',
        'arrival' => 'LEVC',
        'distance_nm' => '400',
        'description' => '',
        'sequence' => 2,
    ],
    // Tour not started
    [
        'id' => 4,
        'tour_id' => 4,
        'departure' => 'LEMD',
        'arrival' => 'LEVC',
        'distance_nm' => '400',
        'description' => '',
        'sequence' => 1,
    ],

];