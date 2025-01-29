<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class FlightFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Flight';
    public $depends = [
                        'tests\fixtures\AirportFixture',
                        'tests\fixtures\AircraftFixture',
                        'tests\fixtures\PilotFixture'
                        ];
}