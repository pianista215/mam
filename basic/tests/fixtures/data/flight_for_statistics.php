<?php

/**
 * Flight fixtures for statistics testing with dynamic dates.
 *
 * Uses current month and previous month for realistic testing.
 *
 * Summary:
 * - Flights 101-103: status 'F' (finished) in current month - should count
 * - Flight 104: status 'R' (rejected) - should NOT count
 * - Flight 105: status 'C' (created) - should NOT count
 * - Flight 106: status 'V' (pending validation) - should NOT count
 * - Flight 107: status 'F' but no flight_time_minutes in report - should NOT count
 * - Flight 108: status 'F' in previous month - different period
 *
 * Pilots:
 * - pilot_id 5: flights 101, 102 (2 flights, more hours)
 * - pilot_id 7: flight 103 (1 flight)
 *
 * Aircraft Types:
 * - aircraft 4, 6: aircraft_type_id 2 (B738)
 * - aircraft 3: aircraft_type_id 4 (C172)
 */

$now = new DateTimeImmutable();
$currentYear = (int) $now->format('Y');
$currentMonth = (int) $now->format('n');

// Calculate previous month
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

// Dates for current month flights
$currentMonth10 = sprintf('%04d-%02d-10 08:00:00', $currentYear, $currentMonth);
$currentMonth15 = sprintf('%04d-%02d-15 09:00:00', $currentYear, $currentMonth);
$currentMonth20 = sprintf('%04d-%02d-20 14:00:00', $currentYear, $currentMonth);
$currentMonth11 = sprintf('%04d-%02d-11 08:00:00', $currentYear, $currentMonth);
$currentMonth18 = sprintf('%04d-%02d-18 08:00:00', $currentYear, $currentMonth);
$currentMonth22 = sprintf('%04d-%02d-22 08:00:00', $currentYear, $currentMonth);
$currentMonth27 = sprintf('%04d-%02d-27 08:00:00', $currentYear, $currentMonth);

// Dates for previous month
$prevMonth15 = sprintf('%04d-%02d-15 08:00:00', $prevYear, $prevMonth);

// Validation dates
$valDate15 = sprintf('%04d-%02d-15 10:00:00', $currentYear, $currentMonth);
$valDate20 = sprintf('%04d-%02d-20 10:00:00', $currentYear, $currentMonth);
$valDate25 = sprintf('%04d-%02d-25 10:00:00', $currentYear, $currentMonth);
$valDate12 = sprintf('%04d-%02d-12 10:00:00', $currentYear, $currentMonth);
$valDate28 = sprintf('%04d-%02d-28 10:00:00', $currentYear, $currentMonth);
$valDatePrev = sprintf('%04d-%02d-20 10:00:00', $prevYear, $prevMonth);

return [
    // Finished flight - pilot 5, aircraft 4 - current month
    [
        'id' => 101,
        'pilot_id' => 5,
        'aircraft_id' => 4,
        'code' => 'STAT001',
        'departure' => 'LEBL',
        'arrival' => 'LEMD',
        'alternative1_icao' => 'LEVC',
        'flight_rules' => 'I',
        'cruise_speed_unit' => 'N',
        'cruise_speed_value' => '350',
        'flight_level_unit' => 'F',
        'flight_level_value' => '320',
        'route' => 'DCT',
        'estimated_time' => '0130',
        'other_information' => 'Stats test',
        'endurance_time' => '0400',
        'report_tool' => 'Mam Acars 1.0',
        'status' => 'F',
        'validator_id' => 5,
        'validation_date' => $valDate15,
        'creation_date' => $currentMonth10,
        'flight_type' => 'R',
    ],
    // Finished flight - pilot 5, aircraft 6 - current month (longest flight)
    [
        'id' => 102,
        'pilot_id' => 5,
        'aircraft_id' => 6,
        'code' => 'STAT002',
        'departure' => 'LEMD',
        'arrival' => 'LEBL',
        'alternative1_icao' => 'LEVC',
        'flight_rules' => 'I',
        'cruise_speed_unit' => 'N',
        'cruise_speed_value' => '350',
        'flight_level_unit' => 'F',
        'flight_level_value' => '320',
        'route' => 'DCT',
        'estimated_time' => '0200',
        'other_information' => 'Stats test',
        'endurance_time' => '0400',
        'report_tool' => 'Mam Acars 1.0',
        'status' => 'F',
        'validator_id' => 5,
        'validation_date' => $valDate20,
        'creation_date' => $currentMonth15,
        'flight_type' => 'R',
    ],
    // Finished flight - pilot 7, aircraft 3 (C172) - current month
    [
        'id' => 103,
        'pilot_id' => 7,
        'aircraft_id' => 3, // C172 (aircraft_type_id=4)
        'code' => 'STAT003',
        'departure' => 'LEBL',
        'arrival' => 'LEVC',
        'alternative1_icao' => 'LEMD',
        'flight_rules' => 'I',
        'cruise_speed_unit' => 'N',
        'cruise_speed_value' => '350',
        'flight_level_unit' => 'F',
        'flight_level_value' => '280',
        'route' => 'DCT',
        'estimated_time' => '0100',
        'other_information' => 'Stats test',
        'endurance_time' => '0300',
        'report_tool' => 'Mam Acars 1.0',
        'status' => 'F',
        'validator_id' => 5,
        'validation_date' => $valDate25,
        'creation_date' => $currentMonth20,
        'flight_type' => 'R',
    ],
    // REJECTED flight - should NOT count in statistics
    [
        'id' => 104,
        'pilot_id' => 5,
        'aircraft_id' => 4,
        'code' => 'STAT004',
        'departure' => 'LEMD',
        'arrival' => 'LEVC',
        'alternative1_icao' => 'LEBL',
        'flight_rules' => 'I',
        'cruise_speed_unit' => 'N',
        'cruise_speed_value' => '350',
        'flight_level_unit' => 'F',
        'flight_level_value' => '300',
        'route' => 'DCT',
        'estimated_time' => '0100',
        'other_information' => 'Stats test - rejected',
        'endurance_time' => '0300',
        'report_tool' => 'Mam Acars 1.0',
        'status' => 'R',
        'validator_id' => 5,
        'validator_comments' => 'Invalid flight',
        'validation_date' => $valDate12,
        'creation_date' => $currentMonth11,
        'flight_type' => 'R',
    ],
    // CREATED flight (not yet submitted) - should NOT count
    [
        'id' => 105,
        'pilot_id' => 7,
        'aircraft_id' => 6,
        'code' => 'STAT005',
        'departure' => 'LEVC',
        'arrival' => 'LEMD',
        'alternative1_icao' => 'LEBL',
        'flight_rules' => 'I',
        'cruise_speed_unit' => 'N',
        'cruise_speed_value' => '350',
        'flight_level_unit' => 'F',
        'flight_level_value' => '300',
        'route' => 'DCT',
        'estimated_time' => '0100',
        'other_information' => 'Stats test - created',
        'endurance_time' => '0300',
        'report_tool' => 'Mam Acars 1.0',
        'status' => 'C',
        'creation_date' => $currentMonth18,
        'flight_type' => 'R',
    ],
    // PENDING VALIDATION flight - should NOT count
    [
        'id' => 106,
        'pilot_id' => 5,
        'aircraft_id' => 7,
        'code' => 'STAT006',
        'departure' => 'LEBL',
        'arrival' => 'LEAL',
        'alternative1_icao' => 'LEVC',
        'flight_rules' => 'I',
        'cruise_speed_unit' => 'N',
        'cruise_speed_value' => '350',
        'flight_level_unit' => 'F',
        'flight_level_value' => '280',
        'route' => 'DCT',
        'estimated_time' => '0045',
        'other_information' => 'Stats test - pending',
        'endurance_time' => '0300',
        'report_tool' => 'Mam Acars 1.0',
        'status' => 'V',
        'creation_date' => $currentMonth22,
        'flight_type' => 'R',
    ],
    // FINISHED but report has NO flight_time_minutes - should NOT count
    [
        'id' => 107,
        'pilot_id' => 7,
        'aircraft_id' => 4,
        'code' => 'STAT007',
        'departure' => 'LEMD',
        'arrival' => 'LEAL',
        'alternative1_icao' => 'LEVC',
        'flight_rules' => 'I',
        'cruise_speed_unit' => 'N',
        'cruise_speed_value' => '350',
        'flight_level_unit' => 'F',
        'flight_level_value' => '280',
        'route' => 'DCT',
        'estimated_time' => '0100',
        'other_information' => 'Stats test - no report data',
        'endurance_time' => '0300',
        'report_tool' => 'Mam Acars 1.0',
        'status' => 'F',
        'validator_id' => 5,
        'validation_date' => $valDate28,
        'creation_date' => $currentMonth27,
        'flight_type' => 'R',
    ],
    // FINISHED in previous month - different period
    [
        'id' => 108,
        'pilot_id' => 5,
        'aircraft_id' => 6,
        'code' => 'STAT008',
        'departure' => 'LEBL',
        'arrival' => 'LEMD',
        'alternative1_icao' => 'LEVC',
        'flight_rules' => 'I',
        'cruise_speed_unit' => 'N',
        'cruise_speed_value' => '350',
        'flight_level_unit' => 'F',
        'flight_level_value' => '320',
        'route' => 'DCT',
        'estimated_time' => '0130',
        'other_information' => 'Stats test - Previous month',
        'endurance_time' => '0400',
        'report_tool' => 'Mam Acars 1.0',
        'status' => 'F',
        'validator_id' => 5,
        'validation_date' => $valDatePrev,
        'creation_date' => $prevMonth15,
        'flight_type' => 'R',
    ],
];
