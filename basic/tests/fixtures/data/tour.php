<?php

return [
    [
        'id' => 1,
        'name' => 'Tour previous',
        'description' => 'Tour already ended without flights associated',
        'start' => date('Y-m-d', strtotime('-100 day')),
        'end' => date('Y-m-d', strtotime('-5 day')),
    ],
    [
        'id' => 2,
        'name' => 'Tour actual empty',
        'description' => 'Tour actual without flights associated',
        'start' => date('Y-m-d', strtotime('-100 day')),
        'end' => date('Y-m-d', strtotime('+20 day')),
    ],
    [
        'id' => 3,
        'name' => 'Tour actual reported',
        'description' => 'Tour actual with flights associated',
        'start' => date('Y-m-d', strtotime('-100 day')),
        'end' => date('Y-m-d', strtotime('+100 day')),
    ],
    [
        'id' => 4,
        'name' => 'Tour not started',
        'description' => 'Tour that will start in the future',
        'start' => date('Y-m-d', strtotime('+100 day')),
        'end' => date('Y-m-d', strtotime('+300 day')),
    ],

];