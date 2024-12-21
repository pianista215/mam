<?php

return [
    [
        'id' => 1,
        'country_id' => 1,
        'license' => 'AB1234',
        'name' => 'John',
        'surname' => 'Doe',
        'email' => 'john.doe@example.com',
        'password' => Yii::$app->security->generatePasswordHash('SecurePass123!'),
        'city' => 'Madrid',
        'location' => 'LEMD',
        'date_of_birth' => '1990-01-01',
    ],
    [
        'id' => 2,
        'country_id' => 1,
        'license' => 'ADM123',
        'name' => 'Admin',
        'surname' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Yii::$app->security->generatePasswordHash('admin1234!'),
        'city' => 'Valladolid',
        'location' => 'LEMD',
        'date_of_birth' => '1980-01-01',
    ],
];