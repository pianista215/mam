<?php

namespace app\rbac\constants;

final class Permissions
{
    // Pilot
    public const SUBMIT_FPL = 'submitFpl';
    public const CRUD_OWN_FPL = 'crudOwnFpl';
    public const UPLOAD_IMAGE = 'uploadImage';
    public const DELETE_OWN_FLIGHT = 'deleteOwnFlight';

    // Validators
    public const VALIDATE_FLIGHT = 'validateFlight';
    public const VALIDATE_VFR_FLIGHT = 'validateVfrFlight';
    public const VALIDATE_IFR_FLIGHT = 'validateIfrFlight';

    // Fleet
    public const MOVE_AIRCRAFT = 'moveAircraft';
    public const AIRCRAFT_TYPE_CRUD = 'aircraftTypeCrud';
    public const AIRCRAFT_CONFIGURATION_CRUD = 'aircraftConfigurationCrud';
    public const AIRCRAFT_CRUD = 'aircraftCrud';

    // Route / tour / airport
    public const ROUTE_CRUD = 'routeCrud';
    public const TOUR_CRUD = 'tourCrud';
    public const AIRPORT_CRUD = 'airportCrud';

    // Tour & Admin
    public const EDIT_PAGE_CONTENT = 'editPageContent';

    // Admin
    public const USER_CRUD = 'userCrud';
    public const COUNTRY_CRUD = 'countryCrud';
    public const ROLE_ASSIGNMENT = 'roleAssignment';
    public const RANK_CRUD = 'rankCrud';
    public const IMAGE_CRUD = 'imageCrud';
    public const CHANGE_SITE_SETTINGS = 'changeSiteSettings';

    // Superadmin
    public const ASSIGN_ADMIN = 'assignAdmin';

    private function __construct() {}
}
