<?php

use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use yii\db\Migration;

/**
 * Adds CREDENTIAL_MANAGER and CREDENTIAL_AUTHORITY roles with their permissions.
 * Both roles are also assigned as children of the ADMIN role.
 */
class m260412_103338_add_credential_roles extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        // Permissions
        $credentialCrud = $auth->createPermission(Permissions::CREDENTIAL_CRUD);
        $credentialCrud->description = 'Can create, delete or modify credentials';
        $auth->add($credentialCrud);

        $issueCredential = $auth->createPermission(Permissions::ISSUE_CREDENTIAL);
        $issueCredential->description = 'Can issue or renew credentials to other users';
        $auth->add($issueCredential);

        // Roles
        $credentialManager = $auth->createRole(Roles::CREDENTIAL_MANAGER);
        $auth->add($credentialManager);
        $auth->addChild($credentialManager, $credentialCrud);

        $credentialAuthority = $auth->createRole(Roles::CREDENTIAL_AUTHORITY);
        $auth->add($credentialAuthority);
        $auth->addChild($credentialAuthority, $issueCredential);

        // Add both to admin
        $admin = $auth->getRole(Roles::ADMIN);
        $auth->addChild($admin, $credentialManager);
        $auth->addChild($admin, $credentialAuthority);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $auth->remove($auth->getRole(Roles::CREDENTIAL_AUTHORITY));
        $auth->remove($auth->getRole(Roles::CREDENTIAL_MANAGER));
        $auth->remove($auth->getPermission(Permissions::ISSUE_CREDENTIAL));
        $auth->remove($auth->getPermission(Permissions::CREDENTIAL_CRUD));
    }
}
