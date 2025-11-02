<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class TourStageFixture extends ActiveFixture
{
    public $modelClass = 'app\models\TourStage';
    public $depends = [
                        'tests\fixtures\TourFixture',
                        'tests\fixtures\FlightFixture',
    ];
}