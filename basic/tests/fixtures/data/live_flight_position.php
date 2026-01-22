<?php

// Active flights (updated_at within last 2 minutes)
// Stale flights (updated_at more than 2 minutes ago)

return [
    // Active flight - pilot 5 with FPL 1 (Route: LEBL -> GCLP)
    [
        'submitted_flight_plan_id' => 1,
        'latitude' => 40.4168,
        'longitude' => -3.7038,
        'altitude' => 35000,
        'heading' => 270,
        'ground_speed' => 450,
        'updated_at' => date('Y-m-d H:i:s'), // Now - active
    ],
    // Active flight - pilot 6 with FPL 2 (Route: LEBL -> LEVC)
    [
        'submitted_flight_plan_id' => 2,
        'latitude' => 41.2971,
        'longitude' => 2.0785,
        'altitude' => 28000,
        'heading' => 180,
        'ground_speed' => 380,
        'updated_at' => date('Y-m-d H:i:s', strtotime('-1 minute')), // 1 minute ago - active
    ],
    // Stale flight - pilot 7 with FPL 3 (more than 2 minutes old)
    [
        'submitted_flight_plan_id' => 3,
        'latitude' => 39.4699,
        'longitude' => -0.3763,
        'altitude' => 32000,
        'heading' => 90,
        'ground_speed' => 420,
        'updated_at' => date('Y-m-d H:i:s', strtotime('-5 minutes')), // 5 minutes ago - stale
    ],
    // Active flight - pilot 8 with FPL 4 (Tour stage: LEBL -> LEMD)
    [
        'submitted_flight_plan_id' => 4,
        'latitude' => 40.4936,
        'longitude' => -3.5668,
        'altitude' => 38000,
        'heading' => 45,
        'ground_speed' => 480,
        'updated_at' => date('Y-m-d H:i:s', strtotime('-30 seconds')), // 30 seconds ago - active
    ],
    // Active flight - pilot 4 with FPL 5 (Charter: LEBL -> LEVC)
    [
        'submitted_flight_plan_id' => 5,
        'latitude' => 39.8894,
        'longitude' => -0.4816,
        'altitude' => 25000,
        'heading' => 200,
        'ground_speed' => 350,
        'updated_at' => date('Y-m-d H:i:s', strtotime('-90 seconds')), // 90 seconds ago - active
    ],
];
