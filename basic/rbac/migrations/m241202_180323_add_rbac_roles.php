<?php

use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use app\rbac\rules\EditPageContentRule;
use app\rbac\rules\FlightDeletionRule;
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
        $submitFpl = $auth->createPermission(Permissions::SUBMIT_FPL);
        $submitFpl->description = 'Submit a flight plan';
        $auth->add($submitFpl);

        // Image upload rule
        $imageUploadRule = new ImageUploadRule();
        $auth->add($imageUploadRule);
        $uploadImage = $auth->createPermission(Permissions::UPLOAD_IMAGE);
        $uploadImage->ruleName = $imageUploadRule->name;
        $auth->add($uploadImage);

        // Owner Fpl Rule
        $ownerFplRule = new SubmittedFlightPlanOwnerRule();
        $auth->add($ownerFplRule);
        $crudOwnFpl = $auth->createPermission(Permissions::CRUD_OWN_FPL);
        $crudOwnFpl->description = 'Update, delete or view its submitted flight plan';
        $crudOwnFpl->ruleName = $ownerFplRule->name;
        $auth->add($crudOwnFpl);

        // Flight deletion rule
        $flightDeletionRule = new FlightDeletionRule();
        $auth->add($flightDeletionRule);
        $deleteOwnFlight = $auth->createPermission(Permissions::DELETE_OWN_FLIGHT);
        $deleteOwnFlight->description = 'Delete own flight pending validation';
        $deleteOwnFlight->ruleName = $flightDeletionRule->name;
        $auth->add($deleteOwnFlight);

        $pilot = $auth->createRole(Roles::PILOT);
        $auth->add($pilot);
        $auth->addChild($pilot, $submitFpl);
        $auth->addChild($pilot, $crudOwnFpl);
        $auth->addChild($pilot, $deleteOwnFlight);
        // All active pilots inherit this rule, so all "potentially" can upload images
        $auth->addChild($pilot, $uploadImage);

        // Flight Validator rule
        $flightValidationRule = new FlightValidationRule();
        $auth->add($flightValidationRule);
        $validateFlight = $auth->createPermission(Permissions::VALIDATE_FLIGHT);
        $validateFlight->ruleName = $flightValidationRule->name;
        $auth->add($validateFlight);

        // VFR validator
        $validateVfrFlight = $auth->createPermission(Permissions::VALIDATE_VFR_FLIGHT);
        $validateVfrFlight->description = 'Validate a VFR flight';
        $auth->add($validateVfrFlight);

        $vfrValidator = $auth->createRole(Roles::VFR_VALIDATOR);
        $auth->add($vfrValidator);
        $auth->addChild($vfrValidator, $validateVfrFlight);
        $auth->addChild($vfrValidator, $validateFlight);

        // IFR validator
        $validateIfrFlight = $auth->createPermission(Permissions::VALIDATE_IFR_FLIGHT);
        $validateIfrFlight->description = 'Validate a IFR flight';
        $auth->add($validateIfrFlight);

        $ifrValidator = $auth->createRole(Roles::IFR_VALIDATOR);
        $auth->add($ifrValidator);
        $auth->addChild($ifrValidator, $validateIfrFlight);
        $auth->addChild($ifrValidator, $validateFlight);

        // Fleet Operator
        $moveAircraft = $auth->createPermission(Permissions::MOVE_AIRCRAFT);
        $moveAircraft->description = 'Move the aircraft to a new location';
        $auth->add($moveAircraft);

        $fleetOperator = $auth->createRole(Roles::FLEET_OPERATOR);
        $auth->add($fleetOperator);
        $auth->addChild($fleetOperator, $moveAircraft);

        // Fleet Manager
        $aircraftTypeCrud = $auth->createPermission(Permissions::AIRCRAFT_TYPE_CRUD);
        $aircraftTypeCrud->description = 'Can create, delete, and modify aircraft types';
        $auth->add($aircraftTypeCrud);

        $aircraftConfigurationCrud = $auth->createPermission(Permissions::AIRCRAFT_CONFIGURATION_CRUD);
        $aircraftConfigurationCrud->description = 'Can create, delete, and modify aircraft configurations';
        $auth->add($aircraftConfigurationCrud);

        $aircraftCrud = $auth->createPermission(Permissions::AIRCRAFT_CRUD);
        $aircraftCrud->description = 'Can create, delete, and modify aircrafts';
        $auth->add($aircraftCrud);

        $fleetManager = $auth->createRole(Roles::FLEET_MANAGER);
        $auth->add($fleetManager);
        $auth->addChild($fleetManager, $aircraftTypeCrud);
        $auth->addChild($fleetManager, $aircraftConfigurationCrud);
        $auth->addChild($fleetManager, $aircraftCrud);

        // Route Manager
        $routeCrud = $auth->createPermission(Permissions::ROUTE_CRUD);
        $routeCrud->description = 'Can create, delete or modify routes';
        $auth->add($routeCrud);

        $routeManager = $auth->createRole(Roles::ROUTE_MANAGER);
        $auth->add($routeManager);
        $auth->addChild($routeManager, $routeCrud);

        // Edit page content rule
        $editPageContentRule = new EditPageContentRule();
        $auth->add($editPageContentRule);
        $editPageContent = $auth->createPermission(Permissions::EDIT_PAGE_CONTENT);
        $editPageContent->ruleName = $editPageContentRule->name;
        $auth->add($editPageContent);

        // Tour Manager
        $tourCrud = $auth->createPermission(Permissions::TOUR_CRUD);
        $tourCrud->description = 'Can create, delete or modify tours';
        $auth->add($tourCrud);

        $tourManager = $auth->createRole(Roles::TOUR_MANAGER);
        $auth->add($tourManager);
        $auth->addChild($tourManager, $tourCrud);
        // Only tour manager and administrator (that inherits permissions) should have the rule for extra security
        $auth->addChild($tourManager, $editPageContent);

        // Airport Manager
        $airportCrud = $auth->createPermission(Permissions::AIRPORT_CRUD);
        $airportCrud->description = 'Can create, delete, and modify airports';
        $auth->add($airportCrud);

        $airportManager = $auth->createRole(Roles::AIRPORT_MANAGER);
        $auth->add($airportManager);
        $auth->addChild($airportManager, $airportCrud);

        // Admin
        $userCrud = $auth->createPermission(Permissions::USER_CRUD);
        $userCrud->description = 'Can create, delete, modify, activate and reset users';
        $auth->add($userCrud);

        $countryCrud = $auth->createPermission(Permissions::COUNTRY_CRUD);
        $countryCrud->description = 'Can create, delete, and modify countries';
        $auth->add($countryCrud);

        $roleAssignment = $auth->createPermission(Permissions::ROLE_ASSIGNMENT);
        $roleAssignment->description = 'Can assign or remove roles to other users';
        $auth->add($roleAssignment);

        $rankCrud = $auth->createPermission(Permissions::RANK_CRUD);
        $rankCrud->description = 'Can create, delete or modify ranks';
        $auth->add($rankCrud);

        $imageCrud = $auth->createPermission(Permissions::IMAGE_CRUD);
        $imageCrud->description = 'Can index and delete images';
        $auth->add($imageCrud);

        $changeSiteSettings = $auth->createPermission(Permissions::CHANGE_SITE_SETTINGS);
        $changeSiteSettings->description = 'Can change the site settings';
        $auth->add($changeSiteSettings);

        $admin = $auth->createRole(Roles::ADMIN);
        $auth->add($admin);
        $auth->addChild($admin, $userCrud);
        $auth->addChild($admin, $countryCrud);
        $auth->addChild($admin, $roleAssignment);
        $auth->addChild($admin, $rankCrud);
        $auth->addChild($admin, $imageCrud);
        $auth->addChild($admin, $changeSiteSettings);
        $auth->addChild($admin, $pilot);
        $auth->addChild($admin, $vfrValidator);
        $auth->addChild($admin, $ifrValidator);
        $auth->addChild($admin, $fleetOperator);
        $auth->addChild($admin, $fleetManager);
        $auth->addChild($admin, $routeManager);
        $auth->addChild($admin, $tourManager);
        $auth->addChild($admin, $airportManager);

        // By now only assignable via database for security purposes
        $assignAdmin = $auth->createPermission(Permissions::ASSIGN_ADMIN);
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
