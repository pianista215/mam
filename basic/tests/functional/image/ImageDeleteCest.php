<?php

namespace tests\functional\image;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ImageFixture;
use Yii;

class ImageDeleteCest
{
    // TODO: Acceptance tests for post suport

    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'image' => ImageFixture::class,
        ];
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('image/delete', ['id' => 1]);

        $I->seeResponseCodeIs(405);

        $count = \app\models\Image::find()->count();
        $I->assertEquals(7, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('image/delete', ['id' => 1]);

        $I->seeResponseCodeIs(405);

        $count = \app\models\Image::find()->count();
        $I->assertEquals(7, $count);
    }

    public function deleteOnlyPostAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('image/delete', ['id' => 1]);

        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }
}
