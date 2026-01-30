<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class StatisticRankingFixture extends ActiveFixture
{
    public $modelClass = 'app\models\StatisticRanking';
    public $depends = [
        'tests\fixtures\StatisticPeriodFixture',
        // Note: StatisticRankingType is seed data from statistics.sql, not a fixture
    ];
}
