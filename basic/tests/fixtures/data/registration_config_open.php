<?php

return [
    [
        'key' => 'registration_start',
        'value' => date('Y-m-d', strtotime('-1 day')),
    ],
    [
        'key' => 'registration_end',
        'value' => date('Y-m-d', strtotime('+1 day')),
    ],
    [
        'key' => 'registration_start_location',
        'value' => 'LEMD',
    ],
];