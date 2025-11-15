<?php

namespace app\rbac\rules;

use yii\rbac\Rule;
use Yii;
use app\models\Image;
use Codeception\Util\Debug;

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
                return Yii::$app->user->can('rankCrud');

            case 'tour_image':
                return Yii::$app->user->can('tourCrud');

            case 'country_icon':
                return Yii::$app->user->can('countryCrud');

            case 'aircraftType_image':
                return Yii::$app->user->can('aircraftTypeCrud');

            case 'page':
                return Yii::$app->authManager->getAssignment('admin', $userId) !== null;

            case 'pilot_profile':
                return ((int)$relatedId === (int)$userId) || Yii::$app->user->can('userCrud');

            default:
                return false;
        }
    }
}
