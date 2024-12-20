<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class PilotFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Pilot';
    public $depends = ['tests\fixtures\AirportFixture'];
}