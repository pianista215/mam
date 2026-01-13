<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\Airport;
use app\models\AirportSearch;
use app\models\Country;
use app\rbac\constants\Permissions;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;

/**
 * AirportController implements the CRUD actions for Airport model.
 */
class AirportController extends Controller
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
                    'only' => ['view', 'create', 'update', 'delete'],
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
     * Lists all Airport models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new AirportSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        $dataProvider->sort->defaultOrder = [
            'icao_code' => SORT_ASC,
        ];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Airport model.
     * @param string $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Airport model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        if(Yii::$app->user->can(Permissions::AIRPORT_CRUD)){
            $model = new Airport();

            if ($this->request->isPost) {
                if ($model->load($this->request->post()) && $model->save()) {
                    $this->logInfo('Created airport', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } else {
                $model->loadDefaultValues();
            }

            $countries = Country::find()
                ->select(['name'])
                ->indexBy('id')
                ->orderBy(['name' => SORT_ASC])
                ->column();

            return $this->render('create', [
                'model' => $model,
                'countries' => $countries,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Updates an existing Airport model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if(Yii::$app->user->can(Permissions::AIRPORT_CRUD)){
            $model = $this->findModel($id);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $this->logInfo('Updated airport', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $countries = Country::find()
                ->select(['name'])
                ->indexBy('id')
                ->orderBy(['name' => SORT_ASC])
                ->column();

            return $this->render('update', [
                'model' => $model,
                'countries' => $countries,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Deletes an existing Airport model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if(Yii::$app->user->can(Permissions::AIRPORT_CRUD)){
            $this->findModel($id)->delete();
            $this->logInfo('Deleted airport', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
            return $this->redirect(['index']);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Finds the Airport model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id ID
     * @return Airport the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Airport::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
