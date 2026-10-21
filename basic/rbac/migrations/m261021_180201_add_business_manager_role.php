<?php

use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use yii\db\Migration;

/**
 * Adds BUSINESS_MANAGER role with CLIENT_CRUD permission.
 * Admin inherits BUSINESS_MANAGER.
 */
class m261021_180201_add_business_manager_role extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $clientCrud = $auth->createPermission(Permissions::CLIENT_CRUD);
        $clientCrud->description = 'Can create, update and delete clients';
        $auth->add($clientCrud);

        $businessManager = $auth->createRole(Roles::BUSINESS_MANAGER);
        $auth->add($businessManager);
        $auth->addChild($businessManager, $clientCrud);

        $admin = $auth->getRole(Roles::ADMIN);
        $auth->addChild($admin, $businessManager);
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $auth->remove($auth->getRole(Roles::BUSINESS_MANAGER));
        $auth->remove($auth->getPermission(Permissions::CLIENT_CRUD));
    }
}
