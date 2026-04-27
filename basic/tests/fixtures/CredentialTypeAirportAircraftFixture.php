<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class CredentialTypeAirportAircraftFixture extends ActiveFixture
{
    public $modelClass = 'app\models\CredentialTypeAirportAircraft';
    public $depends    = [
        'tests\fixtures\CredentialTypeFixture',
        'tests\fixtures\AircraftTypeFixture',
        'tests\fixtures\AirportFixture',
    ];
}
