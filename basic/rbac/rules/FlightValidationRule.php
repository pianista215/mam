<?php

namespace app\rbac\rules;

use app\models\Flight;
use yii\rbac\Rule;
use Yii;

class FlightValidationRule extends Rule
{
    public $name = 'flightValidationRule';

    public function execute($user, $item, $params)
    {
        $flight = $params['flight'] ?? null;
        if (!$flight) {
            return false;
        }

        $currentUserId = Yii::$app->user->id;

        if ((int) $flight->pilot_id === (int) $currentUserId) {
            return false;
        }

        if ($flight->flight_rules === 'V') {
            return Yii::$app->user->can('validateVfrFlight');
        } else {
            return Yii::$app->user->can('validateIfrFlight');
        }

    }
}
