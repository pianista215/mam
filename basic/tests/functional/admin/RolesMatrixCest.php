<?php

namespace tests\functional\admin;

use tests\fixtures\AuthAssignmentFixture;

class RolesMatrixCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
        ];
    }

    private function checkMatrixCommon(\FunctionalTester $I)
    {
        $I->amOnRoute('admin/roles-matrix');
        $I->see('Role assignment');
        $I->see('Admin Admin');
        $I->see('Tour Mgr');
        $I->see('Vfr School');
        $I->see('Vfr Validator');
    }

    public function openRolesMatrixAsGuest(\FunctionalTester $I)
    {
        $I->amOnRoute('admin/roles-matrix');
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openRolesMatrixAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('admin/roles-matrix');
        $I->seeResponseCodeIs(403);
        $I->dontSee('Role assignment');
        $I->dontSee('Admin Admin');
        $I->dontSee('Tour Mgr');
    }

    public function openRolesMatrixAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $this->checkMatrixCommon($I);
        $I->seeElement('span', [
            'title' => 'Only superadmins can edit admin users'
        ]);
    }

    public function openRolesMatrixAsSuperAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(13);
        $this->checkMatrixCommon($I);
        $I->dontSeeElement('span', [
            'title' => 'Only superadmins can edit admin users'
        ]);
    }
}
