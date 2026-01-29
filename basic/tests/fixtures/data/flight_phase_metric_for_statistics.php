<?php

/**
 * Flight phase metric fixtures for statistics testing.
 *
 * Landing rates (LandingVSFpm) for smoothest landings ranking:
 * - Flight 101: -150 fpm (smooth)
 * - Flight 102: -280 fpm (harder)
 * - Flight 103: -120 fpm (very smooth - best)
 * - Flight 108: -200 fpm (December 2024)
 *
 * Rankings (by absolute value, ascending):
 * January 2025: 103 (120), 101 (150), 102 (280)
 * December 2024: 108 (200)
 * Year 2025: 103, 101, 102
 * Year 2024: 108
 * All-time: 103, 101, 108
 */

return [
    // Flight 101 - landing rate -150 fpm
    [
        'flight_phase_id' => 1,
        'metric_type_id' => 1, // LandingVSFpm
        'value' => '-150',
    ],
    // Flight 102 - landing rate -280 fpm
    [
        'flight_phase_id' => 2,
        'metric_type_id' => 1,
        'value' => '-280',
    ],
    // Flight 103 - landing rate -120 fpm (smoothest)
    [
        'flight_phase_id' => 3,
        'metric_type_id' => 1,
        'value' => '-120',
    ],
    // Flight 108 - landing rate -200 fpm
    [
        'flight_phase_id' => 4,
        'metric_type_id' => 1,
        'value' => '-200',
    ],
];
