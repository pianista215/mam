<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\AircraftSearch;
use app\models\AircraftConfiguration;
use app\models\AircraftConfigurationSearch;
use app\models\AircraftType;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;

/**
 * AircraftConfigurationController implements the CRUD actions for AircraftConfiguration model.
 */
class AircraftConfigurationController extends Controller
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
     * Lists all AircraftConfiguration models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new AircraftConfigurationSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        $dataProvider->sort->defaultOrder = [
            'name' => SORT_ASC,
        ];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single AircraftConfiguration model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        $searchModel = new AircraftSearch();
        $searchModel->aircraft_configuration_id = $model->id;
        $dataProvider = $searchModel->search([]);

        return $this->render('view', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new AircraftConfiguration model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        if(Yii::$app->user->can('aircraftConfigurationCrud')){
            $model = new AircraftConfiguration();

            if ($this->request->isPost) {
                if ($model->load($this->request->post()) && $model->save()) {
                    $this->logInfo('Created aircraft config', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } else {
                $model->loadDefaultValues();
            }

            $aircraftTypes = AircraftType::find()
                ->select(['name'])
                ->indexBy('id')
                ->orderBy(['name' => SORT_ASC])->column();

            return $this->render('create', [
                'model' => $model,
                'aircraftTypes' => $aircraftTypes,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Updates an existing AircraftConfiguration model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if(Yii::$app->user->can('aircraftConfigurationCrud')){
            $model = $this->findModel($id);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $this->logInfo('Updated aircraft config', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $aircraftTypes = AircraftType::find()
                ->select(['name'])
                ->indexBy('id')
                ->orderBy(['name' => SORT_ASC])
                ->column();

            return $this->render('update', [
                'model' => $model,
                'aircraftTypes' => $aircraftTypes,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Deletes an existing AircraftConfiguration model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if(Yii::$app->user->can('aircraftConfigurationCrud')){
            $this->findModel($id)->delete();
            $this->logInfo('Deleted aircraft config', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
            return $this->redirect(['index']);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Finds the AircraftConfiguration model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return AircraftConfiguration the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AircraftConfiguration::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
