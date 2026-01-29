<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class FlightPhaseMetricTypeFixture extends ActiveFixture
{
    public $modelClass = 'app\models\FlightPhaseMetricType';
    public $depends = [
        'tests\fixtures\FlightPhaseTypeFixture',
    ];
}
