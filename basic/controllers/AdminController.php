<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\forms\AssignRolesForm;
use app\models\Pilot;
use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;

class AdminController extends Controller
{
    use LoggerTrait;

    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'only' => ['roles-matrix', 'edit-roles'],
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ]
            ]
        );
    }

    public function actionRolesMatrix()
    {
        if (!Yii::$app->user->can(Permissions::ROLE_ASSIGNMENT)) {
            throw new ForbiddenHttpException();
        }

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
        if (!Yii::$app->user->can(Permissions::ROLE_ASSIGNMENT)) {
            throw new ForbiddenHttpException();
        }

        $user = Pilot::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $auth = Yii::$app->authManager;
        $currentRoles = array_keys($auth->getRolesByUser($id));

        if (in_array(Roles::ADMIN, $currentRoles, true) && !Yii::$app->user->can(Permissions::ASSIGN_ADMIN)) {
            $this->logError('Forbidden attempt to modify roles of an admin user without assignAdmin permission', ['role_user' => $user, 'user' => Yii::$app->user->identity->license]);
            throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to change the roles of an admin user.'));
        }

        $form = new AssignRolesForm([
            'userId' => $id,
            'roles' => $currentRoles,
        ]);

        if ($form->load(Yii::$app->request->post())) {
            if (in_array(Roles::ADMIN, $form->roles, true) && !Yii::$app->user->can(Permissions::ASSIGN_ADMIN)) {
                $this->logError('Forbidden attempt to assign admin role without assignAdmin permission', ['role_user' => $id, 'form' => $form, 'user' => Yii::$app->user->identity->license]);
                throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to assign the admin role.'));
            }
            if ($form->save()) {
                $this->logInfo('Roles updated successfully for target user', ['form' => $form, 'user' => Yii::$app->user->identity->license]);
                Yii::$app->session->setFlash('success', Yii::t('app','Roles updated for user') . ': ' . $user->fullname);
                return $this->redirect(['roles-matrix']);
            }
        }

        return $this->render('edit-roles', [
            'user' => $user,
            'roles' => $auth->getRoles(),
            'assigned' => $form->roles,
            'formModel' => $form,
        ]);
    }


}