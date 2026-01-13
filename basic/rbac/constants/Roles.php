<?php

namespace app\rbac\constants;

final class Roles
{
    public const PILOT = 'pilot';

    public const VFR_VALIDATOR = 'vfrValidator';
    public const IFR_VALIDATOR = 'ifrValidator';

    public const FLEET_OPERATOR = 'fleetOperator';
    public const FLEET_MANAGER = 'fleetManager';

    public const ROUTE_MANAGER = 'routeManager';
    public const TOUR_MANAGER = 'tourManager';
    public const AIRPORT_MANAGER = 'airportManager';

    public const ADMIN = 'admin';

    private function __construct() {}
}
