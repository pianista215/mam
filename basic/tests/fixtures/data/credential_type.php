<?php

use app\models\CredentialType;

return [
    [
        'id'          => 1,
        'code'        => 'PPL',
        'name'        => 'Private Pilot License',
        'type'        => CredentialType::TYPE_LICENSE,
        'description' => 'Allows VFR flight as pilot in command.',
    ],
    [
        'id'          => 2,
        'code'        => 'IR',
        'name'        => 'Instrument Rating',
        'type'        => CredentialType::TYPE_RATING,
        'description' => null,
    ],
    [
        'id'          => 3,
        'code'        => 'CPL',
        'name'        => 'Commercial Pilot License',
        'type'        => CredentialType::TYPE_LICENSE,
        'description' => 'Allows commercial operations as PIC.',
    ],
    [
        'id'          => 4,
        'code'        => 'MNPS',
        'name'        => 'MNPS Certification',
        'type'        => CredentialType::TYPE_CERTIFICATION,
        'description' => null,
    ],
    [
        'id'          => 5,
        'code'        => 'B738',
        'name'        => 'Boeing 737-800 Type Rating',
        'type'        => CredentialType::TYPE_RATING,
        'description' => 'Type rating for Boeing 737-800 series aircraft.',
    ],
    [
        'id'          => 6,
        'code'        => 'NVG',
        'name'        => 'Night Vision Goggles',
        'type'        => CredentialType::TYPE_CERTIFICATION,
        'description' => null,
    ],
];
