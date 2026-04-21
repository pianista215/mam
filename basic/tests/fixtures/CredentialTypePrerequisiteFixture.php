<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class CredentialTypePrerequisiteFixture extends ActiveFixture
{
    public $modelClass = 'app\models\CredentialTypePrerequisite';
    public $depends    = ['tests\fixtures\CredentialTypeFixture'];
}
