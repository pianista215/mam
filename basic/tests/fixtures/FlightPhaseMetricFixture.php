<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class FlightPhaseMetricFixture extends ActiveFixture
{
    public $modelClass = 'app\models\FlightPhaseMetric';
    public $depends = [
        'tests\fixtures\FlightPhaseFixture',
        'tests\fixtures\FlightPhaseMetricTypeFixture',
    ];
}
