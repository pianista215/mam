<?php

use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use app\rbac\rules\AircraftTypeResourceAccessRule;
use yii\db\Migration;

/**
 * Adds AIRCRAFT_TYPE_RESOURCE_MANAGER role with its permissions.
 * Pilots inherit ACCESS_AIRCRAFT_TYPE_RESOURCES (with rule).
 * Admin inherits AIRCRAFT_TYPE_RESOURCE_MANAGER.
 */
class m260501_111316_add_aircraft_type_resource_manager_role extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $rule = new AircraftTypeResourceAccessRule();
        $auth->add($rule);

        $accessResources = $auth->createPermission(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES);
        $accessResources->description = 'Can view and download aircraft type resources if credentialed';
        $accessResources->ruleName = $rule->name;
        $auth->add($accessResources);

        $resourceCrud = $auth->createPermission(Permissions::AIRCRAFT_TYPE_RESOURCE_CRUD);
        $resourceCrud->description = 'Can upload and delete aircraft type resources';
        $auth->add($resourceCrud);

        $resourceManager = $auth->createRole(Roles::AIRCRAFT_TYPE_RESOURCE_MANAGER);
        $auth->add($resourceManager);
        $auth->addChild($resourceManager, $accessResources);
        $auth->addChild($resourceManager, $resourceCrud);

        $pilot = $auth->getRole(Roles::PILOT);
        $auth->addChild($pilot, $accessResources);

        $admin = $auth->getRole(Roles::ADMIN);
        $auth->addChild($admin, $resourceManager);
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $pilot = $auth->getRole(Roles::PILOT);
        $auth->removeChild($pilot, $auth->getPermission(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES));

        $auth->remove($auth->getRole(Roles::AIRCRAFT_TYPE_RESOURCE_MANAGER));
        $auth->remove($auth->getPermission(Permissions::AIRCRAFT_TYPE_RESOURCE_CRUD));
        $auth->remove($auth->getPermission(Permissions::ACCESS_AIRCRAFT_TYPE_RESOURCES));
        $auth->remove($auth->getRule('aircraftTypeResourceAccessRule'));
    }
}
