<?php

namespace tests\functional\pilot;

use tests\fixtures\ConfigFixture;
use Yii;

class PilotOpenRegistrationCest
{

    public function _fixtures(){
        return [
            'config' => [
                'class' => ConfigFixture::class,
                'dataFile' => Yii::getAlias('@app/tests/fixtures/data/registration_config_open.php'),
            ],
        ];
    }

    public function openPilotAndSubmitEmptyForm(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot/register');

        $I->see('Register Pilot');
        $I->see('Name');
        $I->see('Surname');
        $I->see('Register', 'button');
        $I->click('Register');
        $I->expectTo('see validations errors');
        $I->see('Name cannot be blank.');
        $I->see('Surname cannot be blank.');
        $I->see('Email cannot be blank.');
        $I->see('Country cannot be blank.');
        $I->see('City cannot be blank.');
        $I->see('Date Of Birth cannot be blank.');
    }

    public function openPilotAndRegisterSuccesfully(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot/register');

        $I->see('Register Pilot');
        $I->see('Name');
        $I->see('Surname');
        $I->see('Register', 'button');

       $I->fillField('#pilot-name', 'Pilot');
       $I->fillField('#pilot-surname', 'Surname');
       $I->fillField('#pilot-email', 'emailpilot@example.com');
       $I->fillField('#pilot-password', 'piLoT1234!');
       $I->selectOption('form select[name="Pilot[country_id]"]', 'Spain');
       $I->fillField('#pilot-city', 'Valladolid');
       $I->fillField('#pilot-date_of_birth', '1980-02-10');
       $I->fillField('#pilot-vatsim_id', '10001');
       $I->fillField('#pilot-ivao_id', '20002');
       $I->click('Register');

       $I->seeResponseCodeIs(200);
       $I->see('Thank you');

       $model = \app\models\Pilot::find()->where(['email' => 'emailpilot@example.com'])->one();
       $I->assertNotNull($model);
       $I->assertEquals('Pilot', $model->name);
       $I->assertEquals('Surname', $model->surname);
       $I->assertEquals('emailpilot@example.com', $model->email);
       $I->assertTrue(Yii::$app->security->validatePassword('piLoT1234!', $model->password));
       $I->assertEquals(1, $model->country_id);
       $I->assertEquals('Valladolid', $model->city);
       $I->assertEquals('1980-02-10', $model->date_of_birth);
       $I->assertEquals(10001, $model->vatsim_id);
       $I->assertEquals(20002, $model->ivao_id);
       $I->assertEquals(0, $model->hours_flown);
       $I->assertNotNull($model->auth_key);
       $I->assertNotNull($model->access_token);
       $I->assertNull($model->license);
       $I->assertEquals('LEMD', $model->location);
       $I->assertEquals(date('Y-m-d'), $model->registration_date);
    }

}