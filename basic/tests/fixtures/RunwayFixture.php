<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class RunwayFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Runway';
    public $depends = ['tests\fixtures\AirportFixture'];
}
