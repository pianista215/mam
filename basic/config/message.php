<?php

return [
    'sourcePath' => dirname(__DIR__),
    'messagePath' => dirname(__DIR__) . '/messages',
    'languages' => ['es'],
    'translator' => 'Yii::t',
    'sort' => true,
    'overwrite' => true,
    'removeUnused' => true,
    'only' => ['*.php'],
    'except' => [
        '.git',
        'vendor',
        'runtime',
        'messages',
        'tests',
    ],
];
