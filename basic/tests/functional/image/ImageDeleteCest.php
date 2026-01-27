<?php

namespace tests\functional\image;

use app\models\Image;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ConfigFixture;
use tests\fixtures\ImageFixture;

class ImageDeleteCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'config' => ConfigFixture::class,
            'image' => ImageFixture::class,
        ];
    }

    public function deleteOnlyPostAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('image/delete', ['id' => 1]);

        $I->seeResponseCodeIs(405);

        $count = Image::find()->count();
        $I->assertEquals(7, $count);
    }

    public function deleteOnlyPostAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('image/delete', ['id' => 1]);

        $I->seeResponseCodeIs(405);

        $count = Image::find()->count();
        $I->assertEquals(7, $count);
    }

    public function deleteOnlyPostAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('image/delete', ['id' => 1]);

        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function adminCanDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/image/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = Image::find()->where(['id' => 1])->count();
        $I->assertEquals(0, $count);
    }

    public function userCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->sendAjaxPostRequest('/image/delete?id=1');

        $I->seeResponseCodeIs(403);
        $count = Image::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }

    public function guestCannotDeleteViaPOST(\FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/image/delete?id=1');

        $I->seeResponseCodeIsRedirection();
        $count = Image::find()->where(['id' => 1])->count();
        $I->assertEquals(1, $count);
    }

    // Image id=1: type='rank_icon', filename='rank_icon.png'
    public function adminCanDeleteImageAndPhysicalFile(\FunctionalTester $I)
    {
        // Setup: create directory and test image file
        $imagePath = '/tmp/mam_test_images/rank_icon';
        if (!is_dir($imagePath)) {
            mkdir($imagePath, 0777, true);
        }
        $filePath = $imagePath . '/rank_icon.png';
        file_put_contents($filePath, 'fake image data');

        $I->assertTrue(file_exists($filePath));

        // Execute delete
        $I->amLoggedInAs(2);
        $I->sendAjaxPostRequest('/image/delete?id=1');

        // Verify image record is deleted
        $I->seeResponseCodeIsRedirection();
        $count = Image::find()->where(['id' => 1])->count();
        $I->assertEquals(0, $count);

        // Verify physical file was deleted
        $I->assertFalse(file_exists($filePath));
    }
}
