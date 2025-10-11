<?php

namespace tests\functional\pilot;

use app\models\Pilot;
use tests\fixtures\PilotFixture;
use Yii;

class PilotChangePasswordCest
{

    public function _fixtures(){
        return [
            'pilot' => PilotFixture::class
        ];
    }

    public function requestPasswordResetSuccess(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot/forgot-password');
        $I->submitForm('form', [
            'ForgotPasswordForm[email]' => 'id4@example.com',
        ]);

        $I->see('If there is an account associated with');
        $pilot = Pilot::findOne(4);
        $I->assertNotEmpty($pilot->pwd_reset_token);
        $I->assertNotEmpty($pilot->pwd_reset_token_created_at);
    }

    public function requestPasswordResetTooSoon(\FunctionalTester $I)
    {
        $pilot = Pilot::findOne(4);
        $pilot->pwd_reset_token_created_at = date('Y-m-d H:i:s');
        $pilot->pwd_reset_token = 'EXISTINGTOKEN';
        $pilot->save(false);

        $I->amOnRoute('pilot/forgot-password');
        $I->submitForm('form', [
            'ForgotPasswordForm[email]' => 'id4@example.com',
        ]);

        $I->see('If there is an account associated with');

        $pilot = Pilot::findOne(4);
        $I->assertEquals('EXISTINGTOKEN', $pilot->pwd_reset_token);
    }

    public function requestPasswordResetInvalidEmail(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot/forgot-password');
        $I->submitForm('form', [
            'ForgotPasswordForm[email]' => 'nonexistent@example.com',
        ]);

        $I->see('If there is an account associated with');
    }

    public function changePasswordOk(\FunctionalTester $I)
    {
        $pilot = Pilot::findOne(4);
        $pilot->pwd_reset_token = 'VALIDTOKEN';
        $pilot->pwd_reset_token_created_at = date('Y-m-d H:i:s', time() - 60);
        $oldHash = $pilot->password;
        $pilot->save(false);

        $I->amOnRoute('pilot/change-password', ['id' => 4, 'token' => 'VALIDTOKEN']);
        $I->see('Please enter your new password:');

        $I->submitForm('form', [
            'ChangePasswordForm[password]' => 'NewPassword123',
        ]);

        $I->see('Password successfully updated.');

        $pilot = Pilot::findOne(4);
        $I->assertNull($pilot->pwd_reset_token);
        $I->assertNull($pilot->pwd_reset_token_created_at);
        $I->assertNotEquals($oldHash, $pilot->password);
        $I->assertTrue(Yii::$app->security->validatePassword('NewPassword123', $pilot->password));
    }

    public function changePasswordInvalidToken(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot/change-password', ['id' => 4, 'token' => 'WRONGTOKEN']);
        $I->see('The password reset link is invalid or has expired.');
    }

    public function changePasswordExpiredToken(\FunctionalTester $I)
    {
        $pilot = Pilot::findOne(4);
        $pilot->pwd_reset_token = 'EXPIREDTOKEN';
        $pilot->pwd_reset_token_created_at = date('Y-m-d H:i:s', strtotime('-25 hours'));
        $pilot->save(false);

        $I->amOnRoute('pilot/change-password', ['id' => 4, 'token' => 'EXPIREDTOKEN']);
        $I->see('The password reset link is invalid or has expired.');
    }

    public function changePasswordWeakPassword(\FunctionalTester $I)
    {
        $pilot = Pilot::findOne(4);
        $pilot->pwd_reset_token = 'WEAKTOKEN';
        $pilot->pwd_reset_token_created_at = date('Y-m-d H:i:s', time() - 60);
        $pilot->save(false);

        $I->amOnRoute('pilot/change-password', ['id' => 4, 'token' => 'WEAKTOKEN']);
        $I->submitForm('form', [
            'ChangePasswordForm[password]' => '123',
        ]);

        $I->see('Password should contain at least 8 characters.');
    }

    public function changePasswordReuseToken(\FunctionalTester $I)
    {
        $pilot = Pilot::findOne(4);
        $pilot->pwd_reset_token = 'REUSETOKEN';
        $pilot->pwd_reset_token_created_at = date('Y-m-d H:i:s', time() - 60);
        $pilot->save(false);

        // First use
        $I->amOnRoute('pilot/change-password', ['id' => 4, 'token' => 'REUSETOKEN']);
        $I->submitForm('form', [
            'ChangePasswordForm[password]' => 'ValidPass123',
        ]);
        $I->see('Password successfully updated.');

        // Second use
        $I->amOnRoute('pilot/change-password', ['id' => 4, 'token' => 'REUSETOKEN']);
        $I->see('The password reset link is invalid or has expired.');
    }


}