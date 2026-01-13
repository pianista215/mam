<?php

namespace tests\functional\admin;

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
        $I->see('You don&apos;t have permission to modify roles.');
    }

    public function editRolesAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('admin/edit-roles', ['id' => 1]);

        $I->seeResponseCodeIs(200);
        $I->see('Edit user roles');
        $I->seeElement('form#edit-roles-form');

        // Admin cannot assign admin role
        $I->dontSee('admin', 'label');
    }

    public function assignAdminRoleAsAdminForbidden(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('admin/edit-roles', ['id' => 1]);

        $I->checkOption('input[value="admin"]');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(403);
        $I->see('You are not allowed to assign admin role.');
    }

    public function editRolesAsSuperAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(13);
        $I->amOnRoute('admin/edit-roles', ['id' => 1]);

        $I->seeResponseCodeIs(200);
        $I->see('Edit user roles');
        $I->see('admin', 'label');
    }

    public function assignAdminRoleAsSuperAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(13);
        $I->amOnRoute('admin/edit-roles', ['id' => 1]);

        $I->checkOption('input[value="admin"]');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Roles updated successfully.');

        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(1);
        $I->assertArrayHasKey('admin', $roles);
    }

    public function revokeAdminRoleAsSuperAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(13);
        $I->amOnRoute('admin/edit-roles', ['id' => 2]); // admin user

        $I->uncheckOption('input[value="admin"]');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Roles updated successfully.');

        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(2);
        $I->assertArrayNotHasKey('admin', $roles);
    }
}
