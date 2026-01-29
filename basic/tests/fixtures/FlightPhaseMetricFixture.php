<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class FlightPhaseMetricFixture extends ActiveFixture
{
    public $modelClass = 'app\models\FlightPhaseMetric';
    public $depends = [
        'tests\fixtures\FlightPhaseFixture',
        // Note: FlightPhaseMetricType is seed data from DDL, not a fixture
    ];
}
