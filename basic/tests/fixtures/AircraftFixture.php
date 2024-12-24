<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class AircraftFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Aircraft';
    public $depends = [
        'tests\fixtures\AircraftConfigurationFixture',
        'tests\fixtures\AirportFixture'
    ];
}