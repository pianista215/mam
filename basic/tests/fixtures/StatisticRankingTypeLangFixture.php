<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class StatisticRankingTypeLangFixture extends ActiveFixture
{
    public $modelClass = 'app\models\StatisticRankingTypeLang';
    public $depends = [
        'tests\fixtures\StatisticRankingTypeFixture',
    ];
}
