<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class RouteFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Route';
    public $depends = ['tests\fixtures\AirportFixture'];
}