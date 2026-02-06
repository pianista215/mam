<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class RunwayEndFixture extends ActiveFixture
{
    public $modelClass = 'app\models\RunwayEnd';
    public $depends = ['tests\fixtures\RunwayFixture'];
}
