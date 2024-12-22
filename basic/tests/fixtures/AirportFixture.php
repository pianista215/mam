<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class AirportFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Airport';
    public $depends = ['tests\fixtures\CountryFixture'];
}