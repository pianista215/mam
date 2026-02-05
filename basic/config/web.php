<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$mailer = require __DIR__ . '/mailer.php';
$secret = require __DIR__ . '/secret.php';

$config = [
    'id' => 'basic',
    'version' => $params['version'],
    'language' => 'es-ES',
    'sourceLanguage' => 'en-US',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        [
            'class' => 'app\components\LanguageSelector',
            'supportedLanguages' => ['en', 'es'],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => $secret['cookieValidationKey'],
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en-US',
                    'fileMap' => [
                        'app' => 'app.php',
                    ],
                ],
            ],
        ],
        'user' => [
            'identityClass' => 'app\models\Pilot',
            'enableAutoLogin' => true,
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            // uncomment if you want to cache RBAC items hierarchy (TODO: Check in the future)
            //'cache' => 'cache',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => $mailer,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => 'php://stdout',
                    'levels' => ['warning', 'info'],
                    'logVars' => [],
                    'categories' => ['mam_*'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => 'php://stdout',
                    'levels' => ['error'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => 'php://stdout',
                    'levels' => ['warning'],
                    'except' => ['mam_*'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'page/edit' => 'page/edit',
                'page/<code:[a-zA-Z0-9_\-]+>' => 'page/view',
                'acars-updater/update/<file:.+>' => 'acars-updater/update',
                [
                    'prefix' => 'api/v1',
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['auth'],
                ],
                [
                    'prefix' => 'api/v1',
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['flight-plan'],
                ],
                [
                    'prefix' => 'api/v1',
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['flight-report'],
                ],
                [
                    'prefix' => 'api/v1',
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['live-position'],
                ],
            ],
        ],
    ],
    'params' => $params,
    'modules' => [
        'api' => [
            'class' => 'app\modules\api\Module',
                'modules' => [
                    'v1' => [
                        'class' => 'yii\base\Module',
                        'controllerNamespace' => 'app\modules\api\controllers\v1',
                ]
            ]
        ],
    ],
];

//if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
//}

return $config;
