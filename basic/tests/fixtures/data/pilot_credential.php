<?php

use app\models\PilotCredential;

return [
    [
        // John Doe, PPL, Active sin expiración → badge Active (verde)
        'id'                 => 1,
        'pilot_id'           => 1,
        'credential_type_id' => 1,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2024-01-15',
        'expiry_date'        => null,
        'issued_by'          => 2,
    ],
    [
        // Vfr Validator, PPL, Student → badge Student (azul)
        'id'                 => 2,
        'pilot_id'           => 4,
        'credential_type_id' => 1,
        'status'             => PilotCredential::STATUS_STUDENT,
        'issued_date'        => '2025-03-01',
        'expiry_date'        => null,
        'issued_by'          => 2,
    ],
    [
        // John Doe, IR, Active con expiry futuro (2027) → badge Active
        'id'                 => 3,
        'pilot_id'           => 1,
        'credential_type_id' => 2,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2025-01-10',
        'expiry_date'        => '2027-01-10',
        'issued_by'          => 2,
    ],
    [
        // Vfr Validator, IR, Active con expiry pasado (2023) → badge Expired (rojo)
        'id'                 => 4,
        'pilot_id'           => 4,
        'credential_type_id' => 2,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2022-05-01',
        'expiry_date'        => '2023-05-01',
        'issued_by'          => 2,
    ],
];
