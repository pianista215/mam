<?php

use app\rbac\rules\ImageUploadRule;
use app\rbac\rules\FlightValidationRule;
use app\rbac\rules\SubmittedFlightPlanOwnerRule;
use yii\db\Migration;

/**
 * Class m241202_180323_add_rbac_roles
 */
class m241202_180323_add_rbac_roles extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand()->delete('`auth_assignment`')->execute();
        Yii::$app->db->createCommand()->delete('`auth_item_child`')->execute();
        Yii::$app->db->createCommand()->delete('`auth_item`')->execute();
        Yii::$app->db->createCommand()->delete('`auth_rule`')->execute();

        $auth = Yii::$app->authManager;

        // Pilot
        $reportFlight = $auth->createPermission('reportFlight');
        $reportFlight->description = 'Report a flight from Acars';
        $auth->add($reportFlight);

        $submitFpl = $auth->createPermission('submitFpl');
        $submitFpl->description = 'Submit a flight plan';
        $auth->add($submitFpl);

        // Image upload rule
        $imageUploadRule = new ImageUploadRule();
        $auth->add($imageUploadRule);
        $uploadImage = $auth->createPermission('uploadImage');
        $uploadImage->ruleName = $imageUploadRule->name;
        $auth->add($uploadImage);

        // Owner Fpl Rule
        $ownerFplRule = new SubmittedFlightPlanOwnerRule();
        $auth->add($ownerFplRule);
        $crudOwnFpl = $auth->createPermission('crudOwnFpl');
        $crudOwnFpl->description = 'Update, delete or view its submitted flight plan';
        $crudOwnFpl->ruleName = $ownerFplRule->name;
        $auth->add($crudOwnFpl);

        $pilot = $auth->createRole('pilot');
        $auth->add($pilot);
        $auth->addChild($pilot, $reportFlight);
        $auth->addChild($pilot, $submitFpl);
        $auth->addChild($pilot, $crudOwnFpl);
        // All active pilots inherit this rule, so all "potentially" can upload images
        $auth->addChild($pilot, $uploadImage);

        // Flight Validator rule
        $flightValidationRule = new FlightValidationRule();
        $auth->add($flightValidationRule);
        $validateFlight = $auth->createPermission('validateFlight');
        $validateFlight->ruleName = $flightValidationRule->name;
        $auth->add($validateFlight);

        // VFR validator
        $validateVfrFlight = $auth->createPermission('validateVfrFlight');
        $validateVfrFlight->description = 'Validate a VFR flight';
        $auth->add($validateVfrFlight);

        $vfrValidator = $auth->createRole('vfrValidator');
        $auth->add($vfrValidator);
        $auth->addChild($vfrValidator, $validateVfrFlight);
        $auth->addChild($vfrValidator, $validateFlight);

        // IFR validator
        $validateIfrFlight = $auth->createPermission('validateIfrFlight');
        $validateIfrFlight->description = 'Validate a IFR flight';
        $auth->add($validateIfrFlight);

        $ifrValidator = $auth->createRole('ifrValidator');
        $auth->add($ifrValidator);
        $auth->addChild($ifrValidator, $validateIfrFlight);
        $auth->addChild($ifrValidator, $validateFlight);

        // Fleet Operator
        $moveAircraft = $auth->createPermission('moveAircraft');
        $moveAircraft->description = 'Move the aircraft to a new location';
        $auth->add($moveAircraft);

        $fleetOperator = $auth->createRole('fleetOperator');
        $auth->add($fleetOperator);
        $auth->addChild($fleetOperator, $moveAircraft);

        // Fleet Manager
        $aircraftTypeCrud = $auth->createPermission('aircraftTypeCrud');
        $aircraftTypeCrud->description = 'Can create, delete, and modify aircraft types';
        $auth->add($aircraftTypeCrud);

        $aircraftConfigurationCrud = $auth->createPermission('aircraftConfigurationCrud');
        $aircraftConfigurationCrud->description = 'Can create, delete, and modify aircraft configurations';
        $auth->add($aircraftConfigurationCrud);

        $aircraftCrud = $auth->createPermission('aircraftCrud');
        $aircraftCrud->description = 'Can create, delete, and modify aircrafts';
        $auth->add($aircraftCrud);

        $fleetManager = $auth->createRole('fleetManager');
        $auth->add($fleetManager);
        $auth->addChild($fleetManager, $aircraftTypeCrud);
        $auth->addChild($fleetManager, $aircraftConfigurationCrud);
        $auth->addChild($fleetManager, $aircraftCrud);

        // Route Manager
        $routeCrud = $auth->createPermission('routeCrud');
        $routeCrud->description = 'Can create, delete or modify routes';
        $auth->add($routeCrud);

        $routeManager = $auth->createRole('routeManager');
        $auth->add($routeManager);
        $auth->addChild($routeManager, $routeCrud);

        // Tour Manager
        $tourCrud = $auth->createPermission('tourCrud');
        $tourCrud->description = 'Can create, delete or modify tours';
        $auth->add($tourCrud);

        $tourManager = $auth->createRole('tourManager');
        $auth->add($tourManager);
        $auth->addChild($tourManager, $tourCrud);

        // Airport Manager
        $airportCrud = $auth->createPermission('airportCrud');
        $airportCrud->description = 'Can create, delete, and modify airports';
        $auth->add($airportCrud);

        $airportManager = $auth->createRole('airportManager');
        $auth->add($airportManager);
        $auth->addChild($airportManager, $airportCrud);

        // Admin
        $userCrud = $auth->createPermission('userCrud');
        $userCrud->description = 'Can create, delete, modify, activate and reset users';
        $auth->add($userCrud);

        $countryCrud = $auth->createPermission('countryCrud');
        $countryCrud->description = 'Can create, delete, and modify countries';
        $auth->add($countryCrud);

        $roleAssignment = $auth->createPermission('roleAssignment');
        $roleAssignment->description = 'Can assign or remove roles to other users';
        $auth->add($roleAssignment);

        $rankCrud = $auth->createPermission('rankCrud');
        $rankCrud->description = 'Can create, delete or modify ranks';
        $auth->add($rankCrud);

        $imageCrud = $auth->createPermission('imageCrud');
        $imageCrud->description = 'Can index and delete images';
        $auth->add($imageCrud);

        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $userCrud);

        $auth->addChild($admin, $countryCrud);
        $auth->addChild($admin, $roleAssignment);
        $auth->addChild($admin, $rankCrud);
        $auth->addChild($admin, $pilot);
        $auth->addChild($admin, $imageCrud);
        $auth->addChild($admin, $vfrValidator);
        $auth->addChild($admin, $ifrValidator);
        $auth->addChild($admin, $fleetOperator);
        $auth->addChild($admin, $fleetManager);
        $auth->addChild($admin, $routeManager);
        $auth->addChild($admin, $tourManager);
        $auth->addChild($admin, $airportManager);

        // By now only assignable via database for security purposes
        $assignAdmin = $auth->createPermission('assignAdmin');
        $assignAdmin->description = 'Can assign or revoke admin role';
        $auth->add($assignAdmin);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();
    }

}
