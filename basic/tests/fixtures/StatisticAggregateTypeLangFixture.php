<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class StatisticAggregateTypeLangFixture extends ActiveFixture
{
    public $modelClass = 'app\models\StatisticAggregateTypeLang';
    public $depends = [
        'tests\fixtures\StatisticAggregateTypeFixture',
    ];
}
