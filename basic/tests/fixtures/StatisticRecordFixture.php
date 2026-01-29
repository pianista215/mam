<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class StatisticRecordFixture extends ActiveFixture
{
    public $modelClass = 'app\models\StatisticRecord';
    public $depends = [
        'tests\fixtures\StatisticPeriodFixture',
        'tests\fixtures\StatisticRecordTypeFixture',
    ];
}
