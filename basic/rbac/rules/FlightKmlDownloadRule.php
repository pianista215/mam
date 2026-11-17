<?php

namespace app\rbac\rules;

use app\rbac\constants\Permissions;
use Yii;
use yii\rbac\Rule;

/**
 * The flight KML (black box for Google Earth) can be downloaded by the flight pilot and by the
 * validators of its flight rules: VFR validators for VFR flights, IFR validators otherwise.
 */
class FlightKmlDownloadRule extends Rule
{
    public $name = 'flightKmlDownloadRule';

    public function execute($user, $item, $params)
    {
        $flight = $params['flight'] ?? null;
        if (!$flight || $user === null) {
            return false;
        }

        if ((int) $flight->pilot_id === (int) $user) {
            return true;
        }

        $authManager = Yii::$app->authManager;
        $permission = $flight->flight_rules === 'V'
            ? Permissions::VALIDATE_VFR_FLIGHT
            : Permissions::VALIDATE_IFR_FLIGHT;
        return $authManager->checkAccess($user, $permission);
    }
}
