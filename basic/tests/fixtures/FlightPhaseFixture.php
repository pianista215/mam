<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class FlightPhaseFixture extends ActiveFixture
{
    public $modelClass = 'app\models\FlightPhase';
    public $depends = [
        'tests\fixtures\FlightReportFixture',
        'tests\fixtures\FlightPhaseTypeFixture',
    ];
}
