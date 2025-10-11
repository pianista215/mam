<?php

namespace tests\unit\models;

use app\models\ChangePasswordForm;
use tests\unit\BaseUnitTest;
use Yii;

class ChangePasswordFormTest extends BaseUnitTest
{

    public function testPasswordValidation()
    {
        $form = new ChangePasswordForm();

        $form->password = 'short';
        $this->assertFalse($form->validate(), 'Password should fail because it is less than 8 characters.');

        $form->password = 'password123';
        $this->assertTrue($form->validate(), 'Password should pass because it has more than 8 characters and includes both letters and numbers.'. json_encode($form->getErrors()));

        $form->password = 'password';
        $this->assertFalse($form->validate(), 'Password should fail because it does not contain a number.');

        $form->password = '12345678';
        $this->assertFalse($form->validate(), 'Password should fail because it does not contain a letter.');
    }

}