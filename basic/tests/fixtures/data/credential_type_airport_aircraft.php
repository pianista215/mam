<?php

// Flying B738 (aircraft_type_id=2) to GCLP additionally requires CPL (credential_type_id=3).
// No rows for other airports → no airport-specific restrictions.
return [
    ['credential_type_id' => 3, 'aircraft_type_id' => 2, 'airport_icao' => 'GCLP'],
];
