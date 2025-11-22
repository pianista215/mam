<?php

namespace tests\functional\image;

use app\config\Config;
use app\models\Image;
use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\PageContentFixture;
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
}
