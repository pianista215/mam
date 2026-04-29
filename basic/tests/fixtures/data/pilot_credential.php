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
    [
        // Pilot 5 (Ifr Validator), PPL, Active con expiry → será limpiado al activar CPL (id=6)
        'id'                 => 5,
        'pilot_id'           => 5,
        'credential_type_id' => 1,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2023-01-01',
        'expiry_date'        => '2026-12-31',
        'issued_by'          => 2,
    ],
    [
        // Pilot 5 (Ifr Validator), CPL, Student → para test actionActivate + ancestor clear
        'id'                 => 6,
        'pilot_id'           => 5,
        'credential_type_id' => 3,
        'status'             => PilotCredential::STATUS_STUDENT,
        'issued_date'        => '2025-01-01',
        'expiry_date'        => null,
        'issued_by'          => 2,
    ],
    [
        // Pilot 6 (Vfr School), PPL, Active sin expiry → base de la cadena
        'id'                 => 7,
        'pilot_id'           => 6,
        'credential_type_id' => 1,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2022-01-01',
        'expiry_date'        => null,
        'issued_by'          => 2,
    ],
    [
        // Pilot 6 (Vfr School), CPL, Active con expiry → para cascade renew + revoke tests
        'id'                 => 8,
        'pilot_id'           => 6,
        'credential_type_id' => 3,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2024-01-01',
        'expiry_date'        => '2026-06-01',
        'issued_by'          => 2,
    ],
    [
        // Pilot 6 (Vfr School), IR, Active con expiry → será cascade-renovado al renovar CPL
        'id'                 => 9,
        'pilot_id'           => 6,
        'credential_type_id' => 2,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2024-01-01',
        'expiry_date'        => '2025-12-31',
        'issued_by'          => 2,
    ],
    [
        // Pilot 7 (Ifr School), PPL, Active con expiry → será limpiado al emitir CPL activo
        'id'                 => 10,
        'pilot_id'           => 7,
        'credential_type_id' => 1,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2023-01-01',
        'expiry_date'        => '2026-06-01',
        'issued_by'          => 2,
    ],
    [
        // Pilot 6 (Vfr School), B738 Type Rating, Active → para tests FPL: ve B738 y pasa GCLP con MNPS
        'id'                 => 11,
        'pilot_id'           => 6,
        'credential_type_id' => 5,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2024-06-01',
        'expiry_date'        => null,
        'issued_by'          => 2,
    ],
    [
        // Pilot 6 (Vfr School), MNPS Certification, Active → desbloquea B738 en GCLP
        'id'                 => 12,
        'pilot_id'           => 6,
        'credential_type_id' => 4,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2024-06-01',
        'expiry_date'        => null,
        'issued_by'          => 2,
    ],
    [
        // Pilot 7 (Ifr School), B738 Type Rating, Active → ve B738 pero bloqueado en GCLP sin MNPS
        'id'                 => 13,
        'pilot_id'           => 7,
        'credential_type_id' => 5,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2024-06-01',
        'expiry_date'        => null,
        'issued_by'          => 2,
    ],
    [
        // John Doe, B738 Type Rating, Active → allows legacy FPL tests with EC-BBB (B738)
        'id'                 => 14,
        'pilot_id'           => 1,
        'credential_type_id' => 5,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2023-06-01',
        'expiry_date'        => null,
        'issued_by'          => 2,
    ],
    [
        // John Doe, MNPS, Active → allows B738 at GCLP (required by airport restriction fixture)
        'id'                 => 15,
        'pilot_id'           => 1,
        'credential_type_id' => 4,
        'status'             => PilotCredential::STATUS_ACTIVE,
        'issued_date'        => '2023-06-01',
        'expiry_date'        => null,
        'issued_by'          => 2,
    ],
];
