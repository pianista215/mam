<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class LiveFlightPositionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\LiveFlightPosition';
    public $depends = ['tests\fixtures\SubmittedFlightPlanFixture'];
}
