<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\PilotSearch;


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

    public function actionActivateUsers(){
        if(Yii::$app->user->can('userCrud')){
            $searchModel = new PilotSearch();
            $dataProvider = $searchModel->search([]);
            $dataProvider->query->andWhere(['license' => null]);
            return $this->render('activate-users', [
                        'dataProvider' => $dataProvider,
                    ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

}