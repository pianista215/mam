<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class AircraftTypeResourceFixture extends ActiveFixture
{
    public $tableName = '{{%aircraft_type_resource}}';
    public $depends   = ['tests\fixtures\AircraftTypeFixture'];
}
