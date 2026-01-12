<?php

namespace app\controllers;

use app\models\Pilot;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;



class AdminController extends Controller
{

    public function actionRolesMatrix()
    {
        $auth = Yii::$app->authManager;

        $roles = $auth->getRoles();

        $users = Pilot::find()->all();

        $matrix = [];
        foreach ($users as $user) {
            $assignedRoles = $auth->getRolesByUser($user->id);
            $matrix[$user->id] = [];
            foreach ($roles as $roleName => $role) {
                $matrix[$user->id][$roleName] = isset($assignedRoles[$roleName]);
            }
        }

        return $this->render('roles-matrix', [
            'users' => $users,
            'roles' => $roles,
            'matrix' => $matrix,
        ]);
    }

    public function actionRoles($id)
    {
        $user = User::findOne($id);
        $auth = Yii::$app->authManager;

        $roles = $auth->getRoles();
        $assigned = array_keys($auth->getRolesByUser($id));

        if (Yii::$app->request->isPost) {
            $selected = Yii::$app->request->post('roles', []);

            $auth->revokeAll($id);
            foreach ($selected as $roleName) {
                $role = $auth->getRole($roleName);
                $auth->assign($role, $id);
            }

            Yii::$app->session->setFlash('success', Yii::t('app','Roles updated'));
            return $this->redirect(['user/view', 'id'=>$id]);
        }

        return $this->render('roles', [
            'user'=>$user,
            'roles'=>$roles,
            'assigned'=>$assigned,
        ]);
    }


}