<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class FlightReportFixture extends ActiveFixture
{
    public $modelClass = 'app\models\FlightReport';
    public $depends = [
                        'tests\fixtures\FlightFixture'
                        ];
}