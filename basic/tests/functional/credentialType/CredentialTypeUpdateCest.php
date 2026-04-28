<?php

namespace tests\functional\credentialType;

use app\models\CredentialType;
use app\models\CredentialTypeAirportAircraft;
use app\models\CredentialTypePrerequisite;
use tests\fixtures\AircraftTypeFixture;
use tests\fixtures\AuthAssignmentFixture;
use tests\fixtures\CredentialTypeFixture;
use Yii;

class CredentialTypeUpdateCest
{
    public function _fixtures()
    {
        return [
            'authAssignment' => AuthAssignmentFixture::class,
            'credentialType' => CredentialTypeFixture::class,
            'aircraftType'   => AircraftTypeFixture::class,
        ];
    }

    public function openUpdateAsAdmin(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/update', ['id' => '1']);

        $I->see('Update Credential Type: Private Pilot License');
        $I->see('Save', 'button');
        $I->seeInField('#credentialtype-code', 'PPL');
        $I->seeInField('#credentialtype-name', 'Private Pilot License');
    }

    public function openUpdateAsUser(\FunctionalTester $I)
    {
        $I->amLoggedInAs(1);
        $I->amOnRoute('credential-type/update', ['id' => '1']);

        $I->seeResponseCodeIs(403);
        $I->see('Forbidden');
        $I->dontSee('Save', 'button');
    }

    public function openUpdateAsVisitor(\FunctionalTester $I)
    {
        $I->amOnRoute('credential-type/update', ['id' => '1']);
        $I->seeCurrentUrlMatches('~login~');
        $I->see('Login');
    }

    public function updateWithEmptyFields(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/update', ['id' => '1']);

        $I->fillField('#credentialtype-code', '');
        $I->fillField('#credentialtype-name', '');
        $I->click('Save', 'button');

        $I->expectTo('see validation errors');
        $I->see('Code cannot be blank.');
        $I->see('Name cannot be blank.');

        $model = CredentialType::findOne(1);
        $I->assertEquals('PPL', $model->code);

        $count = CredentialType::find()->count();
        $I->assertEquals(6, $count);
    }

    public function updateWithDuplicateCode(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/update', ['id' => '2']);

        $I->fillField('#credentialtype-code', 'PPL');
        $I->click('Save', 'button');

        $I->see('Code "PPL" has already been taken.');

        $model = CredentialType::findOne(2);
        $I->assertEquals('IR', $model->code);
    }

    public function updateValidFields(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/update', ['id' => '3']);

        $I->fillField('#credentialtype-code', 'CPL-H');
        $I->fillField('#credentialtype-name', 'Commercial Pilot License (Helicopter)');
        $I->selectOption('#credentialtype-type', '1');
        $I->fillField('#credentialtype-description', 'Helicopter commercial variant.');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('CPL-H');
        $I->see('Commercial Pilot License (Helicopter)');
        $I->see('License');
        $I->see('Helicopter commercial variant.');

        $model = CredentialType::findOne(3);
        $I->assertEquals('CPL-H', $model->code);
        $I->assertEquals('Commercial Pilot License (Helicopter)', $model->name);
        $I->assertEquals('Helicopter commercial variant.', $model->description);

        $count = CredentialType::find()->count();
        $I->assertEquals(6, $count);
    }

    public function updateAddPrerequisite(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/update', ['id' => '2']);

        $I->checkOption('input[name="CredentialType[prerequisiteIds][]"][value="1"]');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Private Pilot License');

        $prereq = CredentialTypePrerequisite::find()
            ->where(['parent_id' => 1, 'child_id' => 2])
            ->one();
        $I->assertNotNull($prereq);
    }

    public function updateAddAircraftType(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/update', ['id' => '1']);

        $I->checkOption('input[name="CredentialType[aircraftTypeIds][]"][value="2"]');
        $I->click('Save', 'button');

        $I->seeResponseCodeIs(200);
        $I->see('Boeing 737-800');

        $model = CredentialType::findOne(1);
        $aircraftTypes = $model->aircraftTypes;
        $I->assertNotEmpty($aircraftTypes);
        $I->assertEquals('Boeing 737-800', $aircraftTypes[0]->name);
    }

    public function updateWithAirportRestriction(\FunctionalTester $I)
    {
        $I->amLoggedInAs(2);
        $I->amOnRoute('credential-type/update', ['id' => '4']);
        $I->submitForm('div.credential-type-form form', [
            'CredentialType[code]'         => 'MNPS',
            'CredentialType[name]'         => 'MNPS Certification',
            'CredentialType[type]'         => 3,
            'airportIcaos[]'               => 'LEBL',
            'restrictionAircraftTypeIds[]' => 1,
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Affected Airports');
        $I->see('LEBL');
        $I->see('Restricted Aircraft Types');
        $I->see('Airbus A320');

        $restriction = CredentialTypeAirportAircraft::find()
            ->where(['credential_type_id' => 4, 'airport_icao' => 'LEBL', 'aircraft_type_id' => 1])
            ->one();
        $I->assertNotNull($restriction);
    }

    public function updateClearsRestrictions(\FunctionalTester $I)
    {
        // Insert a restriction manually
        Yii::$app->db->createCommand()->insert('credential_type_airport_aircraft', [
            'credential_type_id' => 4,
            'aircraft_type_id'   => 1,
            'airport_icao'       => 'LEMD',
        ])->execute();

        $I->amLoggedInAs(2);

        // sendAjaxPostRequest sends exactly the specified payload (no pre-rendered hidden inputs),
        // so the server receives empty airportIcaos and restrictionAircraftTypeIds arrays
        $I->sendAjaxPostRequest('/credential-type/update?id=4', [
            'CredentialType' => [
                'code' => 'MNPS',
                'name' => 'MNPS Certification',
                'type' => 3,
            ],
        ]);

        $I->seeResponseCodeIsRedirection();

        $count = CredentialTypeAirportAircraft::find()
            ->where(['credential_type_id' => 4])
            ->count();
        $I->assertEquals(0, $count);
    }
}
