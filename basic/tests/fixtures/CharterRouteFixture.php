<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class CharterRouteFixture extends ActiveFixture
{
    public $modelClass = 'app\models\CharterRoute';
    public $depends = ['tests\fixtures\AirportFixture'];
}