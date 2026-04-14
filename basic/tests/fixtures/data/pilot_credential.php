<?php

use app\models\PilotCredential;

return [
    [
        // John Doe, PPL, Active sin expiración, vigente → badge Active (verde)
        'id'                 => 1,
        'pilot_id'           => 1,
        'credential_type_id' => 1,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2024-01-15',
        'expiry_date'        => null,
        'superseded_at'      => null,
        'created_at'         => '2024-01-15 10:00:00',
        'notes'              => null,
        'issued_by'          => 2,
    ],
    [
        // Vfr Validator, PPL, Student, vigente → badge Student (azul)
        'id'                 => 2,
        'pilot_id'           => 4,
        'credential_type_id' => 1,
        'status'             => PilotCredential::STATUS_STUDENT,
        'issued_date'        => '2025-03-01',
        'expiry_date'        => null,
        'superseded_at'      => null,
        'created_at'         => '2025-03-01 09:00:00',
        'notes'              => null,
        'issued_by'          => 2,
    ],
    [
        // John Doe, PPL histórico (superseded_at set) → NO debe aparecer en la vista
        'id'                 => 3,
        'pilot_id'           => 1,
        'credential_type_id' => 1,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2023-06-01',
        'expiry_date'        => '2024-06-01',
        'superseded_at'      => '2024-01-15 09:59:00',
        'created_at'         => '2023-06-01 08:00:00',
        'notes'              => 'Superseded by renewal',
        'issued_by'          => 2,
    ],
    [
        // John Doe, IR, Active con expiry futuro (2027), vigente → badge Active
        'id'                 => 4,
        'pilot_id'           => 1,
        'credential_type_id' => 2,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2025-01-10',
        'expiry_date'        => '2027-01-10',
        'superseded_at'      => null,
        'created_at'         => '2025-01-10 12:00:00',
        'notes'              => null,
        'issued_by'          => 2,
    ],
    [
        // Vfr Validator, IR, Active con expiry pasado (2023), vigente → badge Expired (rojo)
        'id'                 => 5,
        'pilot_id'           => 4,
        'credential_type_id' => 2,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2022-05-01',
        'expiry_date'        => '2023-05-01',
        'superseded_at'      => null,
        'created_at'         => '2022-05-01 09:00:00',
        'notes'              => null,
        'issued_by'          => 2,
    ],
];
