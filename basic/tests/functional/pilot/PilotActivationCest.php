<?php

namespace tests\functional\pilot;

use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\ConfigFixture;
use Yii;

class PilotActivationCest
{

    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'config' => ConfigFixture::class,
        ];
    }

    public function openActivatePilotsAsVisitor(\FunctionalTester $I){
        $I->amOnRoute('pilot/activate-pilots');
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function openActivatePilotsAsUser(\FunctionalTester $I){
        $I->amLoggedInAs(1);
        $I->amOnRoute('pilot/activate-pilots');
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Activate pilots');
        $I->dontSee('nonactivated');
    }

    public function openActivatePilotsAsAdmin(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot/activate-pilots');
        $I->seeResponseCodeIs(200);

        $I->see('Activate pilots');
        $I->see('nonactivated');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Delete']);
    }

    public function activatePilotEmptyFormAsAdmin(\FunctionalTester $I){
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot/activate-pilots');
        $I->seeResponseCodeIs(200);

        $I->see('Activate pilots');
        $I->see('nonactivated');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Delete']);
        $I->click('View');

        $I->see('Activate', 'a');
        $I->see('Delete', 'a');
        $I->click('Activate');
        $I->seeResponseCodeIs(200);

        $I->see('Activating Pilot: nonactivated nonactivated');
        $I->see('Activate Pilot', 'button');
        $I->click('Activate Pilot');

        $I->expectTo('see validations errors');
        $I->see('License cannot be blank.');

        $count = \app\models\Pilot::find()->count();
        $I->assertEquals(13, $count);
    }

    public function activatePilotAsAdmin(\FunctionalTester $I){
        $auth = Yii::$app->authManager;
        $start = $auth->getRolesByUser(3);
        $I->assertEmpty($start);

        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot/activate-pilots');
        $I->seeResponseCodeIs(200);

        $I->see('Activate pilots');
        $I->see('nonactivated');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Delete']);
        $I->click('View');

        $I->see('Activate', 'a');
        $I->see('Delete', 'a');
        $I->click('Activate');
        $I->seeResponseCodeIs(200);

        $I->see('Activating Pilot: nonactivated nonactivated');
        $I->see('Activate Pilot', 'button');
        $I->fillField('#pilot-license', 'AB4567');
        $I->click('Activate Pilot');

        $I->seeResponseCodeIs(200);
        $I->see('nonactivated');
        $I->see('AB4567');

        $count = \app\models\Pilot::find()->where(['license' => 'AB4567'])->count();
        $I->assertEquals(1, $count);

        $newRoles = $auth->getRolesByUser(3);
        $I->assertCount(1, $newRoles);
        $I->assertArrayHasKey('pilot', $newRoles);
    }

    public function activatePilotWhitespaceLowercaseAsAdmin(\FunctionalTester $I){
        $auth = Yii::$app->authManager;
        $start = $auth->getRolesByUser(3);
        $I->assertEmpty($start);

        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot/activate-pilots');
        $I->seeResponseCodeIs(200);

        $I->see('Activate pilots');
        $I->see('nonactivated');
        $I->seeElement('a', ['title' => 'View']);
        $I->seeElement('a', ['title' => 'Delete']);
        $I->click('View');

        $I->see('Activate', 'a');
        $I->see('Delete', 'a');
        $I->click('Activate');
        $I->seeResponseCodeIs(200);

        $I->see('Activating Pilot: nonactivated nonactivated');
        $I->see('Activate Pilot', 'button');
        $I->fillField('#pilot-license', 'ab 4567');
        $I->click('Activate Pilot');

        $I->seeResponseCodeIs(200);
        $I->see('nonactivated');
        $I->see('AB4567');

        $count = \app\models\Pilot::find()->where(['license' => 'AB4567'])->count();
        $I->assertEquals(1, $count);

        $newRoles = $auth->getRolesByUser(3);
        $I->assertCount(1, $newRoles);
        $I->assertArrayHasKey('pilot', $newRoles);
    }
}