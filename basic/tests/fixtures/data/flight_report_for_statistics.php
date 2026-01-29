<?php

/**
 * Flight report fixtures for statistics testing.
 *
 * Expected totals for January 2025 (only status 'F' with flight_time_minutes):
 * - total_flights: 3 (flights 101, 102, 103)
 * - total_flight_hours: (90 + 120 + 55) / 60 = 4.4167 hours
 *
 * Rankings for January 2025:
 * - top_pilots_by_hours: pilot 5 (210 min = 3.5h), pilot 7 (55 min = 0.917h)
 * - top_pilots_by_flights: pilot 5 (2), pilot 7 (1)
 * - top_aircraft_by_flights: aircraft 4 (2), aircraft 6 (1)
 *
 * Records for January 2025:
 * - longest_flight_time: flight 102 (120 min)
 * - longest_flight_distance: flight 102 (350 nm)
 *
 * December 2024:
 * - 1 flight (108): 95 min, 280 nm
 */

return [
    // Flight 101 - 90 minutes, 250 nm
    [
        'id' => 101,
        'flight_id' => 101,
        'start_time' => '2025-01-10 08:30:00',
        'end_time' => '2025-01-10 10:00:00',
        'flight_time_minutes' => 90,
        'block_time_minutes' => 95,
        'total_fuel_burn_kg' => 2500,
        'distance_nm' => 250,
        'pilot_comments' => 'Good flight',
        'initial_fuel_on_board' => 5000,
        'zero_fuel_weight' => 50000,
        'crash' => 0,
        'sim_aircraft_name' => 'Zibo 737-800',
    ],
    // Flight 102 - 120 minutes, 350 nm (longest)
    [
        'id' => 102,
        'flight_id' => 102,
        'start_time' => '2025-01-15 09:30:00',
        'end_time' => '2025-01-15 11:30:00',
        'flight_time_minutes' => 120,
        'block_time_minutes' => 130,
        'total_fuel_burn_kg' => 3200,
        'distance_nm' => 350,
        'pilot_comments' => 'Long haul test',
        'initial_fuel_on_board' => 6000,
        'zero_fuel_weight' => 50000,
        'crash' => 0,
        'sim_aircraft_name' => 'Zibo 737-800',
    ],
    // Flight 103 - 55 minutes, 180 nm
    [
        'id' => 103,
        'flight_id' => 103,
        'start_time' => '2025-01-20 14:30:00',
        'end_time' => '2025-01-20 15:25:00',
        'flight_time_minutes' => 55,
        'block_time_minutes' => 60,
        'total_fuel_burn_kg' => 1500,
        'distance_nm' => 180,
        'pilot_comments' => 'Short hop',
        'initial_fuel_on_board' => 4000,
        'zero_fuel_weight' => 48000,
        'crash' => 0,
        'sim_aircraft_name' => 'Zibo 737-800',
    ],
    // Flight 104 (Rejected) - has data but shouldn't count
    [
        'id' => 104,
        'flight_id' => 104,
        'start_time' => '2025-01-11 08:30:00',
        'end_time' => '2025-01-11 09:30:00',
        'flight_time_minutes' => 60,
        'block_time_minutes' => 65,
        'total_fuel_burn_kg' => 1800,
        'distance_nm' => 200,
        'pilot_comments' => 'Rejected flight',
        'initial_fuel_on_board' => 4500,
        'zero_fuel_weight' => 49000,
        'crash' => 0,
        'sim_aircraft_name' => 'Zibo 737-800',
    ],
    // Flight 105 (Created) - has data but shouldn't count
    [
        'id' => 105,
        'flight_id' => 105,
        'start_time' => '2025-01-18 08:30:00',
        'end_time' => '2025-01-18 09:15:00',
        'flight_time_minutes' => 45,
        'block_time_minutes' => 50,
        'total_fuel_burn_kg' => 1200,
        'distance_nm' => 150,
        'pilot_comments' => 'Created but not submitted',
        'initial_fuel_on_board' => 4000,
        'zero_fuel_weight' => 48000,
        'crash' => 0,
        'sim_aircraft_name' => 'Zibo 737-800',
    ],
    // Flight 106 (Pending Validation) - has data but shouldn't count
    [
        'id' => 106,
        'flight_id' => 106,
        'start_time' => '2025-01-22 08:30:00',
        'end_time' => '2025-01-22 09:10:00',
        'flight_time_minutes' => 40,
        'block_time_minutes' => 45,
        'total_fuel_burn_kg' => 1100,
        'distance_nm' => 130,
        'pilot_comments' => 'Pending validation',
        'initial_fuel_on_board' => 3500,
        'zero_fuel_weight' => 47000,
        'crash' => 0,
        'sim_aircraft_name' => 'Zibo 737-800',
    ],
    // Flight 107 (Finished but NO flight_time_minutes) - shouldn't count
    [
        'id' => 107,
        'flight_id' => 107,
        'start_time' => '2025-01-27 08:30:00',
        'end_time' => '2025-01-27 09:30:00',
        'flight_time_minutes' => null,
        'block_time_minutes' => null,
        'total_fuel_burn_kg' => null,
        'distance_nm' => null,
        'pilot_comments' => 'Incomplete report data',
        'initial_fuel_on_board' => null,
        'zero_fuel_weight' => null,
        'crash' => null,
        'sim_aircraft_name' => 'Zibo 737-800',
    ],
    // Flight 108 (December 2024) - different period
    [
        'id' => 108,
        'flight_id' => 108,
        'start_time' => '2024-12-15 08:30:00',
        'end_time' => '2024-12-15 10:05:00',
        'flight_time_minutes' => 95,
        'block_time_minutes' => 100,
        'total_fuel_burn_kg' => 2600,
        'distance_nm' => 280,
        'pilot_comments' => 'December flight',
        'initial_fuel_on_board' => 5200,
        'zero_fuel_weight' => 50000,
        'crash' => 0,
        'sim_aircraft_name' => 'Zibo 737-800',
    ],
];
