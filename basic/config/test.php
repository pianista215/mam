<?php
$params = require __DIR__ . '/params.php';
$db = getenv('GITHUB_ACTIONS') ? require __DIR__ . '/test_db_github.php' : require __DIR__ . '/test_db.php';

/**
 * Application configuration shared by all test types
 */
return [
    'id' => 'basic-tests',
    'basePath' => dirname(__DIR__),
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'bootstrap' => [
        [
            'class' => 'app\components\LanguageSelector',
            'supportedLanguages' => ['en', 'es'],
        ],
    ],
    'language' => 'en-US',
    'components' => [
        'db' => $db,
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
            'messageClass' => 'yii\symfonymailer\Message'
        ],
        'assetManager' => [
            'basePath' => __DIR__ . '/../web/assets',
        ],
        'cache' => [
            'class' => 'yii\caching\DummyCache',
        ],
        'user' => [
            'identityClass' => 'app\models\Pilot',
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            // uncomment if you want to cache RBAC items hierarchy (TODO: Check in the future)
            //'cache' => 'cache',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => true,
            'rules' => [
                'page/edit' => 'page/edit',
                'page/<code:[a-zA-Z0-9_\-]+>' => 'page/view',
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
            ],
        ],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            // but if you absolutely need it set cookie domain to localhost
            /*
            'csrfCookie' => [
                'domain' => 'localhost',
            ],
            */
        ],
    ],
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
    'params' => $params,
];
