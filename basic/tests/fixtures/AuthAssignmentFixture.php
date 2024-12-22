<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class AuthAssignmentFixture extends ActiveFixture
{
    public $tableName = '{{%auth_assignment}}';
    public $depends = ['tests\fixtures\PilotFixture'];
}
