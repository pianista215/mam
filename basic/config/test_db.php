<?php
$db = require __DIR__ . '/db.php';
// test database! Important not to run tests on production or development databases

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=172.17.0.2;dbname=test_mam_database',
    'username' => 'mam',
    'password' => 'complex-password',
    'charset' => 'utf8',

    'enableLogging' => false,
    'enableProfiling' => false,
];
