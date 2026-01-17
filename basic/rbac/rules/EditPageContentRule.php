<?php

namespace app\rbac\rules;

use app\models\Page;
use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use Yii;
use yii\rbac\Rule;

class EditPageContentRule extends Rule
{
    public $name = 'editPageContentRule';

    public function execute($userId, $item, $params)
    {

        if (Yii::$app->user->isGuest) {
            return false;
        }

        $page = $params['page'] ?? null;
        if (!$page instanceof Page) {
            return false;
        }

        $type = $page->type;

        switch ($type) {

            case Page::TYPE_TOUR:
                return Yii::$app->user->can(Permissions::TOUR_CRUD);

            case Page::TYPE_SITE:
                return Yii::$app->authManager->getAssignment(Roles::ADMIN, $userId) !== null;

            case Page::TYPE_COMPONENT:
                return Yii::$app->authManager->getAssignment(Roles::ADMIN, $userId) !== null;

            default:
                return false;
        }
    }
}
