<?php

namespace tests\functional\admin;

use app\rbac\constants\Roles;
use tests\fixtures\AuthAssignmentFixture;
use Yii;

class EditRolesCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
        ];
    }

    public function editRolesAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('admin/edit-roles', ['id' => 1]);
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function editRolesAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('admin/edit-roles', ['id' => 1]);
        $I->seeResponseCodeIs(403);
        $I->dontSee('Roles of user: John Doe');
        $I->dontSeeElement('form#roles-form');
    }

    public function editRolesAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('admin/edit-roles', ['id' => 1]);

        $I->seeResponseCodeIs(200);
        $I->see('Roles of user: John Doe');
        $I->seeElement('form#roles-form');
        $I->dontSee('admin', 'label');

        $I->checkOption('input[value="ifrValidator"]');
        $I->click('Save', 'button');
        $I->seeResponseCodeIs(200);

        $I->see('Roles updated for user: John Doe');

        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(1);
        $I->assertArrayHasKey(Roles::IFR_VALIDATOR, $roles);
    }

    public function editRolesOfSuperAdminAsAdminForbidden(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('admin/edit-roles', ['id' => 13]);

        $I->seeResponseCodeIs(403);
        $I->see('You are not allowed to change the roles of an admin user.');
        $I->dontSeeElement('form#roles-form');
    }

    public function editRolesAsSuperAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(13);
        $I->amOnRoute('admin/edit-roles', ['id' => 1]);

        $I->seeResponseCodeIs(200);
        $I->see('Roles of user: John Doe');
        $I->seeElement('form#roles-form');
        $I->see('admin', 'label');
    }

    public function assignAdminRoleAsAdminForbidden(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('admin/edit-roles', ['id' => 1]);
        $I->dontSeeElement('input[value="admin"]');

        $I->submitForm('form#roles-form', [
            'AssignRolesForm[roles][]' => 'admin',
        ]);

        $I->seeResponseCodeIs(403);
        $I->see('You are not allowed to assign the admin role.');
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(1);
        $I->assertArrayNotHasKey(Roles::ADMIN, $roles);
    }

    public function assignAdminRoleAsSuperAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(13);
        $I->amOnRoute('admin/edit-roles', ['id' => 1]);

        $I->checkOption('input[value="admin"]');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Roles updated for user: John Doe');

        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(1);
        $I->assertArrayHasKey(Roles::ADMIN, $roles);
    }

    public function revokeAdminRoleAsSuperAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(13);
        $I->amOnRoute('admin/edit-roles', ['id' => 2]);

        $I->uncheckOption('input[value="admin"]');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Roles updated for user: Admin Admin');

        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(2);
        $I->assertArrayNotHasKey(Roles::ADMIN, $roles);
    }
}
