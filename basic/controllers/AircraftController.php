<?php

namespace app\controllers;

use app\helpers\LoggerTrait;
use app\models\Aircraft;
use app\models\AircraftConfiguration;
use app\models\AircraftSearch;
use app\models\AircraftType;
use app\models\SubmittedFlightPlan;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;

/**
 * AircraftController implements the CRUD actions for Aircraft model.
 */
class AircraftController extends Controller
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
                    'only' => ['view', 'create', 'update', 'delete', 'move'],
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
     * Lists all Aircraft models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new AircraftSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        $dataProvider->sort->defaultOrder = [
            'registration' => SORT_ASC,
        ];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Aircraft model.
     * @param int $id ID
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
     * Retrieve all aircraft configurations prepared for dropdown lists
     */
    protected function getAircraftConfigurations()
    {
        return AircraftConfiguration::find()
            ->joinWith('aircraftType')
            ->select([
                "CONCAT(aircraft_type.name, ' (', aircraft_configuration.name, ')') AS fullname"
            ])
            ->indexBy('id')
            ->orderBy("fullname")
            ->column();
    }

    /**
     * Creates a new Aircraft model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        if(Yii::$app->user->can('aircraftCrud')){
            $model = new Aircraft();

            if ($this->request->isPost) {
                if ($model->load($this->request->post()) && $model->save()) {
                    $this->logInfo('Created aircraft', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } else {
                $model->loadDefaultValues();
            }

            $aircraftConfigurations = $this->getAircraftConfigurations();

            return $this->render('create', [
                'model' => $model,
                'aircraftConfigurations' => $aircraftConfigurations,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Updates an existing Aircraft model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if(Yii::$app->user->can('aircraftCrud')){
            $model = $this->findModel($id);

            $model->setScenario(Aircraft::SCENARIO_UPDATE);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                $this->logInfo('Updated aircraft', ['model' => $model, 'user' => Yii::$app->user->identity->license]);
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $aircraftConfigurations = $this->getAircraftConfigurations();

            return $this->render('update', [
                'model' => $model,
                'aircraftConfigurations' => $aircraftConfigurations,
            ]);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    public function actionMove($id)
    {
        if (Yii::$app->user->can('moveAircraft')) {
            $model = Aircraft::findOne($id);
            if ($model === null) {
                throw new NotFoundHttpException('Aircraft not found.');
            }

            // Check if there is an active submitted flight plan for this aircraft
            $activePlan = SubmittedFlightPlan::findOne(['aircraft_id' => $id]);
            if ($activePlan !== null) {
                throw new ForbiddenHttpException('You cannot change the location of an aircraft with an active submitted flight plan.');
            }

            $model->setScenario(Aircraft::SCENARIO_MOVE);

            if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
                Yii::$app->session->setFlash('success', 'Aircraft has been moved to ' . $model->location . ' airport.');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            return $this->render('move', [
                'model' => $model,
            ]);
        } else {
            throw new ForbiddenHttpException('You do not have permission to move this aircraft.');
        }
    }

    /**
     * Deletes an existing Aircraft model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if(Yii::$app->user->can('aircraftCrud')){
            $this->findModel($id)->delete();
            $this->logInfo('Deleted aircraft', ['id' => $id, 'user' => Yii::$app->user->identity->license]);
            return $this->redirect(['index']);
        } else {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * Finds the Aircraft model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Aircraft the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Aircraft::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
