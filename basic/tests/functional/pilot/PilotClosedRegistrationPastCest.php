<?php

namespace tests\functional\pilot;

use tests\fixtures\ConfigFixture;
use Yii;

class PilotClosedRegistrationPastCest
{

    public function _fixtures(){
        return [
            'config' => [
                'class' => ConfigFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/registration_config_closed_past.php'),
            ],
        ];
    }

    public function openPilotRegistrationAndSeeRegistrationIsClosed(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot/register');
        $I->see('registration is closed');
    }

}