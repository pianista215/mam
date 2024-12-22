<?php

namespace tests\functional\pilot;

use tests\fixtures\ConfigFixture;
use Yii;

class PilotClosedRegistrationFutureCest
{

    public function _fixtures(){
        return [
            'config' => [
                'class' => ConfigFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/registration_config_closed_future.php'),
            ],
        ];
    }

    public function openPilotRegistrationAndSeeRegistrationIsClosed(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot/register');
        $I->see('registration is closed');
    }

}