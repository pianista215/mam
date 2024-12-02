<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\EntryForm;

class AdminController extends Controller
{

    // TODO: REMOVE WHEN NO LONGER IS NEEDED TO SET ADMIN BY THAT
    // index.php?r=site%2Ftoggle-admin-role
    public function actionToggleAdminRole() {
        if (!Yii::$app->user->isGuest) {
            $auth = Yii::$app->authManager;
            $actualRoles = $auth->getRolesByUser(Yii::$app->user->id);
            if(isset($actualRoles['admin'])) {
                $adminRole = $actualRoles['admin'];
                $auth->revoke($adminRole, Yii::$app->user->id);
            } else {
                $adminRole = $auth->getRole('admin');
                $auth->assign($adminRole, Yii::$app->user->id);
            }
        }
        return $this->redirect(['site/index']);
    }

}