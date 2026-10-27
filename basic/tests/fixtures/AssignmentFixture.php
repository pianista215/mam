<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class AssignmentFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Assignment';
    public $depends = [
        'tests\fixtures\PilotFixture',
        'tests\fixtures\AircraftFixture',
        'tests\fixtures\ClientFixture',
    ];
}
