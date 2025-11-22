<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class ImageFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Image';
    public $depends = [
        'tests\fixtures\AircraftTypeFixture',
        'tests\fixtures\PageContentFixture',
        'tests\fixtures\PilotFixture',
        'tests\fixtures\TourStageFixture',
    ];
}