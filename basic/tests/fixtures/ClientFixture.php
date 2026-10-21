<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class ClientFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Client';
    public $depends    = ['tests\fixtures\AirportFixture'];
}
