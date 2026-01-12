<?php

namespace app\controllers;

use app\models\AssignRolesForm;
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

    public function actionEditRoles($id)
    {
        $user = Pilot::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $auth = Yii::$app->authManager;

        $roles = $auth->getRoles();
        $assigned = array_keys($auth->getRolesByUser($id));

        $form = new AssignRolesForm([
            'userId' => $id,
            'roles' => $assigned,
        ]);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app','Roles updated for user') .': '. $user->fullname);
            return $this->redirect(['roles-matrix']);
        }

        return $this->render('edit-roles', [
            'user' => $user,
            'roles' => $roles,
            'assigned' => $form->roles,
            'formModel' => $form,
        ]);
    }


}