<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class StatisticAggregateFixture extends ActiveFixture
{
    public $modelClass = 'app\models\StatisticAggregate';
    public $depends = [
        'tests\fixtures\StatisticPeriodFixture',
        // Note: StatisticAggregateType is seed data from statistics.sql, not a fixture
    ];
}
