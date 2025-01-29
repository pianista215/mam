<?php

namespace tests\api\v1\auth;

use app\modules\api\dto\v1\response\TokenInfoDTO;
use tests\fixtures\AuthAssignmentFixture;
use \ApiTester;

class AuthCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class
        ];
    }

    public function testLoginFailureIfEmpty(ApiTester $I)
    {
        $I->sendPOST('/auth/login', [
            'license' => '',
            'password' => ''
        ]);
        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson(['message' => 'License and password are required.']);
    }

    private function checkInvalidUsernameOrPassword(ApiTester $I)
    {
        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson(['message' => 'Invalid username or password.']);
    }

    public function testLoginFailureIfNonExistingLicense(ApiTester $I)
    {
        $I->sendPOST('/auth/login', [
            'license' => 'non_existing_license',
            'password' => 'somepassword'
        ]);
        $this->checkInvalidUsernameOrPassword($I);
    }

    public function testLoginFailureIfPasswordMismatch(ApiTester $I)
    {
        $I->sendPOST('/auth/login', [
            'license' => 'ADM123',
            'password' => 'somepassword'
        ]);
        $this->checkInvalidUsernameOrPassword($I);
    }

    private function checkLoginTokenChanges($license, $password, $I)
    {
        $oldToken = \app\models\Pilot::find()->where(['license' => $license])->one()->access_token;

        $I->sendPOST('/auth/login', [
            'license' => $license,
            'password' => $password,
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $newToken = \app\models\Pilot::find()->where(['license' => $license])->one()->access_token;

        $expectedDTO = new TokenInfoDTO();
        $expectedDTO->access_token = $newToken;

        $I->seeResponseContainsJson($expectedDTO->toArray());

        $I->assertNotEquals($oldToken, $newToken);
    }

    public function testLoginSuccess(ApiTester $I)
    {
        $this->checkLoginTokenChanges('ADM123', 'admin1234!', $I);
        $this->checkLoginTokenChanges('adm123', 'admin1234!', $I);
        $this->checkLoginTokenChanges('AB2345', 'otherid4!', $I);
        $this->checkLoginTokenChanges('Z1234', 'otherid6!', $I);
    }
}
