<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class ClientHubFixture extends ActiveFixture
{
    public $modelClass = 'app\models\ClientHub';
    public $depends    = [
        'tests\fixtures\ClientFixture',
        'tests\fixtures\AirportFixture',
    ];
}
