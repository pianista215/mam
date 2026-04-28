<?php

// PPL (id=1) → C172 (type_id=4): light aircraft for private pilots
// CPL (id=3) → BE58 (type_id=5): twin-engine piston for commercial pilots
// B738 Rating (id=5) → B738 (type_id=2): jet type rating required for 737-800
// No entry for IR or MNPS: they do not unlock any aircraft type by themselves.
// No entry for ATPL: not tested in fixtures.
return [
    ['credential_type_id' => 1, 'aircraft_type_id' => 4],  // PPL → C172
    ['credential_type_id' => 3, 'aircraft_type_id' => 5],  // CPL → BE58
    ['credential_type_id' => 5, 'aircraft_type_id' => 2],  // B738 Rating → B738
];
