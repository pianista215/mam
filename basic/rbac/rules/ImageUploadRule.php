<?php

namespace app\rbac\rules;

use app\models\Image;
use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use Yii;
use yii\rbac\Rule;

class ImageUploadRule extends Rule
{
    public $name = 'imageUploadRule';

    public function execute($userId, $item, $params)
    {

        if (Yii::$app->user->isGuest) {
            return false;
        }

        $image = $params['image'] ?? null;
        if (!$image instanceof Image) {
            return false;
        }

        $type = $image->type;
        $relatedId = $image->related_id;

        switch ($type) {

            case 'rank_icon':
                return Yii::$app->user->can(Permissions::RANK_CRUD);

            case 'tour_image':
                return Yii::$app->user->can(Permissions::TOUR_CRUD);

            case 'country_icon':
                return Yii::$app->user->can(Permissions::COUNTRY_CRUD);

            case 'aircraftType_image':
                return Yii::$app->user->can(Permissions::AIRCRAFT_TYPE_CRUD);

            case 'page_image':
                return Yii::$app->authManager->getAssignment(Roles::ADMIN, $userId) !== null;

            case 'pilot_profile':
                return ((int)$relatedId === (int)$userId) || Yii::$app->user->can(Permissions::USER_CRUD);

            default:
                return false;
        }
    }
}
