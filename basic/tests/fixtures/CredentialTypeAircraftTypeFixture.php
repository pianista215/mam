<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class CredentialTypeAircraftTypeFixture extends ActiveFixture
{
    public $modelClass = 'app\models\CredentialTypeAircraftType';
    public $depends    = [
        'tests\fixtures\CredentialTypeFixture',
        'tests\fixtures\AircraftTypeFixture',
    ];
}
