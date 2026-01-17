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

            case Image::TYPE_RANK_ICON:
                return Yii::$app->user->can(Permissions::RANK_CRUD);

            case Image::TYPE_TOUR_IMAGE:
                return Yii::$app->user->can(Permissions::TOUR_CRUD);

            case Image::TYPE_COUNTRY_ICON:
                return Yii::$app->user->can(Permissions::COUNTRY_CRUD);

            case Image::TYPE_AIRCRAFT_TYPE_IMAGE:
                return Yii::$app->user->can(Permissions::AIRCRAFT_TYPE_CRUD);

            case Image::TYPE_PAGE_IMAGE:
                return Yii::$app->authManager->getAssignment(Roles::ADMIN, $userId) !== null;

            case Image::TYPE_PILOT_PROFILE:
                return ((int)$relatedId === (int)$userId) || Yii::$app->user->can(Permissions::USER_CRUD);

            default:
                return false;
        }
    }
}
