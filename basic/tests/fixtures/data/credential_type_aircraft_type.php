<?php

// B738 (aircraft_type_id=2) requires PPL (credential_type_id=1).
// C172 (aircraft_type_id=4) requires PPL — both are unrestricted by default,
// but PPL is used here so C172 is visible only to credentialed pilots.
// B738 → PPL (not CPL) is intentional: it allows testing the airport-restriction
// layer independently (GCLP adds a CPL requirement on top of the type restriction).
return [
    ['credential_type_id' => 1, 'aircraft_type_id' => 2],  // B738 requires PPL
    ['credential_type_id' => 1, 'aircraft_type_id' => 4],  // C172 requires PPL
];
