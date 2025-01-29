<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class AcarsFileFixture extends ActiveFixture
{
    public $modelClass = 'app\models\AcarsFile';
    public $depends = [
                        'tests\fixtures\FlightReportFixture'
                        ];
}