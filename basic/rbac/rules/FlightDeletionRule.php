<?php

namespace app\rbac\rules;

use yii\rbac\Rule;

class FlightDeletionRule extends Rule
{
    public $name = 'flightDeletionRule';

    public function execute($user, $item, $params)
    {
        $flight = $params['flight'] ?? null;
        if (!$flight) {
            return false;
        }

        return (int) $flight->pilot_id === (int) $user && $flight->isPendingValidation();
    }
}
