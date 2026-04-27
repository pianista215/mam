<?php

// B738 (aircraft_type_id=2) requires PPL (credential_type_id=1).
// C172 (aircraft_type_id=4) has no entry → freely flyable by any pilot.
return [
    ['credential_type_id' => 1, 'aircraft_type_id' => 2],
];
