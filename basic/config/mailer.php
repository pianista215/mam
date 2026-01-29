<?php

return [
    'class' => \yii\symfonymailer\Mailer::class,
    'viewPath' => '@app/mail',
    // send all mails to a file by default.
    // Set 'useFileTransport' to false and configure 'transport' for real email delivery.
    'useFileTransport' => true,
    /*
    'transport' => [
        'scheme' => 'smtps',
        'host' => 'smtp.example.com',
        'username' => 'your_username',
        'password' => 'your_password',
        'port' => 465,
    ],
    */
];
