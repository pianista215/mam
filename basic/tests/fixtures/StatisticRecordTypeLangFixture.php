<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class StatisticRecordTypeLangFixture extends ActiveFixture
{
    public $modelClass = 'app\models\StatisticRecordTypeLang';
    public $depends = [
        'tests\fixtures\StatisticRecordTypeFixture',
    ];
}
