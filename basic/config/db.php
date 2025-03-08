<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=172.17.0.2;dbname=mam',
    'username' => 'mam',
    'password' => 'complex-password',
    'charset' => 'utf8',

    'enableLogging' => false,
    'enableProfiling' => false,

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
