<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class ConfigFixture extends ActiveFixture
{
    public $tableName = 'config';
    public $depends = ['tests\fixtures\AirportFixture'];
}