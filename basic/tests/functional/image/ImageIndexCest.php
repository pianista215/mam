<?php

namespace tests\functional\image;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ImageFixture;
use Yii;

class ImageIndexCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'image' => ImageFixture::class,
        ];
    }

    public function openImageIndexAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('image/index');

        $I->seeResponseCodeIs(200);

        $I->see('Images');

        $I->seeElement('select[name="ImageSearch[type]"]');
        $I->seeOptionIsSelected('select[name="ImageSearch[type]"]', '-- All types --');

        $types = ['rank_icon', 'pilot_profile', 'tour_image', 'country_icon', 'aircraftType_image', 'page_image'];
        foreach ($types as $type) {
            $I->seeElement('select[name="ImageSearch[type]"] option', ['value' => $type]);
        }

        foreach (range(1, 7) as $id) {
            $I->see((string)$id, 'td'); // id
        }

        $I->see('Replace');
        $I->see('Delete');
    }

    public function openImageIndexAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnRoute('image/index');

        $I->seeResponseCodeIs(403);
        $I->see('Forbidden');
    }

    public function openImageIndexAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('image/index');

        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }
}
