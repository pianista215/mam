<?php

// Pilot 1 is at LEBL and can fly B738 (aircraft 2, EC-BBB) and C172 (aircraft 3, EC-UUU), with MNPS for GCLP.
// Pilot 10 is at LEAL with aircraft 10 and a high charter ratio.
return [
    // Bookable by pilot 1
    ['id' => 1, 'pilot_id' => 1, 'aircraft_id' => 2, 'client_id' => 1, 'departure' => 'LEBL', 'arrival' => 'LEMD', 'distance_nm' => 261],
    ['id' => 2, 'pilot_id' => 1, 'aircraft_id' => 2, 'client_id' => 2, 'departure' => 'LEBL', 'arrival' => 'GCLP', 'distance_nm' => 1173],
    // Pilot 1, aircraft parked at GCLP
    ['id' => 3, 'pilot_id' => 1, 'aircraft_id' => 9, 'client_id' => 1, 'departure' => 'LEBL', 'arrival' => 'LEVC', 'distance_nm' => 165],
    // Pilot 1, aircraft reserved by FPL 1
    ['id' => 4, 'pilot_id' => 1, 'aircraft_id' => 4, 'client_id' => 2, 'departure' => 'LEBL', 'arrival' => 'LEVC', 'distance_nm' => 165],
    // Another pilot's assignment
    ['id' => 5, 'pilot_id' => 9, 'aircraft_id' => 2, 'client_id' => 1, 'departure' => 'LEBL', 'arrival' => 'LEZG', 'distance_nm' => 140],
    // Pilot 1, out of the C172 range (listed, but can't be booked)
    ['id' => 6, 'pilot_id' => 1, 'aircraft_id' => 3, 'client_id' => 1, 'departure' => 'LEBL', 'arrival' => 'GCLP', 'distance_nm' => 1173],
    // Pilot 1, departing from another airport than the pilot's
    ['id' => 7, 'pilot_id' => 1, 'aircraft_id' => 1, 'client_id' => 1, 'departure' => 'LEMD', 'arrival' => 'LEVC', 'distance_nm' => 160],
    // Pilot 10 (high charter ratio)
    ['id' => 8, 'pilot_id' => 10, 'aircraft_id' => 10, 'client_id' => 1, 'departure' => 'LEAL', 'arrival' => 'LEMD', 'distance_nm' => 197],
];
