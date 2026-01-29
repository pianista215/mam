<?php

/**
 * Flight phase fixtures for statistics testing.
 *
 * Each completed flight (status 'F' with flight_time_minutes) gets a final_landing phase.
 * - Flight 101: landing phase
 * - Flight 102: landing phase
 * - Flight 103: landing phase
 * - Flight 108: landing phase (December 2024)
 */

return [
    // Flight 101 - landing phase
    [
        'id' => 1,
        'flight_report_id' => 101,
        'flight_phase_type_id' => 1, // final_landing
        'start' => '2025-01-10 09:58:00',
        'end' => '2025-01-10 10:00:00',
    ],
    // Flight 102 - landing phase
    [
        'id' => 2,
        'flight_report_id' => 102,
        'flight_phase_type_id' => 1,
        'start' => '2025-01-15 11:28:00',
        'end' => '2025-01-15 11:30:00',
    ],
    // Flight 103 - landing phase
    [
        'id' => 3,
        'flight_report_id' => 103,
        'flight_phase_type_id' => 1,
        'start' => '2025-01-20 15:23:00',
        'end' => '2025-01-20 15:25:00',
    ],
    // Flight 108 - landing phase (December 2024)
    [
        'id' => 4,
        'flight_report_id' => 108,
        'flight_phase_type_id' => 1,
        'start' => '2024-12-15 10:03:00',
        'end' => '2024-12-15 10:05:00',
    ],
];
