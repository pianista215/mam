<?php

namespace tests\functional\pilot;

use tests\fixtures\AuthAssignmentFixture;
use Yii;

class PilotUpdateCest
{
    public function _fixtures(){
        return [
            'authAssignment' => AuthAssignmentFixture::class,
        ];
    }

    public function openPilotUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('pilot/update', [ 'id' => '1' ]);

        $I->see('Update Pilot: John');
        $I->see('Save', 'button');
    }

    public function openPilotUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('pilot/update', [ 'id' => '1' ]);
        $I->seeResponseCodeIs(403);

        $I->see('Forbidden');
        $I->dontSee('Update Pilot: John');
        $I->dontSee('Save', 'button');
    }

    public function openPilotUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('pilot/update', [ 'id' => '1' ]);
        // Check redirect
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function updateEmptyPilot(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('pilot/update', [ 'id' => '1' ]);

       $I->fillField('#pilot-name','');
       $I->fillField('#pilot-surname','');
       $I->fillField('#pilot-email','');
       $I->selectOption('form select[name="Pilot[country_id]"]', 'Select Country');
       $I->fillField('#pilot-city','');
       $I->fillField('#pilot-date_of_birth','');
       $I->fillField('#pilot-location','');
       $I->click('Save');

       $I->expectTo('see validations errors');
       $I->see('Name cannot be blank.');
       $I->see('Surname cannot be blank.');
       $I->see('Email cannot be blank.');
       $I->see('Country cannot be blank.');
       $I->see('City cannot be blank.');
       $I->see('Date Of Birth cannot be blank.');
       $I->see('Location cannot be blank.');

       $count = \app\models\Pilot::find()->count();
       $I->assertEquals(10, $count);
    }

    public function updateValidPilot(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('pilot/update', [ 'id' => '1' ]);

       $I->fillField('#pilot-name','Other');
       $I->fillField('#pilot-surname','Name');
       $I->fillField('#pilot-email','name@test.com');
       $I->selectOption('form select[name="Pilot[country_id]"]', 'Spain');
       $I->fillField('#pilot-city','Other');
       $I->fillField('#pilot-date_of_birth','1988-12-11');
       $I->fillField('#pilot-location','LEMD');
       $I->click('Save');

       $I->seeResponseCodeIs(200);
       $I->see('AB1234');
       $I->see('Other Name');
       $I->see('Rank 1');
       $I->see('LEMD');

       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Pilot::find()->where(['id' => 1])->one();
       $I->assertNotNull($model);
       $I->assertEquals('Other', $model->name);
       $I->assertEquals('Name', $model->surname);
       $I->assertEquals('name@test.com', $model->email);
       $I->assertEquals('Other', $model->city);
       $I->assertEquals('1988-12-11', $model->date_of_birth);
       $I->assertEquals('LEMD', $model->location);
       $I->assertEquals('2020-02-02', $model->registration_date);
       $I->assertEquals(1, $model->id);
       $I->assertEquals(1, $model->rank_id);
       $I->assertEquals(10.5, $model->hours_flown);


       $count = \app\models\Pilot::find()->count();
       $I->assertEquals(10, $count);
    }

    public function updatePilotRange(\FunctionalTester $I)
    {
       $I->amLoggedInAs(2);
       $I->amOnRoute('pilot/update', [ 'id' => '1' ]);

       $I->selectOption('form select[name="Pilot[rank_id]"]', 'Rank 3');
       $I->click('Save');

       $I->seeResponseCodeIs(200);
       $I->see('AB1234');
       $I->see('John Doe');
       $I->see('Rank 3');
       $I->see('LEBL');

       $I->see('Update', 'a');
       $I->see('Delete', 'a');

       $model = \app\models\Pilot::find()->where(['id' => 1])->one();
       $I->assertNotNull($model);
       $I->assertEquals('John', $model->name);
       $I->assertEquals('Doe', $model->surname);
       $I->assertEquals('john.doe@example.com', $model->email);
       $I->assertEquals('Madrid', $model->city);
       $I->assertEquals('1990-01-01', $model->date_of_birth);
       $I->assertEquals('LEBL', $model->location);
       $I->assertEquals('2020-02-02', $model->registration_date);
       $I->assertEquals(1, $model->id);
       $I->assertEquals(3, $model->rank_id);
       $I->assertEquals(10.5, $model->hours_flown);

       $count = \app\models\Pilot::find()->count();
       $I->assertEquals(10, $count);
    }

}