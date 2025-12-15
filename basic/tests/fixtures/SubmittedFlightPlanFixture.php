<?php

namespace tests\fixtures;

use yii\test\ActiveFixture;

class SubmittedFlightPlanFixture extends ActiveFixture
{
    public $modelClass = 'app\models\SubmittedFlightPlan';
    public $depends = [
                        'tests\fixtures\AircraftFixture',
                        'tests\fixtures\CharterRouteFixture',
                        'tests\fixtures\RouteFixture',
                        'tests\fixtures\TourStageFixture',
                        ];
}