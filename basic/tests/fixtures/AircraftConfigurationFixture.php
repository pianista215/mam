<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class AircraftConfigurationFixture extends ActiveFixture
{
    public $modelClass = 'app\models\AircraftConfiguration';
    public $depends = ['tests\fixtures\AircraftTypeFixture'];
}