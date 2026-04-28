<?php

// Flying B738 (aircraft_type_id=2) to GCLP additionally requires MNPS (credential_type_id=4).
// No rows for other airports → no airport-specific restrictions elsewhere.
return [
    ['credential_type_id' => 4, 'aircraft_type_id' => 2, 'airport_icao' => 'GCLP'],
];
