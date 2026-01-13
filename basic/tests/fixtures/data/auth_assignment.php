<?php

use app\rbac\constants\Roles;

return [
    [
        'item_name' => Roles::ADMIN,
        'user_id' => '2',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::PILOT,
        'user_id' => '1',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::PILOT,
        'user_id' => '4',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::VFR_VALIDATOR,
        'user_id' => '4',
        'created_at' => time(),
    ],
    // TODO: Think if validators should inherit pilot role, or just different roles
    [
        'item_name' => Roles::PILOT,
        'user_id' => '5',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::IFR_VALIDATOR,
        'user_id' => '5',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::PILOT,
        'user_id' => '6',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::PILOT,
        'user_id' => '7',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::PILOT,
        'user_id' => '8',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::PILOT,
        'user_id' => '9',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::FLEET_MANAGER,
        'user_id' => '9',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::PILOT,
        'user_id' => '10',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::TOUR_MANAGER,
        'user_id' => '10',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::PILOT,
        'user_id' => '11',
        'created_at' => time(),
    ],
    [
        'item_name' => Roles::FLEET_OPERATOR,
        'user_id' => '11',
        'created_at' => time(),
    ],
];