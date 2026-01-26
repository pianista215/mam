<?php

use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use app\rbac\rules\FlightDeletionRule;
use yii\db\Migration;

/**
 * Class m260126_000000_add_delete_own_flight
 */
class m260126_000000_add_delete_own_flight extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        // Add the flight deletion rule
        $flightDeletionRule = new FlightDeletionRule();
        $auth->add($flightDeletionRule);

        // Create the permission with the rule
        $deleteOwnFlight = $auth->createPermission(Permissions::DELETE_OWN_FLIGHT);
        $deleteOwnFlight->description = 'Delete own flight pending validation';
        $deleteOwnFlight->ruleName = $flightDeletionRule->name;
        $auth->add($deleteOwnFlight);

        // Assign the permission to the pilot role
        $pilot = $auth->getRole(Roles::PILOT);
        if ($pilot) {
            $auth->addChild($pilot, $deleteOwnFlight);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        // Remove the permission from pilot role
        $pilot = $auth->getRole(Roles::PILOT);
        $deleteOwnFlight = $auth->getPermission(Permissions::DELETE_OWN_FLIGHT);
        if ($pilot && $deleteOwnFlight) {
            $auth->removeChild($pilot, $deleteOwnFlight);
        }

        // Remove the permission
        if ($deleteOwnFlight) {
            $auth->remove($deleteOwnFlight);
        }

        // Remove the rule
        $rule = $auth->getRule('flightDeletionRule');
        if ($rule) {
            $auth->remove($rule);
        }
    }
}
