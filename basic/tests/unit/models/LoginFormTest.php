<?php

namespace tests\unit\models;

use app\models\Airport;
use app\models\Country;
use app\models\LoginForm;
use app\models\Pilot;
use tests\unit\DbTestCase;
use Yii;

class LoginFormTest extends DbTestCase
{
    private $model;

    protected function _before()
    {
        parent::_before();

        $country = new Country(['id' => 1, 'name' => 'Spain', 'iso2_code' => 'ES']);
        $country->save(false);

        $airport = new Airport(
            [
                'id' => 1,
                'icao_code' => 'LEVD',
                'name' => 'Villanubla',
                'latitude' => 0.0,
                'longitude' => 0.0,
                'city' => 'Valladolid',
                'country_id' => 1
            ]
        );
        $airport->save(false);

        $pilot = new Pilot([
            'license' => 'ABC12345',
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'country_id' => 1,
            'city' => 'New York',
            'location' => 'LEVD',
            'password' => Yii::$app->security->generatePasswordHash('SecurePass123!'),
            'date_of_birth' => '1990-01-01',
        ]);
        $pilot->save(false);
    }

    protected function _after()
    {
        \Yii::$app->user->logout();
        parent::_after();
    }

    public function testLoginNoUser()
    {
        $this->model = new LoginForm([
            'username' => 'NOTEXISTING',
            'password' => 'not_existing_password',
        ]);

        verify($this->model->login())->false();
        verify(\Yii::$app->user->isGuest)->true();
    }

    public function testLoginWrongPassword()
    {
        $this->model = new LoginForm([
            'username' => 'ABC12345',
            'password' => 'wrong_password',
        ]);

        verify($this->model->login())->false();
        verify(\Yii::$app->user->isGuest)->true();
        verify($this->model->errors)->arrayHasKey('password');
    }

    public function testLoginCorrect()
    {
        $this->model = new LoginForm([
            'username' => 'ABC12345',
            'password' => 'SecurePass123!',
        ]);

        verify($this->model->login())->true();
        verify(\Yii::$app->user->isGuest)->false();
        verify($this->model->errors)->arrayHasNotKey('password');
    }
}

