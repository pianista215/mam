<?php

namespace app\rbac\rules;

use Yii;
use yii\rbac\Rule;

/**
 * Checks if the flight plan has been submitted by the pilot
 */
class SubmittedFlightPlanOwnerRule extends Rule
{
    public $name = 'isFlightPlanOwner';

    /**
     * Executes the rule logic.
     *
     * @param string|int $userId ID of the user.
     * @param Item $item The role or permission associated.
     * @param array $params Additional parameters.
     * @return bool Whether the user has permission or not.
     */
    public function execute($userId, $item, $params)
    {
        if (isset($params['submittedFlightPlan'])) {
            return $params['submittedFlightPlan']->pilot_id == $userId;
        }
        return false;
    }
}