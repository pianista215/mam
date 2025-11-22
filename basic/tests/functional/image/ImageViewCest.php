<?php

namespace tests\functional\image;

use app\config\Config;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ImageFixture;
use Yii;

class ImageViewCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'image' => ImageFixture::class,
        ];
    }

    public function _before(\FunctionalTester $I)
    {
        // Configura la ruta base de las imágenes
        Config::set('images_storage_path', '/tmp/mam_test_images');

        $types = ['rank_icon', 'pilot_profile', 'tour_image', 'country_icon', 'aircraftType_image', 'page_image'];

        foreach ($types as $type) {
            $dir = '/tmp/mam_test_images/' . $type;
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        $placeholders = [
            'rank_icon' => '@app/web/images/placeholders/rank_icon.png',
            'pilot_profile' => '@app/web/images/placeholders/pilot_profile.png',
            'tour_image' => '@app/web/images/placeholders/tour_image.png',
            'country_icon' => '@app/web/images/placeholders/country_icon.png',
            'aircraftType_image' => '@app/web/images/placeholders/aircraftType_image.png',
        ];

        foreach ($placeholders as $type => $sourcePath) {
            $dir = '/tmp/mam_test_images/' . $type;
            $targetFile = $dir . '/' . basename($sourcePath);
            copy(Yii::getAlias($sourcePath), $targetFile);
        }

        $page_images = [
            'page_image_1' => '@app/web/images/placeholders/rank_icon.png',
            'page_image_2' => '@app/web/images/placeholders/rank_icon.png',
        ];

        foreach ($page_images as $image => $sourcePath) {
            $dir = '/tmp/mam_test_images/page_image';
            $targetFile = $dir . '/' . $image . '.png';
            copy(Yii::getAlias($sourcePath), $targetFile);
        }
    }


    public function viewImagesAsGuest(\FunctionalTester $I)
    {
        $restricted = [
            ['pilot_profile', 2, 0],
            ['tour_image', 2, 0],
            ['page_image', 7, 0], // private
        ];

        foreach ($restricted as [$type, $related_id, $element]) {
            $I->amOnRoute('image/view', ['type' => $type, 'related_id' => $related_id, 'element' => $element]);
            $I->seeResponseCodeIs(403); // Forbidden
        }

        $allowed = [
            ['rank_icon', 1, 0],
            ['country_icon', 1, 0],
            ['aircraftType_image', 1, 0],
            ['page_image', 6, 0], // public
            ['aircraftType_image', 2, 0] // must return placeholder
        ];

        foreach ($allowed as [$type, $related_id, $element]) {
            $I->amOnRoute('image/view', ['type' => $type, 'related_id' => $related_id, 'element' => $element]);
            $I->seeResponseCodeIs(200);
        }
    }

    public function viewImagesAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $types = [
            ['rank_icon', 1, 0],
            ['pilot_profile', 2, 0],
            ['tour_image', 2, 0],
            ['country_icon', 1, 0],
            ['aircraftType_image', 1, 0],
            ['page_image', 7, 0], // private
            ['page_image', 6, 0], // public
            ['aircraftType_image', 2, 0] // must return placeholder
        ];

        foreach ($types as [$type, $related_id, $element]) {
            $I->amOnRoute('image/view', ['type' => $type, 'related_id' => $related_id, 'element' => $element]);
            $I->seeResponseCodeIs(200);
        }
    }

}
