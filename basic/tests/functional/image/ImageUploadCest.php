<?php

namespace tests\functional\image;

use app\config\Config;
use app\models\Image;
use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\PageContentFixture;
use tests\fixtures\PageFixture;
use tests\fixtures\PilotFixture;
use tests\fixtures\TourStageFixture;
use Yii;

class ImageUploadCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'pilot'          => PilotFixture::class,
            'aircraftType'   => AircraftTypeFixture::class,
            'tourStage'      => TourStageFixture::class,
            'page'           => PageFixture::class,
            'pageContent'    => PageContentFixture::class,
        ];
    }

    // TODO: Acceptance test with javascript and crop

    public function guestCannotAccessUpload(\FunctionalTester $I)
    {
        $I->amOnRoute('image/upload', [
            'type' => 'pilot_profile',
            'related_id' => 1
        ]);

        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function userCannotAccessCountryIconUpload(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('image/upload', [
            'type' => 'country_icon',
            'related_id' => 1
        ]);

        $I->seeResponseCodeIs(403);
        $I->see('Forbidden');
    }


    public function adminCanAccessCountryIconUpload(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('image/upload', [
            'type' => 'country_icon',
            'related_id' => 1
        ]);

        $I->see('Uploading image for country');
        $I->see('Upload Image', 'button');
    }

    public function pilotCanUploadOwnProfileImage(\FunctionalTester $I)
    {
        $pilotId = 5;

        $I->amLoggedInAs($pilotId);
        $I->amOnRoute('image/upload', [
            'type' => 'pilot_profile',
            'related_id' => $pilotId
        ]);

        $I->see('Uploading image for pilot');
        $I->see('Upload Image', 'button');
    }

    public function unauthorizedPilotCannotUploadAnotherProfile(\FunctionalTester $I)
    {
        $I->amLoggedInAs(4);
        $I->amOnRoute('image/upload', [
            'type' => 'pilot_profile',
            'related_id' => 1
        ]);

        $I->seeResponseCodeIs(403);
    }

    public function inactivatedPilotCannotUploadImage(\FunctionalTester $I)
    {
        $I->amLoggedInAs(3);
        $I->amOnRoute('image/upload', [
            'type' => 'pilot_profile',
            'related_id' => 3
        ]);

        $I->seeResponseCodeIs(403);
    }

    public function adminCanUploadAircraftTypeImage(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('image/upload', [
            'type' => 'aircraftType_image',
            'related_id' => 1
        ]);

        $I->see('Uploading image for aircraft type');
        $I->see('Upload Image', 'button');
    }

    public function tourManagerCanUploadTourPageImage(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10); // Tour Manager
        $I->amOnRoute('image/upload', [
            'type' => Image::TYPE_PAGE_IMAGE,
            'related_id' => 7, // Tour page
            'element' => 0,
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Uploading image for page:');
        $I->see('Upload Image', 'button');
    }

    public function tourManagerCannotUploadSitePageImage(\FunctionalTester $I)
    {
        $I->amLoggedInAs(10); // Tour Manager
        $I->amOnRoute('image/upload', [
            'type' => Image::TYPE_PAGE_IMAGE,
            'related_id' => 2, // Site page
            'element' => 0,
        ]);

        $I->seeResponseCodeIs(403);
    }

    public function regularUserCannotUploadTourPageImage(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1); // Regular pilot
        $I->amOnRoute('image/upload', [
            'type' => Image::TYPE_PAGE_IMAGE,
            'related_id' => 7, // Tour page
            'element' => 0,
        ]);

        $I->seeResponseCodeIs(403);
    }

    public function adminCanUploadSitePageImage(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2); // Admin
        $I->amOnRoute('image/upload', [
            'type' => Image::TYPE_PAGE_IMAGE,
            'related_id' => 2, // Site page
            'element' => 0,
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Uploading image for page:');
        $I->see('Upload Image', 'button');
    }
}
