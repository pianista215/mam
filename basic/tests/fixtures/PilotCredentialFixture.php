<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class PilotCredentialFixture extends ActiveFixture
{
    public $modelClass = 'app\models\PilotCredential';
    public $depends = [
        'tests\fixtures\CredentialTypeFixture',
        'tests\fixtures\PilotFixture',
    ];
}
