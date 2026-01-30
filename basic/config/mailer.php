<?php

return [
    'class' => \yii\symfonymailer\Mailer::class,
    'viewPath' => '@app/mail',
    // send all mails to a file by default.
    // Set 'useFileTransport' to false and configure 'transport' for real email delivery.
    'useFileTransport' => true,
    /*
    // Local testing with Mailhog (http://localhost:8025):
    'useFileTransport' => false,
    'transport' => [
        'scheme' => 'smtp',
        'host' => '127.0.0.1',
        'port' => 1025,
    ],
    */

    /*
    // Production SMTP:
    'useFileTransport' => false,
    'transport' => [
        'scheme' => 'smtps',
        'host' => 'smtp.example.com',
        'username' => 'your_username',
        'password' => 'your_password',
        'port' => 465,
    ],
    */
];
