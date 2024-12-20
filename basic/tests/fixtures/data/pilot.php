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
        'city' => 'New York',
        'location' => 'LEMD',
        'date_of_birth' => '1990-01-01',
    ],
];