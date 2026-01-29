<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class StatisticPeriodFixture extends ActiveFixture
{
    public $modelClass = 'app\models\StatisticPeriod';
    public $depends = [
        'tests\fixtures\StatisticPeriodTypeFixture',
    ];
}
