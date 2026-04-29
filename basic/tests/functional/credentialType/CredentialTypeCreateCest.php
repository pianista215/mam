<?php

namespace tests\functional\credentialType;

use app\models\CredentialType;
use app\models\CredentialTypeAirportAircraft;
use app\models\CredentialTypePrerequisite;
use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeFixture;

class CredentialTypeCreateCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'credentialType' => CredentialTypeFixture::class,
            'aircraftType'   => AircraftTypeFixture::class,
        ];
    }

    public function openCreateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/create');

        $I->see('Create Credential Type');
        $I->see('Save', 'button');
        $I->see('Code');
        $I->see('Name');
        $I->see('Prerequisites');
        $I->see('Unlocked Aircraft Types');
    }

    public function openCreateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('credential-type/create');

        $I->seeResponseCodeIs(403);
        $I->see('Forbidden');
        $I->dontSee('Create Credential Type');
        $I->dontSee('Save', 'button');
    }

    public function openCreateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('credential-type/create');
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function submitEmpty(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/create');
        $I->click('Save', 'button');

        $I->expectTo('see validation errors');
        $I->see('Code cannot be blank.');
        $I->see('Name cannot be blank.');

        $count = CredentialType::find()->count();
        $I->assertEquals(6, $count);
    }

    public function submitDuplicateCode(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/create');

        $I->fillField('#credentialtype-code', 'PPL');
        $I->fillField('#credentialtype-name', 'Duplicate PPL');
        $I->selectOption('#credentialtype-type', '1');
        $I->click('Save', 'button');

        $I->see('Code "PPL" has already been taken.');

        $count = CredentialType::find()->count();
        $I->assertEquals(6, $count);
    }

    public function submitValidMinimal(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/create');

        $I->fillField('#credentialtype-code', 'ATPL');
        $I->fillField('#credentialtype-name', 'Airline Transport Pilot License');
        $I->selectOption('#credentialtype-type', '1');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('ATPL');
        $I->see('Airline Transport Pilot License');
        $I->see('License');
        $I->see('Update', 'a');
        $I->see('Delete', 'a');

        $model = CredentialType::find()->where(['code' => 'ATPL'])->one();
        $I->assertNotNull($model);
        $I->assertEquals('Airline Transport Pilot License', $model->name);
        $I->assertEquals(CredentialType::TYPE_LICENSE, $model->type);
        $I->assertEmpty($model->description);

        $count = CredentialType::find()->count();
        $I->assertEquals(7, $count);
    }

    public function submitWithDescription(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/create');

        $I->fillField('#credentialtype-code', 'MEP');
        $I->fillField('#credentialtype-name', 'Multi Engine Piston');
        $I->selectOption('#credentialtype-type', '2');
        $I->fillField('#credentialtype-description', 'Allows flying multi-engine piston aircraft.');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('MEP');
        $I->see('Rating');
        $I->see('Allows flying multi-engine piston aircraft.');

        $model = CredentialType::find()->where(['code' => 'MEP'])->one();
        $I->assertNotNull($model);
        $I->assertEquals('Allows flying multi-engine piston aircraft.', $model->description);
        $I->assertEquals(CredentialType::TYPE_RATING, $model->type);
    }

    public function submitWithPrerequisite(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/create');

        $I->fillField('#credentialtype-code', 'CPL-NEW');
        $I->fillField('#credentialtype-name', 'CPL New');
        $I->selectOption('#credentialtype-type', '1');
        $I->checkOption('input[name="CredentialType[prerequisiteIds][]"][value="1"]');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('CPL New');
        $I->see('Private Pilot License');

        $model = CredentialType::find()->where(['code' => 'CPL-NEW'])->one();
        $I->assertNotNull($model);

        $prereq = CredentialTypePrerequisite::find()
            ->where(['parent_id' => 1, 'child_id' => $model->id])
            ->one();
        $I->assertNotNull($prereq);
    }

    public function submitWithAircraftType(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/create');

        $I->fillField('#credentialtype-code', 'TYPE-A320');
        $I->fillField('#credentialtype-name', 'A320 Type Rating');
        $I->selectOption('#credentialtype-type', '2');
        $I->checkOption('input[name="CredentialType[aircraftTypeIds][]"][value="1"]');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('A320 Type Rating');
        $I->see('Airbus A320');

        $model = CredentialType::find()->where(['code' => 'TYPE-A320'])->one();
        $I->assertNotNull($model);
        $I->assertNotEmpty($model->aircraftTypes);
        $I->assertEquals('Airbus A320', $model->aircraftTypes[0]->name);
    }

    public function submitWithAirportRestriction(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/create');
        $I->submitForm('div.credential-type-form form', [
            'CredentialType[code]'         => 'VQPR-CERT',
            'CredentialType[name]'         => 'Paro Certification',
            'CredentialType[type]'         => 3,
            'airportIcaos[]'               => 'LEMD',
            'restrictionAircraftTypeIds[]' => 1,
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Paro Certification');
        $I->see('Certification');
        $I->see('Affected Airports');
        $I->see('LEMD');
        $I->see('Restricted Aircraft Types');
        $I->see('Airbus A320');

        $model = CredentialType::find()->where(['code' => 'VQPR-CERT'])->one();
        $I->assertNotNull($model);

        $restriction = CredentialTypeAirportAircraft::find()
            ->where(['credential_type_id' => $model->id, 'airport_icao' => 'LEMD', 'aircraft_type_id' => 1])
            ->one();
        $I->assertNotNull($restriction);
    }
}
