<?php

use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use app\rbac\rules\FlightKmlDownloadRule;
use yii\db\Migration;

/**
 * Adds DOWNLOAD_FLIGHT_KML (with FlightKmlDownloadRule): the flight pilot and the validators of
 * its flight rules can download the black box KML. Granted to pilots and to both validator
 * roles (admin inherits them).
 */
class m261116_191930_add_download_flight_kml_permission extends Migration
{
    private const ROLES = [Roles::PILOT, Roles::VFR_VALIDATOR, Roles::IFR_VALIDATOR];

    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $rule = new FlightKmlDownloadRule();
        $auth->add($rule);

        $permission = $auth->createPermission(Permissions::DOWNLOAD_FLIGHT_KML);
        $permission->description = 'Download the black box KML of own flights or flights the user can validate';
        $permission->ruleName = $rule->name;
        $auth->add($permission);

        foreach (self::ROLES as $roleName) {
            $auth->addChild($auth->getRole($roleName), $permission);
        }
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $permission = $auth->getPermission(Permissions::DOWNLOAD_FLIGHT_KML);
        foreach (self::ROLES as $roleName) {
            $auth->removeChild($auth->getRole($roleName), $permission);
        }
        $auth->remove($permission);
        $auth->remove($auth->getRule('flightKmlDownloadRule'));
    }
}
