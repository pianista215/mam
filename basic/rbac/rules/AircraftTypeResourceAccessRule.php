<?php

namespace app\rbac\rules;

use app\models\CredentialTypeAircraftType;
use app\rbac\constants\Permissions;
use Yii;
use yii\rbac\Rule;

class AircraftTypeResourceAccessRule extends Rule
{
    public $name = 'aircraftTypeResourceAccessRule';

    public function execute($userId, $item, $params)
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        if (Yii::$app->user->can(Permissions::AIRCRAFT_TYPE_RESOURCE_CRUD)) {
            return true;
        }

        $aircraftTypeId = $params['aircraft_type_id'] ?? null;
        if ($aircraftTypeId === null) {
            return false;
        }

        return CredentialTypeAircraftType::pilotCanFlyAircraftType((int) $userId, (int) $aircraftTypeId);
    }
}
