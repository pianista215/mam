<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\CredentialType;
use app\models\CredentialTypeSearch;
use app\rbac\constants\Permissions;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;

/**
 * CredentialTypeController implements the CRUD actions for CredentialType model.
 */
class CredentialTypeController extends Controller
{
    use LoggerTrait;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'only' => ['index', 'view', 'create', 'update', 'delete'],
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all CredentialType models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new CredentialTypeSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        $dataProvider->sort->defaultOrder = [
            'type' => SORT_ASC,
            'name' => SORT_ASC,
        ];

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single CredentialType model.
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new CredentialType model.
     *
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->can(Permissions::CREDENTIAL_CRUD)) {
            throw new ForbiddenHttpException();
        }

        $model = new CredentialType();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                $this->logInfo('Created credential type', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing CredentialType model.
     *
     * @param int $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->user->can(Permissions::CREDENTIAL_CRUD)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            $this->logInfo('Updated credential type', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing CredentialType model.
     *
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        if (!Yii::$app->user->can(Permissions::CREDENTIAL_CRUD)) {
            throw new ForbiddenHttpException();
        }

        $model = $this->findModel($id);
        $model->delete();
        $this->logInfo('Deleted credential type', ['id' => $id, 'user' => Yii::$app->user->identity->license]);

        return $this->redirect(['index']);
    }

    /**
     * Finds the CredentialType model based on its primary key value.
     *
     * @param int $id
     * @return CredentialType
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = CredentialType::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
